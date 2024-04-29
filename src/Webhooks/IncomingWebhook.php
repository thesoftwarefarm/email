<?php

namespace TsfCorp\Email\Webhooks;

interface IncomingWebhook
{
    public function getRemoteIdentifier(): string;
    public function getRecipients(): array;
    public function getMetadata(): array;
    public function getPayload(): array;
}
