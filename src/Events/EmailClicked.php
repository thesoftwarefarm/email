<?php

namespace TsfCorp\Email\Events;

use TsfCorp\Email\Models\EmailModel;

class EmailClicked
{
    /**
     * @var \TsfCorp\Email\Models\EmailModel
     */
    private $email;
    private $payload;

    /**
     * EmailClicked constructor.
     * @param \TsfCorp\Email\Models\EmailModel $email
     */
    public function __construct(EmailModel $email, $payload = null)
    {
        $this->email = $email;
        $this->payload = $payload;
    }

    /**
     * @return \TsfCorp\Email\Models\EmailModel
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function getPayload()
    {
        return $this->payload;
    }
}