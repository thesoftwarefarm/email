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
    const STATUS_DELIVERED = 'delivered';

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
