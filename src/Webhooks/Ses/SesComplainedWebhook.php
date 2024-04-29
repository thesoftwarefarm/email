<?php

namespace TsfCorp\Email\Webhooks\Ses;

use TsfCorp\Email\Webhooks\ComplainedWebhook;

class SesComplainedWebhook implements ComplainedWebhook
{
    use InteractsWithSesPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function getRecipients(): array
    {
        $recipients = data_get($this->payload, 'complaint.complainedRecipients', []);

        return array_map(fn($recipient) => $recipient['emailAddress'], $recipients);
    }
}
