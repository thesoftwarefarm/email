<?php

namespace TsfCorp\Email\Webhooks;

class WebhookRecipient
{
    public const DELIVERED = 'delivered';
    public const BOUNCED = 'bounced';
    public const OPENED = 'opened';
    public const CLICKED = 'clicked';
    public const UNSUBSCRIBED = 'unsubscribed';
    public const COMPLAINED = 'complained';

    private string $email;
    private string $status;
    private ?string $message;

    public function __construct(string $email, string $status, ?string $message = null)
    {
        $this->email = $email;
        $this->status = $status;
        $this->message = $message;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public static function makeForDelivered(string $email): static
    {
        return new static($email, self::DELIVERED);
    }

    public static function makeForBounced(string $email, ?string $message = null): static
    {
        return new static($email, self::BOUNCED, $message);
    }

    public static function makeForOpened(string $email): static
    {
        return new static($email, self::OPENED);
    }

    public static function makeForClicked(string $email): static
    {
        return new static($email, self::CLICKED);
    }

    public static function makeForUnsubscribed(string $email): static
    {
        return new static($email, self::UNSUBSCRIBED);
    }

    public static function makeForComplained(string $email): static
    {
        return new static($email, self::COMPLAINED);
    }
}
