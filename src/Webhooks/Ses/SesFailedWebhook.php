<?php

namespace TsfCorp\Email\Webhooks\Ses;

use TsfCorp\Email\Webhooks\FailedWebhook;

class SesFailedWebhook implements FailedWebhook
{
    use InteractsWithSesPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function getRecipients(): array
    {
        $recipients = data_get($this->payload, 'bounce.bouncedRecipients', []);

        return array_map(fn($recipient) => $recipient['emailAddress'], $recipients);
    }

    public function getReason(): ?string
    {
        return '';
    }
}
