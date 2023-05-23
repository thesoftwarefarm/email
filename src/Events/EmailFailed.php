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
    private $eventPayload;
    private $reason;

    /**
     * EmailFailed constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param \Throwable|null $exception
     */
    public function __construct(EmailModel $email, Throwable $exception = null, $reason = null, $eventPayload = null)
    {
        $this->email = $email;
        $this->exception = $exception;
        $this->eventPayload = $eventPayload;
        $this->reason = $reason;
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

    public function getReason()
    {
        return $this->reason;
    }

    public function getEventPayload()
    {
        return $this->eventPayload;
    }
}