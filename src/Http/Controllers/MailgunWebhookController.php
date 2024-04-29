<?php

namespace TsfCorp\Email\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use TsfCorp\Email\DefaultWebhookEmailModelResolver;
use TsfCorp\Email\Webhooks\Mailgun\MailgunWebhookFactory;

class MailgunWebhookController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $rules = [
            'event-data.message.headers.message-id' => 'required',
            'signature.signature' => 'required',
            'signature.timestamp' => 'required',
            'signature.token' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response('Validation error', 422);
        }

        if (!$this->checkSignature($request)) {
            return response('Invalid signature', 403);
        }

        /** @var \TsfCorp\Email\WebhookEmailModelResolverInterface $resolver */
        $resolver = config('email.webhook_email_model_resolver', DefaultWebhookEmailModelResolver::class);

        $webhook = MailgunWebhookFactory::make($request->input());

        $email = $resolver::resolve($webhook);

        if (!$email) {
            return response('Email not found.', 406);
        }

        $email->processIncomingWebhook($webhook);

        return response('Ok');
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function checkSignature(Request $request)
    {
        $signature = hash_hmac('SHA256', $request->input('signature.timestamp') . $request->input('signature.token'), config('email.providers.mailgun.webhook_secret'));

        return $signature === $request->input('signature.signature');
    }
}
