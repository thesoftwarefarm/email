<?php

namespace TsfCorp\Email\Webhooks\Ses;

use TsfCorp\Email\Webhooks\IncomingWebhook;
use TsfCorp\Email\Webhooks\UnknownWebhook;
use TsfCorp\Email\Webhooks\WebhookFactory;

class SesWebhookFactory implements WebhookFactory
{
    public static function make(array $payload): IncomingWebhook
    {
        $event = data_get($payload, 'eventType');

        return match ($event) {
            'Delivery' => new SesDeliveredWebhook($payload),
            'Bounce' => new SesBouncedWebhook($payload),
            'Complaint' => new SesComplainedWebhook($payload),
            default => new UnknownWebhook(),
        };
    }
}
