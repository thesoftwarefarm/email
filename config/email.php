<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project Name
    |--------------------------------------------------------------------------
    |
    | Since this package can be installed within multiple projects which
    | push email to the same database, the project name is required to
    | differentiate between them.
    |
    | Example: "my_project"
    |
    */

    'project' => '',

    /*
    |--------------------------------------------------------------------------
    | Default provider
    |--------------------------------------------------------------------------
    |
    | Configure the default provider to be used to send email.
    |
    | Available options: 'mailgun'
    |
    */

    'default_provider' => 'mailgun',

    /*
    |--------------------------------------------------------------------------
    | Providers credentials
    |--------------------------------------------------------------------------
    |
    | Credentials for email providers.
    |
    */

    'providers' => [
        'mailgun' => [
            'api_key' => env('MAILGUN_API_KEY'),
            'domain' => env('MAILGUN_DOMAIN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This value is used to determine which database connection to use. Use a
    | valid connection which is defined in config/database.php
    |
    */

    'database_connection' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | Specify a name and address that is used globally for
    | all e-mails that are sent
    |
    */

    'from' => [
        'address' => '',
        'name' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max retries
    |--------------------------------------------------------------------------
    |
    | Configure here the max number of attempts in case of a failure when
    | sending an email.
    |
    */

    'max_retries' => 10,
];