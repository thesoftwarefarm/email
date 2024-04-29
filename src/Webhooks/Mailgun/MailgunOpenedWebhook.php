<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\OpenedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class MailgunOpenedWebhook implements OpenedWebhook
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
            WebhookRecipient::makeForOpened($recipient),
        ];
    }
}
