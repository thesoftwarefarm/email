<?php

namespace TsfCorp\Email\Events;

use Throwable;
use TsfCorp\Email\Models\EmailModel;

class EmailSendingFailed
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    public $email;
    /**
     * @var \Throwable|null
     */
    public $exception;

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param \Throwable|null $exception
     */
    public function __construct(EmailModel $email, ?Throwable $exception = null)
    {
        $this->email = $email;
        $this->exception = $exception;
    }
}
