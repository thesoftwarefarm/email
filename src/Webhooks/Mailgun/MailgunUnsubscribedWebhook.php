<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\UnsubscribedWebhook;

class MailgunUnsubscribedWebhook implements UnsubscribedWebhook
{
    use InteractsWithMailgunPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
