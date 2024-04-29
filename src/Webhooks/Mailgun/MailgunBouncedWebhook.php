<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\BouncedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class MailgunBouncedWebhook implements BouncedWebhook
{
    use InteractsWithMailgunPayload;

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
        $recipient = data_get($this->payload, 'event-data.recipient');

        $reason_from_description = data_get($this->payload, 'event-data.delivery-status.description');
        $reason_from_message = data_get($this->payload, 'event-data.delivery-status.message');

        return [
            WebhookRecipient::makeForBounced($recipient, $reason_from_description ?? $reason_from_message),
        ];
    }
}
