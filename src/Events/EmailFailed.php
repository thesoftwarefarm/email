<?php

namespace TsfCorp\Email\Events;

use Throwable;
use TsfCorp\Email\Models\EmailModel;

class EmailFailed
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    public $email;
    public $exception;
    public $payload;
    public $reason;

    /**
     * EmailFailed constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param \Throwable|null $exception
     */
    public function __construct(EmailModel $email, Throwable $exception = null, $reason = null, $payload = null)
    {
        $this->email = $email;
        $this->exception = $exception;
        $this->payload = $payload;
        $this->reason = $reason;
    }
}