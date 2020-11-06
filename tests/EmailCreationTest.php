<?php

namespace TsfCorp\Email\Tests;

use TsfCorp\Email\Email;
use TsfCorp\Email\Jobs\EmailJob;

class EmailCreationTest extends TestCase
{
    public function test_it_throws_exception_if_from_address_is_missing()
    {
        // first unset default from address
        $this->app['config']->set('email.from.address', null);
        $this->app['config']->set('email.from.name', null);

        $this->expectExceptionMessage('Invalid from address');

        $email = new Email();
        $email->to('to@mail.com');
        $email->enqueue();
    }

    public function test_it_throws_exception_if_to_address_is_missing()
    {
        $this->expectExceptionMessage('Missing to address.');

        $email = new Email();
        $email->enqueue();
    }

    public function test_default_from_address_is_used()
    {
        $email = new Email();
        $email->to('to@mail.com');
        $email->enqueue();

        $model = $email->getModel();

        $from = json_decode($model->from);

        $this->assertEquals(config('email.from.address'), $from->email);
        $this->assertEquals(config('email.from.name'), $from->name);
    }

    public function test_email_is_saved_in_database()
    {
        $email = (new Email())
            ->from('sender@mail.com', 'Sender Name')
            ->to('to@mail.com', 'To recipient')
            ->cc('cc@mail.com', 'Cc recipient')
            ->bcc('bcc@mail.com', 'Bcc recipient')
            ->subject('Subject')
            ->body('Body')
            ->addAttachment('attachment.txt')
            ->via('mailgun')
            ->enqueue();

        $model = $email->getModel()->fresh();

        $from = json_decode($model->from);
        $to = json_decode($model->to);
        $cc = json_decode($model->cc);
        $bcc = json_decode($model->bcc);
        $attachments = json_decode($model->attachments);

        $this->assertEquals(config('email.project'), $model->project);

        $this->assertEquals('sender@mail.com', $from->email);
        $this->assertEquals('Sender Name', $from->name);

        $this->assertCount(1, $to);
        $this->assertEquals('to@mail.com', $to[0]->email);
        $this->assertEquals('To recipient', $to[0]->name);

        $this->assertCount(1, $cc);
        $this->assertEquals('cc@mail.com', $cc[0]->email);
        $this->assertEquals('Cc recipient', $cc[0]->name);

        $this->assertCount(1, $bcc);
        $this->assertEquals('bcc@mail.com', $bcc[0]->email);
        $this->assertEquals('Bcc recipient', $bcc[0]->name);

        $this->assertEquals('Subject', $model->subject);
        $this->assertEquals('Body', $model->body);
        $this->assertEquals('mailgun', $model->provider);

        $this->assertCount(1, $attachments);
        $this->assertEquals('attachment.txt', $attachments[0]);

        $this->assertEquals('pending', $model->status);
    }

    public function test_enqueue_method_inserts_the_record_and_job_not_dispatched()
    {
        $this->doesntExpectJobs(EmailJob::class);

        $email = (new Email)->to('to@mail.com')->enqueue();

        $this->assertEquals('pending', $email->getModel()->status);
    }

    public function test_dispatch_method_throws_exception_if_model_not_set()
    {
        $this->expectExceptionMessage('There is no email to be dispatched.');

        (new Email)->to('to@mail.com')->dispatch();
    }

    public function test_message_is_added_in_database_and_job_dispatched()
    {
        $this->expectsJobs(EmailJob::class);

        $email = (new Email)->to('to@mail.com')->enqueue()->dispatch();

        $this->assertEquals('queued', $email->getModel()->status);
    }
}