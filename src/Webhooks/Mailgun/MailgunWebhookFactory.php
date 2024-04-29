<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\IncomingWebhook;
use TsfCorp\Email\Webhooks\UnknownWebhook;
use TsfCorp\Email\Webhooks\WebhookFactory;

class MailgunWebhookFactory implements WebhookFactory
{
    public static function make(array $payload): IncomingWebhook
    {
        $event = data_get($payload, 'event-data.event');

        return match ($event) {
            'delivered' => new MailgunDeliveredWebhook($payload),
            'failed' => new MailgunBouncedWebhook($payload),
            'opened' => new MailgunOpenedWebhook($payload),
            'clicked' => new MailgunClickedWebhook($payload),
            'unsubscribed' => new MailgunUnsubscribedWebhook($payload),
            'complained' => new MailgunComplainedWebhook($payload),
            default => new UnknownWebhook(),
        };
    }
}
