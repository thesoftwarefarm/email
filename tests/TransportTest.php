<?php

namespace TsfCorp\Email\Tests;


use Illuminate\Support\Arr;
use TsfCorp\Email\Email;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Transport\MailgunTransport;
use TsfCorp\Email\Transport\SesTransport;
use TsfCorp\Email\Transport\Transport;

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

        $this->assertInstanceOf(MailgunTransport::class, $transport);
    }

    public function test_it_resolves_ses_transport()
    {
        $email = new EmailModel();
        $email->provider = 'ses';

        $transport = Transport::resolveFor($email);

        $this->assertInstanceOf(SesTransport::class, $transport);
    }
}