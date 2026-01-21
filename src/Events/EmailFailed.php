<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class EmailFailed
{
    public EmailModel $email;
    public EmailRecipient $recipient;
    public ?string $reason;
    public mixed $payload;

    public function __construct(EmailModel $email, EmailRecipient $recipient, ?string $reason = null, mixed $payload = null)
    {
        $this->email = $email;
        $this->recipient = $recipient;
        $this->reason = $reason;
        $this->payload = $payload;
    }
}
