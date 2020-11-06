<?php

namespace TsfCorp\Email\Tests;

use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Transport;

class TransportTest extends TestCase
{
    public function test_it_throws_exception_if_could_not_resolve_transport()
    {
        $email = new EmailModel();

        $this->expectExceptionMessage('Invalid email provider');

        Transport::resolveFor($email);
    }

    public function test_it_resolves_mailgun_transport()
    {
        $email = new EmailModel();
        $email->provider = 'mailgun';

        $transport = Transport::resolveFor($email);

        $this->assertInstanceOf(MailgunApiTransport::class, $transport->getProvider());
    }

    public function test_it_resolves_ses_transport()
    {
        $email = new EmailModel();
        $email->provider = 'ses';

        $transport = Transport::resolveFor($email);

        $this->assertInstanceOf(SesApiAsyncAwsTransport::class, $transport->getProvider());
    }

    public function test_it_resolves_google_smtp_transport()
    {
        $email = new EmailModel();
        $email->provider = 'google-smtp';

        $transport = Transport::resolveFor($email);

        $this->assertInstanceOf(GmailSmtpTransport::class, $transport->getProvider());
    }
}