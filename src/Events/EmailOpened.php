<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class EmailOpened
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    public $email;
    /**
     * @var \TsfCorp\Email\Models\EmailRecipient
     */
    public $recipient;
    /**
     * @var mixed
     */
    public $payload;

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param null $payload
     */
    public function __construct(EmailModel $email, EmailRecipient $recipient, mixed $payload = null)
    {
        $this->email = $email;
        $this->recipient = $recipient;
        $this->payload = $payload;
    }
}
