<?php

namespace TsfCorp\Email\Webhooks\Mailgun;

trait InteractsWithMailgunPayload
{
    public function getRemoteIdentifier(): string
    {
        $remote_identifier = data_get($this->getPayload(), 'event-data.message.headers.message-id');

        return "<{$remote_identifier}>";
    }

    public function getRecipients(): array
    {
        return [
            data_get($this->getPayload(), 'event-data.recipient'),
        ];
    }

    public function getMetadata(): array
    {
        return (array) data_get($this->getPayload(), 'event-data.user-variables');
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
