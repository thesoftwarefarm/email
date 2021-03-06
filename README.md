# Library for sending emails - Laravel
 
Fluent interface for composing and sending emails

This package was designed to work in a standalone project or in a cluster of projects which push messages into a master project/database which act as a collector.

If you use this package in cluster mode, make sure the process `php artisan emails:dispatch-jobs` is running on master project. This can be kept alive with `supervisor`

#Upgrade from 2.x to 3.x
* add a new uuid column in emails table
* addAttachments(...$file_paths) method was removed

# Installation

Require this package in your `composer.json` and update composer. Run the following command:
```php
composer require tsfcorp/email
```

After updating composer, the service provider will automatically be registered and enabled using Auto-Discovery

If your Laravel version is less than 5.5, make sure you add the service provider within `app.php` config file.

```php
'providers' => [
    // ...
    TsfCorp\Email\EmailServiceProvider::class,
];
```

Next step is to run the artisan command to install config file and optionally migration file. The command will guide you through the process.

```php
php artisan email:install
```

Update `config/email.php` with your settings.
### Requirements
This package makes use of Laravel Queues/Jobs to send emails. Make sure the queue system is configured properly

# Usage Instructions

```php
use TsfCorp\Email\Email;

$email = (new Email())
    ->to('to@mail.com')
    ->cc('cc@mail.com')
    ->bcc('bcc@mail.com')
    ->subject('Hi')
    ->body('Hi there!');
``` 
Use `enqueue()` method to save the message in database without sending. Useful when you want to just save the message but delay sending. Or when `database_connection` config value is another database and sending is performed from there.

```php
$email->enqueue();
```

Save the message and schedule a job to send the email
```php
$email->enqueue()->dispatch();
```

#Email Providers
- Mailgun
- Amazon SES
- Google SMTP

```
Note 1: In order to use Google SMTP you need at least PHP 7.1.3 and also require symfony/google-mailer in your composer.json
Note 2: If your Google Account has 2FA enabled you need to generate an "App Password" in your Google Acccount
```

#Bounce Webhooks
If an email could not be sent to a recipient, the email provider can notify you about this. This package handles permanent failures webhooks for you. 

#### Mailgun
Add `http://app.example/webhook-mailgun` link to "Permanent Failure" section within you mailgun webhooks settings.

#### Amazon SES
1. Create a new topic under Amazon SNS
2. Create a new subscription under the topic created above where you specify `http://app.example/webhook-ses` as endpoint
3. After the subscription was created, AWS will make a post request to specified endpoint with an URL which should be used to confirm subscription. That url can be found in app logs. Copy and paste that in browser.
4. Edit Amazon SES domain and add the topic created at step 1 under Notifications -> Bounce Notifications SNS Topic

