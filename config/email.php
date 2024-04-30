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

    'project' => null,

    /*
    |--------------------------------------------------------------------------
    | Default provider
    |--------------------------------------------------------------------------
    |
    | Configure the default provider to be used to send email.
    |
    | Available options: 'mailgun', 'ses', 'google-smtp',
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
            'region' => env('MAILGUN_REGION'),
            'webhook_secret' => env('MAILGUN_WEBHOOK_SECRET'),
        ],
        'ses' => [
            'key' => env('AWS_SES_KEY'),
            'secret' => env('AWS_SES_SECRET'),
            'region' => env('AWS_SES_REGION'),  // e.g. us-east-1
        ],
        'google-smtp' => [
            'email' => env('GMAIL_USER_EMAIL'),
            'password' => env('GMAIL_USER_PASSWORD'),
        ]
    ],

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
    | Webhook Email Model Resolver
    |--------------------------------------------------------------------------
    |
    | When an webhook comes in, this class is used to resolve the email model
    | from database.
    |
    */

    'webhook_email_model_resolver' => \TsfCorp\Email\DefaultWebhookEmailModelResolver::class,

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
