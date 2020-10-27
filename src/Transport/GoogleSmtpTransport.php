<?php

namespace TsfCorp\Email\Transport;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;
use TsfCorp\Email\Models\EmailModel;

class GoogleSmtpTransport extends Transport
{
    /**
     * @var \Symfony\Component\Mailer\Mailer
     */
    private $mailer;

    /**
     * GoogleSmtpTransport constructor.
     * @param \Symfony\Component\Mailer\Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
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

            $this->mailer->send($symfony_email);

            $this->message = 'Queued.';
        }
        catch (Throwable $t)
        {
            throw $t;
        }
    }
}