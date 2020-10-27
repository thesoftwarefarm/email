<?php

namespace TsfCorp\Email\Models;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TsfCorp\Email\Jobs\EmailJob;

/**
 * @property string project
 * @property string provider
 * @property array from
 * @property array to
 * @property array cc
 * @property array bcc
 * @property string subject
 * @property string body
 * @property string status
 * @property string attachments
 */

class EmailModel extends Model
{
    protected $table = 'emails';

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return parent::resolveConnection(config('email.database_connection'));
    }

    /**
     * @return HasMany
     */
    public function bounces()
    {
        return $this->hasMany(EmailBounce::class, 'email_id');
    }

    /**
     * @param $identifier
     * @return EmailModel|null
     */
    public static function getByRemoteIdentifier($identifier)
    {
        if ( empty($identifier))
            return null;

        return self::where('remote_identifier', $identifier)->first();
    }

    /**
     * Dispatches a job for current record
     * @param Carbon|null $delay
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
        $from = $this->decodeRecipient($this->from);

        if (empty($from))
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
     * @return array|mixed
     */
    public function decodeRecipient($recipients)
    {
        $decoded = json_decode($recipients);

        if(json_last_error() !== JSON_ERROR_NONE)
        {
            $decoded = [];
        }

        return $decoded;
    }

    /**
     * @param $recipients
     * @return array
     */
    private function prepareRecipient($recipients)
    {
        $recipients = array_filter($this->decodeRecipient($recipients), function ($recipient) {
             return !empty($recipient->email) && filter_var($recipient->email, FILTER_VALIDATE_EMAIL);
        });

        if ( ! count($recipients))
            return [];

        return array_map(function ($recipient) {
            return sprintf('%s <%s>', $recipient->name, $recipient->email);
        }, $recipients);
    }

    /**
     * @return array
     */
    public function prepareAttachments()
    {
        foreach (json_decode($this->attachments, true) as $attachment_path) {
            $path_array = explode('/', $attachment_path);
            $filename = $path_array[count($path_array) - 1];
            $prepared_attachment = [
                'filePath' => $attachment_path,
                'filename' => $filename
            ];

            $prepared_attachments[] = $prepared_attachment;
        }

        if (empty($prepared_attachments)) {
            return null;
        }

        return $prepared_attachments;
    }
}
