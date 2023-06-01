<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;

class EmailSendingSucceeded
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    public $email;

    /**
     * EmailSent constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     */
    public function __construct(EmailModel $email)
    {
        $this->email = $email;
    }
}
