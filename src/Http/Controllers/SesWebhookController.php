<?php

namespace TsfCorp\Email\Http\Controllers;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TsfCorp\Email\DefaultWebhookEmailModelResolver;
use TsfCorp\Email\Webhooks\Ses\SesWebhookFactory;

class SesWebhookController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Aws\Sns\MessageValidator $validator
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, MessageValidator $validator)
    {
        $payload = json_decode($request->getContent(), true);

        if (empty($payload)) {
            return response('No payload supplied.', 403);
        }

        if (!$validator->isValid(new Message($payload))) {
            return response('Invalid Signature.', 403);
        }

        return match ($payload['Type']) {
            'SubscriptionConfirmation' => $this->parseSubscriptionConfirmation($payload),
            'Notification' => $this->parseNotification($payload),
            default => response('Invalid notification type.', 403),
        };
    }

    /**
     * @param $payload
     * @return \Illuminate\Http\Response
     */
    private function parseSubscriptionConfirmation($payload)
    {
        Log::info("SubscribeURL: " . $payload['SubscribeURL']);

        return response('Confirmation link received.', 200);
    }

    /**
     * @param $payload
     * @return \Illuminate\Http\Response
     */
    private function parseNotification($payload)
    {
         /** @var \TsfCorp\Email\WebhookEmailModelResolverInterface $resolver */
        $resolver = config('email.webhook_email_model_resolver', DefaultWebhookEmailModelResolver::class);

        $webhook = SesWebhookFactory::make(json_decode($payload['Message'], true));

        $email = $resolver::resolve($webhook);

        if (!$email) {
            return response('Email not found.', 404);
        }

        $email->processIncomingWebhook($webhook);

        return response('Ok', 200);
    }
}
