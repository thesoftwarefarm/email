<?php

namespace TsfCorp\Email\Transport;

use Aws\Ses\Exception\SesException;
use Aws\Ses\SesClient;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use TsfCorp\Email\Models\EmailModel;

class SesTransport extends Transport
{
    /**
     * @var SesClient
     */
    private $ses;

    /**
     * MailgunTransport constructor.
     * @param \Aws\Ses\SesClient $ses
     */
    public function __construct(SesClient $ses)
    {
        $this->ses = $ses;
    }

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @throws \Throwable
     */
    public function send(EmailModel $email)
    {
        try
        {
            $from = $email->decodeRecipient($email->from);

            $to = array_map(function ($recipient) {
                return new Address($recipient->email, $recipient->name ? $recipient->name : '');
            }, $email->decodeRecipient($email->to));

            $cc = array_map(function ($recipient) {
                return new Address($recipient->email, $recipient->name ? $recipient->name : '');
            }, $email->decodeRecipient($email->cc));

            $bcc = array_map(function ($recipient) {
                return new Address($recipient->email, $recipient->name ? $recipient->name : '');
            }, $email->decodeRecipient($email->bcc));

            $attachments = json_decode($email->attachments, true);

            $symfony_email = (new Email())
                ->from(new Address($from->email, $from->name ? $from->name : ''))
                ->to(...$to)
                ->cc(...$cc)
                ->bcc(...$bcc)
                ->subject($email->subject)
                ->text('To view the message, please use an HTML compatible email viewer')
                ->html($email->body);

            if (!empty($attachments)) {
                foreach ($attachments as $attachment_path) {
                    $symfony_email->attachFromPath($attachment_path);
                }
            }

            $response = $this->ses->sendRawEmail([
                'RawMessage' => [
                    'Data' => $symfony_email
                ]
            ]);

            $this->remote_identifier = $response->get('MessageId');
            $this->message = 'Queued.';
        } catch (SesException $error) {
            throw $error->getAwsErrorMessage();
        }
    }
}