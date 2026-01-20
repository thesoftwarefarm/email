<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;

class EmailSendingSucceeded
{
    public EmailModel $email;

    public function __construct(EmailModel $email)
    {
        $this->email = $email;
    }
}
