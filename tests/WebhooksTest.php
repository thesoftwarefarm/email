<?php

namespace TsfCorp\Email\Tests;

use Illuminate\Support\Facades\Event;
use Mockery;
use TsfCorp\Email\Email;
use TsfCorp\Email\Events\EmailClicked;
use TsfCorp\Email\Events\EmailComplained;
use TsfCorp\Email\Events\EmailDelivered;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Events\EmailOpened;
use TsfCorp\Email\Events\EmailUnsubscribed;
use TsfCorp\Email\Models\EmailRecipient;
use TsfCorp\Email\Webhooks\ClickedWebhook;
use TsfCorp\Email\Webhooks\ComplainedWebhook;
use TsfCorp\Email\Webhooks\DeliveredWebhook;
use TsfCorp\Email\Webhooks\BouncedWebhook;
use TsfCorp\Email\Webhooks\OpenedWebhook;
use TsfCorp\Email\Webhooks\UnsubscribedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class WebhooksTest extends TestCase
{
    public function test_it_processes_delivered_webhook()
    {
        Event::fake();

        $to = 'to@mail.com';

        $email = (new Email())->to($to)->enqueue()->getModel();
        $email->remote_identifier = 'identifier';
        $email->save();

        $webhook = Mockery::mock(DeliveredWebhook::class);
        $webhook->shouldReceive('getRemoteIdentifier')->andReturn($email->remote_identifier);
        $webhook->shouldReceive('getRecipients')->andReturn([WebhookRecipient::makeForDelivered($to)]);
        $webhook->shouldReceive('getPayload')->andReturn([]);

        $email->processIncomingWebhook($webhook);

        $recipient = $email->getRecipientByEmail($to);

        $this->assertEquals(EmailRecipient::STATUS_DELIVERED, $recipient->status);
        Event::assertDispatched(EmailDelivered::class);
    }

    public function test_it_processes_bounced_webhook()
    {
        Event::fake();

        $to = 'to@mail.com';

        $email = (new Email())->to($to)->enqueue()->getModel();
        $email->remote_identifier = 'identifier';
        $email->save();

        $webhook = Mockery::mock(BouncedWebhook::class);
        $webhook->shouldReceive('getRemoteIdentifier')->andReturn($email->remote_identifier);
        $webhook->shouldReceive('getRecipients')->andReturn([WebhookRecipient::makeForBounced($to, 'reason')]);
        $webhook->shouldReceive('getPayload')->andReturn([]);

        $email->processIncomingWebhook($webhook);

        $recipient = $email->getRecipientByEmail($to);

        $this->assertEquals(EmailRecipient::STATUS_FAILED, $recipient->status);
        $this->assertEquals('reason', $recipient->notes);
        Event::assertDispatched(EmailFailed::class);
    }

    public function test_it_processes_opened_webhook()
    {
        Event::fake();

        $to = 'to@mail.com';

        $email = (new Email())->to($to)->enqueue()->getModel();
        $email->remote_identifier = 'identifier';
        $email->save();

        $webhook = Mockery::mock(OpenedWebhook::class);
        $webhook->shouldReceive('getRemoteIdentifier')->andReturn($email->remote_identifier);
        $webhook->shouldReceive('getRecipients')->andReturn([WebhookRecipient::makeForOpened($to)]);
        $webhook->shouldReceive('getPayload')->andReturn([]);

        $email->processIncomingWebhook($webhook);

        Event::assertDispatched(EmailOpened::class);
    }

    public function test_it_processes_clicked_webhook()
    {
        Event::fake();

        $to = 'to@mail.com';

        $email = (new Email())->to($to)->enqueue()->getModel();
        $email->remote_identifier = 'identifier';
        $email->save();

        $webhook = Mockery::mock(ClickedWebhook::class);
        $webhook->shouldReceive('getRemoteIdentifier')->andReturn($email->remote_identifier);
        $webhook->shouldReceive('getRecipients')->andReturn([WebhookRecipient::makeForClicked($to)]);
        $webhook->shouldReceive('getPayload')->andReturn([]);

        $email->processIncomingWebhook($webhook);

        Event::assertDispatched(EmailClicked::class);
    }

    public function test_it_processes_unsubscribed_webhook()
    {
        Event::fake();

        $to = 'to@mail.com';

        $email = (new Email())->to($to)->enqueue()->getModel();
        $email->remote_identifier = 'identifier';
        $email->save();

        $webhook = Mockery::mock(UnsubscribedWebhook::class);
        $webhook->shouldReceive('getRemoteIdentifier')->andReturn($email->remote_identifier);
        $webhook->shouldReceive('getRecipients')->andReturn([WebhookRecipient::makeForUnsubscribed($to)]);
        $webhook->shouldReceive('getPayload')->andReturn([]);

        $email->processIncomingWebhook($webhook);

        Event::assertDispatched(EmailUnsubscribed::class);
    }

    public function test_it_processes_complained_webhook()
    {
        Event::fake();

        $to = 'to@mail.com';

        $email = (new Email())->to($to)->enqueue()->getModel();
        $email->remote_identifier = 'identifier';
        $email->save();

        $webhook = Mockery::mock(ComplainedWebhook::class);
        $webhook->shouldReceive('getRemoteIdentifier')->andReturn($email->remote_identifier);
        $webhook->shouldReceive('getRecipients')->andReturn([WebhookRecipient::makeForComplained($to)]);
        $webhook->shouldReceive('getPayload')->andReturn([]);

        $email->processIncomingWebhook($webhook);

        Event::assertDispatched(EmailComplained::class);
    }
}
