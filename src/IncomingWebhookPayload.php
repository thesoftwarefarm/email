<?php

namespace TsfCorp\Email;

class IncomingWebhookPayload
{
    /**
     * @var string
     */
    private $provider;
    /**
     * @var string
     */
    private $remote_identifier;
    /**
     * @var array
     */
    private $metadata;

    public function __construct($provider, $remote_identifier, $metadata)
    {
        $this->provider = $provider;
        $this->remote_identifier = $remote_identifier;
        $this->metadata = $metadata;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getRemoteIdentifier(): string
    {
        return $this->remote_identifier;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
