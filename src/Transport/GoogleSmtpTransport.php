<?php

namespace TsfCorp\Email\Transport;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\NamedAddress;
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
                return new NamedAddress($recipient->email, $recipient->name ? $recipient->name : '');
            }, $email->decodeRecipient($email->to));

            $cc = array_map(function ($recipient) {
                return new NamedAddress($recipient->email, $recipient->name ? $recipient->name : '');
            }, $email->decodeRecipient($email->cc));

            $bcc = array_map(function ($recipient) {
                return new NamedAddress($recipient->email, $recipient->name ? $recipient->name : '');
            }, $email->decodeRecipient($email->bcc));

            $symfony_email = (new Email())
                ->from(new NamedAddress($from->email, $from->name ? $from->name : ''))
                ->to(...$to)
                ->cc(...$cc)
                ->bcc(...$bcc)
                ->subject($email->subject)
                ->text('To view the message, please use an HTML compatible email viewer')
                ->html($email->body);

            $this->mailer->send($symfony_email);

            $this->message = 'Queued.';
        }
        catch (Throwable $t)
        {
            throw $t;
        }
    }
}