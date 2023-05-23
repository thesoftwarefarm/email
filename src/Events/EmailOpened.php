<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;

class EmailOpened
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    private $email;
    private $eventPayload;

    /**
     * EmailOpened constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     */
    public function __construct(EmailModel $email, $eventPayload = null)
    {
        $this->email = $email;
        $this->eventPayload = $eventPayload;
    }

    /**
     * @return \TsfCorp\Email\Models\EmailModel
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function getEventPayload()
    {
        return $this->eventPayload;
    }
}