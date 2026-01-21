<?php

namespace TsfCorp\Email\Webhooks;

class UnknownWebhook implements IncomingWebhook
{
    public function getRemoteIdentifier(): string
    {
        return '';
    }

    public function getRecipients(): array
    {
        return [];
    }

    public function getMetadata(): array
    {
        return [];
    }

    public function getPayload(): array
    {
        return [];
    }
}
