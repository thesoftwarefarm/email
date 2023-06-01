<?php

namespace TsfCorp\Email\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TsfCorp\Email\Jobs\EmailJob;

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

        if ($delay)
        {
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
}
