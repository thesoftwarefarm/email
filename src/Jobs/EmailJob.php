<?php

namespace TsfCorp\Email\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;
use TsfCorp\Email\Events\EmailSendingFailed;
use TsfCorp\Email\Events\EmailSendingSucceeded;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Transport;

class EmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $id;
    private ?EmailModel $email;

    public function __construct(int $id, ?string $database_connection = null)
    {
        $this->id = $id;
        $this->email = EmailModel::on($database_connection)->find($this->id);
    }

    public function handle()
    {
        if (!$this->email) {
            throw new Exception("Record with id [{$this->id}] not found.");
        }

        try {
            $transport = Transport::resolveFor($this->email);
        } catch (Throwable $t) {
            $this->email->status = EmailModel::STATUS_FAILED;
            $this->email->notes = $t->getMessage();
            $this->email->save();

            event(new EmailSendingFailed($this->email, $t));

            return;
        }

        $this->sendVia($transport);
    }

    public function sendVia(Transport $transport)
    {
        if (config('app.env') != 'production') {
            $this->email->status = EmailModel::STATUS_FAILED;
            $this->email->notes = 'Sending email is disabled in non production environment.';
            $this->email->save();

            event(new EmailSendingFailed($this->email));

            return;
        }

        if ($this->email->retries >= config('email.max_retries')) {
            $this->email->status = EmailModel::STATUS_FAILED;
            $this->email->notes = "Max retry limit reached. {$this->email->notes}";
            $this->email->save();

            event(new EmailSendingFailed($this->email));

            return;
        }

        try {
            $transport->send($this->email);

            $this->email->status = EmailModel::STATUS_SENT;
            $this->email->remote_identifier = $transport->getRemoteIdentifier();
            $this->email->notes = $transport->getMessage();
            $this->email->save();

            event(new EmailSendingSucceeded($this->email));
        } catch (Throwable $t) {
            $this->email->status = EmailModel::STATUS_FAILED;
            $this->email->notes = $t->getMessage();
            $this->email->save();

            event(new EmailSendingFailed($this->email, $t));

            $this->email->retry();
        }
    }
}
