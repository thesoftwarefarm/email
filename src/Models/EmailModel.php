<?php

namespace TsfCorp\Email\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TsfCorp\Email\Jobs\EmailJob;

class EmailModel extends Model
{
    protected $table = 'emails';

    /**
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return parent::resolveConnection(config('email.database_connection'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bounces()
    {
        return $this->hasMany(EmailBounce::class, 'email_id');
    }

    /**
     * @param $identifier
     * @return \TsfCorp\Email\Models\EmailModel|null
     */
    public static function getByRemoteIdentifier($identifier)
    {
        if ( empty($identifier))
            return null;

        return self::where('remote_identifier', $identifier)->first();
    }

    /**
     * Dispatches a job for current record
     * @param \Carbon\Carbon|null $delay
     */
    public function dispatchJob(Carbon $delay = null)
    {
        $this->status = 'queued';
        $this->save();

        $job = new EmailJob($this->id);

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
     * @return string
     */
    public function prepareFromAddress()
    {
        $from = json_decode($this->from);

        if(json_last_error() || empty($from) || ! is_object($from))
            return '';

        return sprintf('%s <%s>', $from->name, $from->email);
    }

    /**
     * @return array
     */
    public function prepareToAddress()
    {
        return $this->prepareRecipient($this->to);
    }

    /**
     * @return array
     */
    public function prepareCcAddress()
    {
        return $this->prepareRecipient($this->cc);
    }

    /**
     * @return array
     */
    public function prepareBccAddress()
    {
        return $this->prepareRecipient($this->bcc);
    }

    /**
     * @param $recipients
     * @return array
     */
    private function prepareRecipient($recipients)
    {
        $recipients = json_decode($recipients);

        if(json_last_error() || ! is_array($recipients) || ! count($recipients))
            return [];

        $recipients = array_filter($recipients, function ($recipient) {
             return !empty($recipient->email) && filter_var($recipient->email, FILTER_VALIDATE_EMAIL);
        });

        if ( ! count($recipients))
            return [];

        return array_map(function ($recipient) {
            return sprintf('%s <%s>', $recipient->name, $recipient->email);
        }, $recipients);
    }
}