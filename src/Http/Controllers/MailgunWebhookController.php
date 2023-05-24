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
    public function index(Request $request)
    {
        if (!$this->checkSignature(
            $request->input('signature.timestamp'),
            $request->input('signature.token'),
            $request->input('signature.signature')
        )) {
            return response()->json(['message' => 'Invalid signature'], 403);
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

        return match($event['event']){
            'delivered' => $this->processDeliveredEvent($event, $email),
            'failed' => $this->processFailedEvent($event, $email),
            'opened' => $this->processOpenedEvent($event, $email),
            'clicked' => $this->processClickedEvent($event, $email),
            'unsubscribed' => $this->processUnsubscribedEvent($event, $email),
            'complained' => $this->processComplainedEvent($event, $email),
            default => null
        };
    }

    private function processComplainedEvent($event, EmailModel $email)
    {
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
        if ($email->status == EmailModel::STATUS_SENT || $email->status == EmailModel::STATUS_FAILED) {
            $email->status = EmailModel::STATUS_DELIVERED;
            $email->save();
        }

        event(new EmailDelivered($email, payload: $event));

        return true;
    }

    private function processFailedEvent($event, EmailModel $email)
    {
        if ($email->status == EmailModel::STATUS_SENT || $email->status == EmailModel::STATUS_FAILED) {
            $email->status == EmailModel::STATUS_FAILED;

            if (isset($event['reason'])) {
                $email->notes = $event['reason'];
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
