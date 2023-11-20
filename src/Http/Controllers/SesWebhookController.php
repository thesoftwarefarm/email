<?php

namespace TsfCorp\Email\Http\Controllers;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TsfCorp\Email\Events\EmailComplained;
use TsfCorp\Email\Events\EmailDelivered;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Models\EmailRecipient;

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
        $message = json_decode($payload['Message'], true);

        $email = EmailModel::getByRemoteIdentifier($message['mail']['messageId']);

        if (!$email) {
            return response('Email not found.', 404);
        }

        match ($message['notificationType']) {
            'Delivery' => $this->processDeliveredEvent($email, $message['delivery']),
            'Bounce' => $this->processFailedEvent($email, $message['bounce']),
            'Complaint' => $this->processComplainedEvent($email, $message['complaint']),
            default => null,
        };

        return response('Ok', 200);
    }

    /**
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#delivery-object
     *
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param $event
     * @return void
     */
    private function processDeliveredEvent(EmailModel $email, $event)
    {
        foreach ($event['recipients'] as $email_address) {
            $recipient = $email->getRecipientByEmail($email_address);

            if (!$recipient) {
                continue;
            }

            $recipient->status = EmailRecipient::STATUS_DELIVERED;
            $recipient->save();

            event(new EmailDelivered($email, $recipient, $event));
        }
    }

    /**
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#bounce-object
     *
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param $event
     * @return void
     */
    private function processFailedEvent(EmailModel $email, $event)
    {
        foreach ($event['bouncedRecipients'] as $bounced_recipient) {
            $recipient = $email->getRecipientByEmail($bounced_recipient['emailAddress']);

            if (!$recipient) {
                continue;
            }

            $recipient->status = EmailRecipient::STATUS_FAILED;
            $recipient->notes = $bounced_recipient['diagnosticCode'] ?? null;
            $recipient->save();

            event(new EmailFailed($email, $recipient, $recipient->notes, $event));
        }
    }

    /**
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#complaint-object
     *
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param $event
     * @return void
     */
    private function processComplainedEvent(EmailModel $email, $event)
    {
        foreach ($event['complainedRecipients'] as $complained_recipient) {
            $recipient = $email->getRecipientByEmail($complained_recipient['emailAddress']);

            if (!$recipient) {
                continue;
            }

            event(new EmailComplained($email, $recipient, $event));
        }
    }
}
