<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\FailedWebhook;

class MailgunFailedWebhook implements FailedWebhook
{
    use InteractsWithMailgunPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function getReason(): ?string
    {
        $description = data_get($this->payload, 'event-data.delivery-status.description');
        $message = data_get($this->payload, 'event-data.delivery-status.message');

        return $description ?? $message;
    }
}
