<?php

namespace TsfCorp\Email\Tests;

use Mockery;
use TsfCorp\Email\Email;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Events\EmailSent;
use TsfCorp\Email\Jobs\EmailJob;
use TsfCorp\Email\Transport;

class EmailSendingTest extends TestCase
{
    public function test_job_throws_exception_if_record_not_found()
    {
        $this->expectExceptionMessage('Record with id [0] not found.');

        (new EmailJob(0))->handle();
    }

    public function test_email_is_marked_as_failed_if_provider_can_not_be_resolved()
    {
        $email = (new Email())->to('to@mail.com')->enqueue();

        // overwrite provider to an invalid one
        $email->getModel()->provider = 'invalid_provider';
        $email->getModel()->save();

        $this->expectsEvents(EmailFailed::class);

        (new EmailJob($email->getModel()->id))->handle();

        $model = $email->getModel()->fresh();

        $this->assertEquals('failed', $model->status);
        $this->assertEquals('Invalid email provider', $model->notes);
    }

    public function test_email_is_marked_as_failed_if_reached_max_number_of_retries()
    {
        $this->app['config']->set('email.max_retries', 5);

        $email = (new Email())->to('to@mail.com')->enqueue();

        // overwrite retries number to match max retries
        $email->getModel()->retries = 5;
        $email->getModel()->save();

        $job = new EmailJob($email->getModel()->id);

        $transport = Mockery::mock(Transport::class);

        $this->expectsEvents(EmailFailed::class);

        $job->sendVia($transport);

        $email = $email->getModel()->fresh();
        $this->assertEquals('failed', $email->status);
        $this->assertEquals('Max retry limit reached. ', $email->notes);
    }

    public function test_email_is_retried_if_sending_to_provider_failed()
    {
        $email = (new Email())->to('to@mail.com')->enqueue();

        $job = new EmailJob($email->getModel()->id);

        $transport = Mockery::mock(Transport::class);
        $transport->shouldReceive('send')->andThrow(\Exception::class, 'Some Exception');

        $this->expectsJobs(EmailJob::class);
        $this->expectsEvents(EmailFailed::class);

        $job->sendVia($transport);

        $email = $email->getModel()->fresh();
        $this->assertEquals('queued', $email->status);
        $this->assertEquals('1', $email->retries);
        $this->assertEquals('Some Exception', $email->notes);
    }

    public function test_email_is_successfully_sent_to_provider()
    {
        $email = (new Email())->to('to@mail.com')->enqueue();

        $job = new EmailJob($email->getModel()->id);

        $transport = Mockery::mock(Transport::class);
        $transport->shouldReceive('send');
        $transport->shouldReceive('getRemoteIdentifier')->andReturn('REMOTE_IDENTIFIER');
        $transport->shouldReceive('getMessage')->andReturn('Queued. Thank you!');

        $this->expectsEvents(EmailSent::class);
        $job->sendVia($transport);

        $email = $email->getModel()->fresh();
        $this->assertEquals('sent', $email->status);
        $this->assertEquals('REMOTE_IDENTIFIER', $email->remote_identifier);
        $this->assertEquals('Queued. Thank you!', $email->notes);
    }

    public function test_that_email_is_not_sent_in_non_production_environment()
    {
        $this->app['config']->set('app.env', 'local');

        $email = (new Email())->to('to@mail.com')->enqueue();

        $job = new EmailJob($email->getModel()->id);

        $transport = Mockery::mock(Transport::class);

        $this->expectsEvents(EmailFailed::class);

        $job->sendVia($transport);

        $email = $email->getModel()->fresh();
        $this->assertEquals('failed', $email->status);
        $this->assertEquals('Sending email is disabled in non production environment.', $email->notes);
    }

//    /**
//     * Method to do end to end testing with email providers.
//     *
//     * @throws \Exception
//     */
//    public function test_end_to_end()
//    {
//        $this->app['config']->set('email.providers', [
//            'mailgun' => [
//                'api_key' => 'mailgun_api_key',
//                'domain' => '',
//                'region' => '',
//            ],
//            'ses' => [
//                'key' => 'ses_api_secret',
//                'secret' => 'ses_api_secret',
//                'region' => 'eu-west-1',
//            ],
//            'google-smtp' => [
//                'email' => 'me@mail.com',
//                'password' => 'mypassword',
//            ]
//        ]);
//
//        $this->app['config']->set('email.from', [
//            'address' => 'default@address.com',
//            'name' => 'Default Name'
//        ]);
//
//        $email = (new Email())
//            ->subject('My email')
//            ->to('mail@mail.com')
//            ->body('<h1>Body</h1>')
//            ->addAttachment(__DIR__.'/../stubs/attachment.txt')
//            ->via('mailgun')
//            ->enqueue();
//
//        $job = new EmailJob($email->getModel()->id);
//
//        $job->handle();
//
//        dd($email->getModel()->fresh()->toArray());
//    }
}