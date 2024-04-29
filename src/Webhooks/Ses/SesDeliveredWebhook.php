<?php

namespace TsfCorp\Email\Webhooks\Ses;

use TsfCorp\Email\Webhooks\DeliveredWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class SesDeliveredWebhook implements DeliveredWebhook
{
    use InteractsWithSesPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return \TsfCorp\Email\Webhooks\WebhookRecipient[]
     */
    public function getRecipients(): array
    {
        $recipients = data_get($this->payload, 'delivery.recipients', []);

        return array_map(fn($email) => WebhookRecipient::makeForDelivered($email), $recipients);
    }
}
