<?php

namespace TsfCorp\Email\Models;

use Illuminate\Database\Eloquent\Model;

class EmailBounce extends Model
{
    protected $table = 'email_bounces';
    const UPDATED_AT = null;

    /**
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return parent::resolveConnection(config('email.database_connection'));
    }
}