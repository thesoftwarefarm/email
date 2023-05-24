<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;

class EmailUnsubscribed
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    public $email;
    public $payload;

    /**
     * EmailUnsubscribed constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     */
    public function __construct(EmailModel $email, $payload = null)
    {
        $this->email = $email;
        $this->payload = $payload;
    }
}