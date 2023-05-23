<?php

namespace TsfCorp\Email\Http\Controllers;

use Illuminate\Http\Request;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Events\EmailClicked;
use TsfCorp\Email\Events\EmailComplained;
use TsfCorp\Email\Events\EmailDelivered;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Events\EmailOpened;
use TsfCorp\Email\Events\EmailUnsubscribed;

class MailgunWebhookController
{
    //Map Mailgun events to methods
    //Eg. 'delivered' => 'processDeliveredEvent' means that when Mailgun sends a 'delivered' event, the method 'processDeliveredEvent' will be called
    const EVENTS = [
        'delivered' => 'processDeliveredEvent',
        'failed' => 'processFailedEvent',
        'opened' => 'processOpenedEvent',
        'clicked' => 'processClickedEvent',
        'unsubscribed' => 'processUnsubscribedEvent',
        'complained' => 'processComplainedEvent',
    ];

    public function webhook(Request $request)
    {
        \Log::info($request->all());
        return;
        if (!$this->checkSignature(
            $request->input('signature.timestamp'),
            $request->input('signature.token'),
            $request->input('signature.signature')
        )) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        if (!array_key_exists($request->input('event-data.event'), self::EVENTS)) {
            return response()->json(['message' => 'Invalid event'], 400);
        }

        if (!$this->processEvent($request->input('event-data'))) {
            return response()->json(['message' => 'Error processing event'], 500);
        }

        return response()->json(['message' => 'OK']);
    }

    private function processEvent($event)
    {
        $email = $this->getEmailFromEvent($event);
        if (!$email) {
            return false;
        }

        if (isset($event['reason'])) {
            $email->notes = $event['reason'];
            $email->save();
        }

        if (isset($event['delivery-status']['description']) && $event['delivery-status']['description'] != '' && $event['delivery-status']['description'] != null) {
            $email->notes = $event['delivery-status']['description'];
            $email->save();
        }

        return $this->{self::EVENTS[$event['event']]}($event, $email);
    }

    private function processComplainedEvent($event, EmailModel $email)
    {
        if ($email->status == 'sent' || $email->status == 'soft_bounced') {
            $email->status = 'complained';
            $email->save();
        }

        event(new EmailComplained($email, payload: $event));

        return true;
    }

    private function processUnsubscribedEvent($event, EmailModel $email)
    {
        event(new EmailUnsubscribed($email, payload: $event));

        return true;
    }

    private function processClickedEvent($event, EmailModel $email)
    {
        event(new EmailClicked($email, payload: $event));

        return true;
    }

    private function processOpenedEvent($event, EmailModel $email)
    {
        event(new EmailOpened($email, payload: $event));

        return true;
    }


    private function processDeliveredEvent($event, EmailModel $email)
    {
        if ($email->status == 'sent' || $email->status == 'soft_bounced') {
            $email->status = 'delivered';
            $email->save();
        }

        event(new EmailDelivered($email, payload: $event));

        return true;
    }

    private function processFailedEvent($event, EmailModel $email)
    {
        if ($email->status == 'sent' || $email->status == 'soft_bounced' || $email->status == 'failed') {
            $email->status == 'failed';
            if ($event['severity'] == 'temporary') {
                $email->status = 'soft_bounced';
            }

            if ($event['severity'] == 'permanent') {
                $email->status = 'hard_bounced';
            }

            $email->save();
        }

        event(new EmailFailed($email, reason: $event['reason'], payload: $event));

        return true;
    }

    private function getEmailFromEvent($event)
    {
        $email = EmailModel::getByRemoteIdentifier('<' . $event['message']['headers']['message-id'] . '>');
        if (!$email) {
            return false;
        }

        return $email;
    }

    private function checkSignature($timestamp, $token, $signature)
    {
        $hash = hash_hmac('sha256', $timestamp . $token, config('email.providers.mailgun.webhook_secret'));

        return $hash === $signature;
    }
}
