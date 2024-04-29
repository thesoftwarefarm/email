<?php

namespace TsfCorp\Email\Webhooks\Ses;

use TsfCorp\Email\Webhooks\DeliveredWebhook;

class SesDeliveredWebhook implements DeliveredWebhook
{
    use InteractsWithSesPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function getRecipients(): array
    {
        return data_get($this->payload, 'delivery.recipients', []);
    }
}
