<?php

namespace TsfCorp\Email;

use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Webhooks\IncomingWebhook;

interface WebhookEmailModelResolverInterface
{
    public static function resolve(IncomingWebhook $webhook): ?EmailModel;
}
