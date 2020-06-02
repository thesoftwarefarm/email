<?php

namespace TsfCorp\Email\Transport;

use Aws\Ses\SesClient;
use Exception;
use Mailgun\Mailgun;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Mailer;
use TsfCorp\Email\Models\EmailModel;

abstract class Transport
{
    /**
     * @var string|null
     */
    protected $remote_identifier;
    /**
     * @var string
     */
    protected $message;

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     */
    abstract public function send(EmailModel $email);

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
     * Determine with which third party service this email should be sent
     *
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @return \TsfCorp\Email\Transport\Transport
     * @throws \Exception
     */
    public static function resolveFor(EmailModel $email)
    {
        if ($email->provider == 'mailgun')
        {
            if (config('email.providers.mailgun.api_url'))
                $mailgun = Mailgun::create(config('email.providers.mailgun.api_key'), config('email.providers.mailgun.api_url'));
            else
                $mailgun = Mailgun::create(config('email.providers.mailgun.api_key'));

            return new MailgunTransport($mailgun);
        }

        if ($email->provider == 'ses')
        {
            $ses = new SesClient([
                'region' => config('email.providers.ses.region'),
                'version' => 'latest',
                'service' => 'email',
                'credentials' => [
                    'key' => config('email.providers.ses.key'),
                    'secret' => config('email.providers.ses.secret'),
                ],
            ]);

            return new SesTransport($ses);
        }

        if ($email->provider == 'google-smtp')
        {
            $symfony_transport = new GmailSmtpTransport(config('email.providers.google-smtp.email'), config('email.providers.google-smtp.password'));
            $symfony_mailer = new Mailer($symfony_transport);

            return new GoogleSmtpTransport($symfony_mailer);
        }

        throw new Exception('Invalid email provider');
    }
}