<?php

namespace TsfCorp\Email\Webhooks;

interface WebhookFactory
{
    public static function make(array $payload): IncomingWebhook;
}
