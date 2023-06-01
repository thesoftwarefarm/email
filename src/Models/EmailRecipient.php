<?php

namespace TsfCorp\Email\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Mime\Address;

class EmailRecipient extends Model
{
    protected $table = 'email_recipients';

    public const TYPE_TO = 'to';
    public const TYPE_CC = 'cc';
    public const TYPE_BCC = 'bcc';

    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function email()
    {
        return $this->belongsTo(EmailModel::class, 'email_id');
    }

    /**
     * @return \Symfony\Component\Mime\Address
     */
    public function asMimeAddress()
    {
        return new Address($this->email, $this->name ?? '');
    }
}
