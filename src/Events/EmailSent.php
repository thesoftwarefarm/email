<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;

class EmailSent
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    private $email;

    /**
     * EmailFailed constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     */
    public function __construct(EmailModel $email)
    {
        $this->email = $email;
    }

    /**
     * @return \TsfCorp\Email\Models\EmailModel
     */
    public function getEmail()
    {
        return $this->email;
    }
}