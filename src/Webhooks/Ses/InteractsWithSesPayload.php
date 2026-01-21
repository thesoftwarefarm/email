<?php

namespace TsfCorp\Email\Webhooks\Ses;

trait InteractsWithSesPayload
{
    public function getRemoteIdentifier(): string
    {
        return data_get($this->getPayload(), 'mail.messageId');
    }

    public function getMetadata(): array
    {
        $tags = (array)data_get($this->getPayload(), 'mail.tags', []);

        // ses returns the value of a tag as an array
        return array_map(fn($values) => $values[0] ?? '' , $tags);
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
