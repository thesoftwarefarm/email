<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your project. This value is used to
    | differentiate between multiple projects which push emails
    | in the same database
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
            'api_url' => env('MAILGUN_API_URL', ''),
            'api_key' => env('MAILGUN_API_KEY'),
            'domain' => env('MAILGUN_DOMAIN'),
        ],
        'ses' => [
            'key' => env('AWS_SES_KEY'),
            'secret' => env('AWS_SES_SECRET'),
            'region' => env('AWS_SES_REGION'),  // e.g. us-east-1
        ]
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