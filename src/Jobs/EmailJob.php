<?php

namespace TsfCorp\Email\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Events\EmailSent;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Transport;

class EmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var $id
     */
    private $id;
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    private $email;

    /**
     * @param $id
     */
    public function __construct($id, $database_connection = null)
    {
        $this->id = $id;
        $this->email = EmailModel::on($database_connection)->find($this->id);
    }

    /**
     *
     * @throws \Exception
     */
    public function handle()
    {
        if ( ! $this->email)
        {
            throw new Exception('Record with id [' . $this->id . '] not found.');
        }

        try
        {
            $transport = Transport::resolveFor($this->email);
        }
        catch (Throwable $t)
        {
            $this->email->status = 'failed';
            $this->email->notes = $t->getMessage();
            $this->email->save();

            event(new EmailFailed($this->email));

            return;
        }

        $this->sendVia($transport);
    }

    /**
     * @param \TsfCorp\Email\Transport $transport
     */
    public function sendVia(Transport $transport)
    {
        if (config('app.env') != 'production' && !config('email.force_send_emails'))
        {
            $this->email->status = 'failed';
            $this->email->notes = 'Sending email is disabled in non production environment.';
            $this->email->save();

            event(new EmailFailed($this->email));

            return;
        }

        if($this->email->retries >= config('email.max_retries'))
        {
            $this->email->status = 'failed';
            $this->email->notes = 'Max retry limit reached. '.$this->email->notes;
            $this->email->save();

            event(new EmailFailed($this->email));

            return;
        }

        try
        {
            $transport->send($this->email);

            $this->email->status = 'sent';
            $this->email->remote_identifier = $transport->getRemoteIdentifier();
            $this->email->notes = $transport->getMessage();
            $this->email->save();

            event(new EmailSent($this->email));
        }
        catch (Throwable $t)
        {
            $this->email->status = 'failed';
            $this->email->notes = $t->getMessage();
            $this->email->save();

            event(new EmailFailed($this->email, $t));

            $this->email->retry();
        }
    }
}
