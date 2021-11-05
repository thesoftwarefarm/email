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
     * Force re-send an email
     */
    public function resend()
    {
        $this->status = 'pending';
        $this->retries = 0;
        $this->remote_identifier = null;
        $this->notes = null;
        $this->save();

        $this->dispatchJob();
    }
}
