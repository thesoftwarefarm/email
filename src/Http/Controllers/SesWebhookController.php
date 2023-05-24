<?php

namespace TsfCorp\Email\Http\Controllers;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Events\EmailFailed;

class SesWebhookController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Aws\Sns\MessageValidator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, MessageValidator $validator)
    {
        $payload = json_decode($request->getContent(), true);

        if (empty($payload)) {
            return response()->json('No payload supplied.', 403);
        }

        $message = new Message($payload);

        if (!$validator->isValid($message)) {
            return response()->json('Invalid Signature.', 403);
        }

        $type = !empty($payload['Type']) ? $payload['Type'] : null;

        if ($type == 'SubscriptionConfirmation') {
            return $this->parseSubscriptionConfirmation($payload);
        }

        if ($type == 'Notification') {
            return $this->parseNotification($payload);
        }

        return response()->json('Invalid notification type.', 403);
    }

    /**
     * @param $payload
     * @return \Illuminate\Http\JsonResponse
     */
    private function parseSubscriptionConfirmation($payload)
    {
        Log::info("SubscribeURL: " . $payload['SubscribeURL']);

        return response()->json('Confirmation link received.', 200);
    }

    /**
     * @param $payload
     * @return \Illuminate\Http\JsonResponse
     */
    private function parseNotification($payload)
    {
        $message = json_decode($payload['Message']);

        if ($message->notificationType == 'Bounce' && $message->bounce->bounceType == 'Permanent') {
            $email = EmailModel::getByRemoteIdentifier($message->mail->messageId);

            if (!$email)
                return response()->json('Record not found.', 404);

            $email->status = EmailModel::STATUS_FAILED;
            $email->save();

            event(new EmailFailed($email, payload: $message));
        }

        return response()->json('Thank you.', 200);
    }
}
