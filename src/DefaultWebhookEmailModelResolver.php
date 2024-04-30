<?php

namespace TsfCorp\Email;

use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Webhooks\IncomingWebhook;

class DefaultWebhookEmailModelResolver implements WebhookEmailModelResolverInterface
{
    public static function resolve(IncomingWebhook $webhook): ?EmailModel
    {
        return EmailModel::getByRemoteIdentifier($webhook->getRemoteIdentifier());
    }
}
