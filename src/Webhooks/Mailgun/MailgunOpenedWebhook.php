<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\OpenedWebhook;

class MailgunOpenedWebhook implements OpenedWebhook
{
    use InteractsWithMailgunPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
