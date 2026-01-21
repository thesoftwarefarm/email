<?php

namespace TsfCorp\Email\Webhooks;

interface IncomingWebhook
{
    public function getRemoteIdentifier(): string;

    /**
     * @return \TsfCorp\Email\Webhooks\WebhookRecipient[]
     */
    public function getRecipients(): array;
    public function getMetadata(): array;
    public function getPayload(): array;
}
