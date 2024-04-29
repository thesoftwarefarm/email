<?php

namespace TsfCorp\Email\Webhooks;

interface FailedWebhook extends IncomingWebhook
{
    public function getReason(): ?string;
}
