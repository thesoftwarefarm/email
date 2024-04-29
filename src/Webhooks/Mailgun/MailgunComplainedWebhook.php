<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\ComplainedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class MailgunComplainedWebhook implements ComplainedWebhook
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
        $recipient = data_get($this->getPayload(), 'event-data.recipient');

        return [
            WebhookRecipient::makeForComplained($recipient),
        ];
    }
}
