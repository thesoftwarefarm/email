<?php

namespace TsfCorp\Email;

use TsfCorp\Email\Models\EmailModel;

class DefaultWebhookEmailModelResolver implements WebhookEmailModelResolverInterface
{
    /**
     * @param \TsfCorp\Email\IncomingWebhookPayload $incoming_webhook
     * @return \TsfCorp\Email\Models\EmailModel|null
     */
    public static function resolve(IncomingWebhookPayload $incoming_webhook)
    {
        return EmailModel::getByRemoteIdentifier($incoming_webhook->getRemoteIdentifier());
    }
}
