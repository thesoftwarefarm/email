<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

use TsfCorp\Email\Webhooks\ComplainedWebhook;

class MailgunComplainedWebhook implements ComplainedWebhook
{
    use InteractsWithMailgunPayload;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
