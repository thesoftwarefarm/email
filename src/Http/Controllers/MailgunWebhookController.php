<?php

namespace TsfCorp\Email\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Events\EmailClicked;
use TsfCorp\Email\Events\EmailComplained;
use TsfCorp\Email\Events\EmailDelivered;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Events\EmailOpened;
use TsfCorp\Email\Events\EmailUnsubscribed;
use TsfCorp\Email\Models\EmailRecipient;

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

        $email = EmailModel::getByRemoteIdentifier("<{$request->input('event-data.message.headers.message-id')}>");

		if (!$email) {
			return response('Email not found.', 406);
        }

        $event = $request->input('event-data');

        $recipient = $email->getRecipientByEmail($event['recipient']);

        if (!$recipient) {
			return response('Email recipient not found.', 406);
        }

        match ($event['event']) {
            'delivered' => $this->processDeliveredEvent($email, $recipient, $event),
            'failed' => $this->processFailedEvent($email, $recipient, $event),
            'opened' => $this->processOpenedEvent($email, $recipient, $event),
            'clicked' => $this->processClickedEvent($email, $recipient, $event),
            'unsubscribed' => $this->processUnsubscribedEvent($email, $recipient, $event),
            'complained' => $this->processComplainedEvent($email, $recipient, $event),
            default => null,
        };

        return response('Ok');
    }

    /**
     * @param EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param $event
     * @return void
     */
    private function processDeliveredEvent(EmailModel $email, EmailRecipient $recipient, $event)
    {
        $recipient->status = EmailRecipient::STATUS_DELIVERED;
        $recipient->save();

        event(new EmailDelivered($email, $recipient, $event));
    }

    /**
     * @param EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param $event
     * @return void
     */
    private function processFailedEvent(EmailModel $email, EmailRecipient $recipient, $event)
    {
        $recipient->status = EmailRecipient::STATUS_FAILED;
        $recipient->notes = $event['delivery-status']['message'];
        $recipient->save();

        event(new EmailFailed($email, $recipient, $event));
    }

    /**
     * @param EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param $event
     * @return void
     */
    private function processComplainedEvent(EmailModel $email, EmailRecipient $recipient, $event)
    {
        event(new EmailComplained($email, $recipient, $event));
    }

    /**
     * @param EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param $event
     * @return void
     */
    private function processUnsubscribedEvent(EmailModel $email, EmailRecipient $recipient, $event)
    {
        event(new EmailUnsubscribed($email, $recipient, $event));
    }

    /**
     * @param EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param $event
     * @return void
     */
    private function processClickedEvent(EmailModel $email, EmailRecipient $recipient, $event)
    {
        event(new EmailClicked($email, $recipient, $event));
    }

    /**
     * @param EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param $event
     * @return void
     */
    private function processOpenedEvent(EmailModel $email, EmailRecipient $recipient, $event)
    {
        event(new EmailOpened($email, $recipient, $event));
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function checkSignature(Request $request)
	{
		$signature = hash_hmac('SHA256', $request->input('signature.timestamp').$request->input('signature.token'), config('email.providers.mailgun.webhook_secret'));

		return $signature === $request->input('signature.signature');
	}
}
