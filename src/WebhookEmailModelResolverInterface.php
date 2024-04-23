<?php

namespace TsfCorp\Email;

interface WebhookEmailModelResolverInterface
{
    /**
     * @param \TsfCorp\Email\IncomingWebhookPayload $incoming_webhook
     * @return \TsfCorp\Email\Models\EmailModel|null
     */
    public static function resolve(IncomingWebhookPayload $incoming_webhook);
}
