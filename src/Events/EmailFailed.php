<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class EmailFailed
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
     * @var string|null
     */
    private $reason;
    /**
     * @var mixed
     */
    public $payload;

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @param \TsfCorp\Email\Models\EmailRecipient $recipient
     * @param string|null $reason
     * @param null $payload
     */
    public function __construct(EmailModel $email, EmailRecipient $recipient, ?string $reason = null, mixed $payload = null)
    {
        $this->email = $email;
        $this->recipient = $recipient;
        $this->reason = $reason;
        $this->payload = $payload;
    }
}
