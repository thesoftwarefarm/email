<?php

namespace TsfCorp\Email\Transport;

use Mailgun\Mailgun;
use Throwable;
use TsfCorp\Email\Models\EmailModel;

class MailgunTransport extends Transport
{
    /**
     * @var \Mailgun\Mailgun
     */
    private $mailgun;

    /**
     * MailgunTransport constructor.
     * @param \Mailgun\Mailgun $mailgun
     */
    public function __construct(Mailgun $mailgun)
    {
        $this->mailgun = $mailgun;
    }

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @throws \Throwable
     */
    public function send(EmailModel $email)
    {
        try
        {
            $to = $email->prepareToAddress();
            $cc = $email->prepareCcAddress();
            $bcc = $email->prepareBccAddress();

            $response = $this->mailgun->messages()->send(config('email.providers.mailgun.domain'), [
                'from'       => $email->prepareFromAddress(),
                'to'         => is_array($to) && count($to) ? implode(', ', $to) : null,
                'cc'         => is_array($cc) && count($cc) ? implode(', ', $cc) : null,
                'bcc'        => is_array($bcc) && count($bcc) ? implode(', ', $bcc) : null,
                'subject'    => $email->subject,
                'text'       => 'To view the message, please use an HTML compatible email viewer',
                'html'       => $email->body,
            ]);

            $this->remote_identifier = $response->getId();
            $this->message = $response->getMessage();
        }
        catch (Throwable $t)
        {
            throw $t;
        }
    }
}