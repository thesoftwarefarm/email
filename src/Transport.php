<?php

namespace TsfCorp\Email;

use AsyncAws\Ses\SesClient;
use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Throwable;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class Transport
{
    /**
     * @var \Symfony\Component\Mailer\Transport\TransportInterface
     */
    private $provider;
    /**
     * @var string|null
     */
    private $remote_identifier;
    /**
     * @var string
     */
    private $message;

    /**
     * @param \Symfony\Component\Mailer\Transport\TransportInterface $provider
     */
    public function __construct(TransportInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return string|null
     */
    public function getRemoteIdentifier()
    {
        return $this->remote_identifier;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Throwable
     */
    public function send(EmailModel $email)
    {
        try {
            $from = $this->fromJson($email->from);

            $to = $email->to->map(fn(EmailRecipient $r) => $r->asMimeAddress());
            $cc = $email->cc->map(fn(EmailRecipient $r) => $r->asMimeAddress());
            $bcc = $email->bcc->map(fn(EmailRecipient $r) => $r->asMimeAddress());

            $reply_to = array_map(function ($recipient) {
                return new Address($recipient->email, $recipient->name ?? '');
            }, $this->fromJson($email->reply_to));

            $attachments = array_map(function ($attachment) {
                return (new Attachment())
                    ->setPath($attachment->path)
                    ->setDisk($attachment->disk)
                    ->setName($attachment->name ?? null);
            }, $this->fromJson($email->attachments));

            $symfony_email = (new \Symfony\Component\Mime\Email())
                ->from(new Address($from->email, $from->name ?? ''))
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

            $response = $this->provider->send($symfony_email);

            $this->remote_identifier = $response->getMessageId();
            $this->message = 'Queued';
        } catch (Throwable $t) {
            throw $t;
        }
    }

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @return \TsfCorp\Email\Transport
     * @throws \Exception
     */
    public static function resolveFor(EmailModel $email)
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

    /**
     * @param $json
     * @return array
     */
    private function fromJson($json)
    {
        $decoded = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = [];
        }

        return $decoded;
    }
}
