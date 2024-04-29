<?php

namespace TsfCorp\Email\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TsfCorp\Email\Events\EmailClicked;
use TsfCorp\Email\Events\EmailComplained;
use TsfCorp\Email\Events\EmailDelivered;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Events\EmailOpened;
use TsfCorp\Email\Events\EmailUnsubscribed;
use TsfCorp\Email\Jobs\EmailJob;
use TsfCorp\Email\Webhooks\ClickedWebhook;
use TsfCorp\Email\Webhooks\ComplainedWebhook;
use TsfCorp\Email\Webhooks\DeliveredWebhook;
use TsfCorp\Email\Webhooks\BouncedWebhook;
use TsfCorp\Email\Webhooks\IncomingWebhook;
use TsfCorp\Email\Webhooks\OpenedWebhook;
use TsfCorp\Email\Webhooks\UnsubscribedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class EmailModel extends Model
{
    protected $table = 'emails';

    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recipients()
    {
        return $this->hasMany(EmailRecipient::class, 'email_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function to()
    {
        return $this->recipients()->where('type', EmailRecipient::TYPE_TO);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cc()
    {
        return $this->recipients()->where('type', EmailRecipient::TYPE_CC);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bcc()
    {
        return $this->recipients()->where('type', EmailRecipient::TYPE_BCC);
    }

    /**
     * @param $email
     * @return \TsfCorp\Email\Models\EmailRecipient|null
     */
    public function getRecipientByEmail($email)
    {
        return $this->recipients()->where('email', $email)->first();
    }

    /**
     * @param $identifier
     * @return \TsfCorp\Email\Models\EmailModel|null
     */
    public static function getByRemoteIdentifier($identifier)
    {
        return self::where('remote_identifier', $identifier)->first();
    }

    /**
     * Dispatches a job for current record
     * @param \Carbon\Carbon|null $delay
     */
    public function dispatchJob(Carbon $delay = null)
    {
        $this->status = EmailModel::STATUS_QUEUED;
        $this->save();

        $job = new EmailJob($this->id, $this->getConnectionName());

        if ($delay) {
            $job->delay($delay);
        }

        dispatch($job);
    }

    /**
     * Reschedule an email
     */
    public function retry()
    {
        $this->retries++;
        $this->save();

        $delay = Carbon::now()->addMinutes(5);

        $this->dispatchJob($delay);
    }

    /**
     * Force re-send an email
     */
    public function resend()
    {
        $this->status = self::STATUS_PENDING;
        $this->retries = 0;
        $this->remote_identifier = null;
        $this->notes = null;
        $this->save();

        $this->dispatchJob();
    }

    /**
     * @param \TsfCorp\Email\Webhooks\IncomingWebhook $webhook
     * @return void
     */
    public function processIncomingWebhook(IncomingWebhook $webhook)
    {
        foreach($webhook->getRecipients() as $webhook_recipient) {
            $recipient = $this->getRecipientByEmail($webhook_recipient->getEmail());

            if(!$recipient) {
                continue;
            }

            match (true) {
                is_a($webhook, DeliveredWebhook::class) => $this->processDeliveredWebhook($recipient, $webhook_recipient, $webhook),
                is_a($webhook, BouncedWebhook::class) => $this->processBouncedWebhook($recipient, $webhook_recipient, $webhook),
                is_a($webhook, OpenedWebhook::class) => $this->processOpenedWebhook($recipient, $webhook_recipient, $webhook),
                is_a($webhook, ClickedWebhook::class) => $this->processClickedWebhook($recipient, $webhook_recipient, $webhook),
                is_a($webhook, UnsubscribedWebhook::class) => $this->processUnsubscribedWebhook($recipient, $webhook_recipient, $webhook),
                is_a($webhook, ComplainedWebhook::class) => $this->processComplainedWebhook($recipient, $webhook_recipient, $webhook),
            };
        }
    }

    /**
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param \TsfCorp\Email\Webhooks\WebhookRecipient $webhook_recipient
     * @param \TsfCorp\Email\Webhooks\DeliveredWebhook $webhook
     * @return void
     */
    private function processDeliveredWebhook(EmailRecipient $recipient, WebhookRecipient $webhook_recipient, DeliveredWebhook $webhook)
    {
        $recipient->status = EmailRecipient::STATUS_DELIVERED;
        $recipient->save();

        event(new EmailDelivered($this, $recipient, $webhook->getPayload()));
    }

    /**
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param \TsfCorp\Email\Webhooks\WebhookRecipient $webhook_recipient
     * @param \TsfCorp\Email\Webhooks\BouncedWebhook $webhook
     * @return void
     */
    private function processBouncedWebhook(EmailRecipient $recipient, WebhookRecipient $webhook_recipient, BouncedWebhook $webhook)
    {
        $recipient->status = EmailRecipient::STATUS_FAILED;
        $recipient->notes = $webhook_recipient->getMessage();
        $recipient->save();

        event(new EmailFailed($this, $recipient, $recipient->notes, $webhook->getPayload()));
    }

    /**
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param \TsfCorp\Email\Webhooks\WebhookRecipient $webhook_recipient
     * @param \TsfCorp\Email\Webhooks\OpenedWebhook $webhook
     * @return void
     */
    private function processOpenedWebhook(EmailRecipient $recipient, WebhookRecipient $webhook_recipient, OpenedWebhook $webhook)
    {
         event(new EmailOpened($this, $recipient, $webhook->getPayload()));
    }

    /**
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param \TsfCorp\Email\Webhooks\WebhookRecipient $webhook_recipient
     * @param \TsfCorp\Email\Webhooks\ClickedWebhook $webhook
     * @return void
     */
    private function processClickedWebhook(EmailRecipient $recipient, WebhookRecipient $webhook_recipient, ClickedWebhook $webhook)
    {
         event(new EmailClicked($this, $recipient, $webhook->getPayload()));
    }

    /**
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param \TsfCorp\Email\Webhooks\WebhookRecipient $webhook_recipient
     * @param \TsfCorp\Email\Webhooks\UnsubscribedWebhook $webhook
     * @return void
     */
    private function processUnsubscribedWebhook(EmailRecipient $recipient, WebhookRecipient $webhook_recipient, UnsubscribedWebhook $webhook)
    {
         event(new EmailUnsubscribed($this, $recipient, $webhook->getPayload()));
    }

    /**
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param \TsfCorp\Email\Webhooks\WebhookRecipient $webhook_recipient
     * @param \TsfCorp\Email\Webhooks\ComplainedWebhook $webhook
     * @return void
     */
    private function processComplainedWebhook(EmailRecipient $recipient, WebhookRecipient $webhook_recipient, ComplainedWebhook $webhook)
    {
        event(new EmailComplained($this, $recipient, $webhook->getPayload()));
    }
}
