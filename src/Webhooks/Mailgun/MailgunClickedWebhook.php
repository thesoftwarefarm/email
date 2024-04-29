<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\ClickedWebhook;

class MailgunClickedWebhook implements ClickedWebhook
{
    use InteractsWithMailgunPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
