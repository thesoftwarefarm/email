<?php

namespace TsfCorp\Email\Webhooks\Ses;

use TsfCorp\Email\Webhooks\FailedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class SesFailedWebhook implements FailedWebhook
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
        $recipients = data_get($this->payload, 'bounce.bouncedRecipients', []);

        return array_map(fn($recipient) => WebhookRecipient::makeForFailed($recipient['emailAddress'], $recipient['diagnosticCode'] ?? null), $recipients);
    }
}
