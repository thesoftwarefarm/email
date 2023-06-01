<?php

namespace TsfCorp\Email\Tests;

use Illuminate\Support\Facades\Bus;
use TsfCorp\Email\Email;
use TsfCorp\Email\Jobs\EmailJob;
use TsfCorp\Email\Models\EmailModel;

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
            ->replyTo('reply_to@mail.com', 'Reply to name')
            ->subject('Subject')
            ->body('Body')
            ->addAttachment('attachment.txt')
            ->via('mailgun')
            ->enqueue();

        $model = $email->getModel()->fresh();

        $from = json_decode($model->from);
        $reply_to = json_decode($model->reply_to);
        $attachments = json_decode($model->attachments);

        $this->assertEquals(config('email.project'), $model->project);

        $this->assertEquals('sender@mail.com', $from->email);
        $this->assertEquals('Sender Name', $from->name);

        $this->assertCount(1, $model->to);
        $this->assertEquals('to@mail.com', $model->to->first()->email);
        $this->assertEquals('To recipient', $model->to->first()->name);

        $this->assertCount(1, $model->cc);
        $this->assertEquals('cc@mail.com', $model->cc->first()->email);
        $this->assertEquals('Cc recipient', $model->cc->first()->name);

        $this->assertCount(1, $model->bcc);
        $this->assertEquals('bcc@mail.com', $model->bcc->first()->email);
        $this->assertEquals('Bcc recipient', $model->bcc->first()->name);

        $this->assertCount(1, $reply_to);
        $this->assertEquals('reply_to@mail.com', $reply_to[0]->email);
        $this->assertEquals('Reply to name', $reply_to[0]->name);

        $this->assertEquals('Subject', $model->subject);
        $this->assertEquals('Body', $model->body);
        $this->assertEquals('mailgun', $model->provider);

        $this->assertCount(1, $attachments);
        $this->assertEquals('local', $attachments[0]->disk);
        $this->assertEquals('attachment.txt', $attachments[0]->path);

        $this->assertEquals(EmailModel::STATUS_PENDING, $model->status);
    }

    public function test_enqueue_method_inserts_the_record_and_job_not_dispatched()
    {
        Bus::fake();

        $email = (new Email)->to('to@mail.com')->enqueue();

        $this->assertEquals(EmailModel::STATUS_PENDING, $email->getModel()->status);
        Bus::assertNotDispatched(EmailJob::class);
    }

    public function test_dispatch_method_throws_exception_if_model_not_set()
    {
        $this->expectExceptionMessage('There is no email to be dispatched.');

        (new Email)->to('to@mail.com')->dispatch();
    }

    public function test_message_is_added_in_database_and_job_dispatched()
    {
        Bus::fake();

        $email = (new Email)->to('to@mail.com')->enqueue()->dispatch();

        $this->assertEquals(EmailModel::STATUS_QUEUED, $email->getModel()->status);
        Bus::assertDispatched(EmailJob::class);
    }
}
