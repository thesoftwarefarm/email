<?php

namespace TsfCorp\Email\Events;

use Throwable;
use TsfCorp\Email\Models\EmailModel;

class EmailSendingFailed
{
    public EmailModel $email;
    public ?Throwable $exception;

    public function __construct(EmailModel $email, ?Throwable $exception = null)
    {
        $this->email = $email;
        $this->exception = $exception;
    }
}
