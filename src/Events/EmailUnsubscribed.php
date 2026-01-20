<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class EmailUnsubscribed
{
    public EmailModel $email;
    public EmailRecipient $recipient;
    public mixed $payload;

    public function __construct(EmailModel $email, EmailRecipient $recipient, mixed $payload = null)
    {
        $this->email = $email;
        $this->recipient = $recipient;
        $this->payload = $payload;
    }
}
