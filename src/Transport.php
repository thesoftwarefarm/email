<?php

namespace TsfCorp\Email;

use AsyncAws\Ses\SesClient;
use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Throwable;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class Transport
{
    private TransportInterface $provider;
    private ?string $remote_identifier;
    private ?string $message;

    public function __construct(TransportInterface $provider)
    {
        $this->provider = $provider;
    }

    public function getProvider(): TransportInterface
    {
        return $this->provider;
    }

    public function getRemoteIdentifier(): ?string
    {
        return $this->remote_identifier;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function send(EmailModel $email): void
    {
        try {
            $from = $this->fromJson($email->from);

            $to = $email->to->map(fn(EmailRecipient $r) => $r->asMimeAddress());
            $cc = $email->cc->map(fn(EmailRecipient $r) => $r->asMimeAddress());
            $bcc = $email->bcc->map(fn(EmailRecipient $r) => $r->asMimeAddress());

            $reply_to = array_map(function ($recipient) {
                return new Address($recipient['email'], $recipient['name'] ?? '');
            }, $this->fromJson($email->reply_to));

            $attachments = array_map(function ($attachment) {
                return new Attachment(
                    path: $attachment['path'],
                    name: $attachment['name'],
                    disk: $attachment['disk'],
                );
            }, $this->fromJson($email->attachments));

            $metadata = $this->fromJson($email->metadata);

            $symfony_email = (new \Symfony\Component\Mime\Email())
                ->from(new Address($from['email'], $from['name'] ?? ''))
                ->to(...$to)
                ->cc(...$cc)
                ->bcc(...$bcc)
                ->replyTo(...$reply_to)
                ->subject($email->subject ?? '')
                ->text('To view the message, please use an HTML compatible email viewer')
                ->html($email->body);

            try {
                foreach ($attachments as $attachment) {
                    if ($attachment->getDisk() == 'local') {
                        $symfony_email->attachFromPath($attachment->getPath(), $attachment->getName());
                    } else {
                        $symfony_email->attach(Storage::disk($attachment->getDisk())->readStream($attachment->getPath()), $attachment->getName());
                    }
                }
            } catch (Throwable $e) {
                // do not rethrow the error in case a file can't be attached.
            }

            if ($metadata) {
                foreach ($metadata as $key => $value) {
                    $symfony_email->getHeaders()->add(new MetadataHeader($key, $value));
                }
            }

            $response = $this->provider->send($symfony_email);

            $this->remote_identifier = $response->getMessageId();
            $this->message = 'Queued';
        } catch (Throwable $t) {
            throw $t;
        }
    }

    public static function resolveFor(EmailModel $email): static
    {
        $provider = null;

        if ($email->provider == 'mailgun') {
            $provider = new MailgunApiTransport(config('email.providers.mailgun.api_key'), config('email.providers.mailgun.domain'), config('email.providers.mailgun.region'));
        }

        if ($email->provider == 'ses') {
            $client = new SesClient([
                'accessKeyId' => config('email.providers.ses.key'),
                'accessKeySecret' => config('email.providers.ses.secret'),
                'region' => config('email.providers.ses.region'),
            ]);

            $provider = new SesApiAsyncAwsTransport($client);
        }

        if ($email->provider == 'google-smtp') {
            $provider = new GmailSmtpTransport(config('email.providers.google-smtp.email'), config('email.providers.google-smtp.password'));
        }

        if (!$provider) {
            throw new Exception('Invalid email provider');
        }

        return new static($provider);
    }

    private function fromJson(?string $json): array
    {
        $decoded = json_decode((string)$json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = [];
        }

        return $decoded;
    }
}
