<?php

namespace TsfCorp\Email\Events;

use Throwable;
use TsfCorp\Email\Models\EmailModel;

class EmailFailed
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    private $email;
    private $exception;

    /**
     * EmailFailed constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param \Throwable|null $exception
     */
    public function __construct(EmailModel $email, Throwable $exception = null)
    {
        $this->email = $email;
        $this->exception = $exception;
    }

    /**
     * @return \TsfCorp\Email\Models\EmailModel
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return \Throwable|null
     */
    public function getException()
    {
        return $this->exception;
    }
}