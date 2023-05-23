<?php

namespace TsfCorp\Email\Tests;

use TsfCorp\Email\Email;
use Illuminate\Support\Facades\Event;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Jobs\EmailJob;

class MailgunWebhookTest extends TestCase
{
    public function test_failed_event()
    {
        Event::fake();
        $email = (new Email())->to('to@mail.com')->enqueue();

        $model = $email->getModel();
        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->status = 'sent';
        $model->save();

        $time = time();
        $token = 'TOKEN';
        $signature = hash_hmac('SHA256', $time.$token, config('email.providers.mailgun.webhook_secret'));

        $this->call('POST', '/webhooks/mailgun', [
            'signature' => [
                'timestamp' => $time,
                'token' => $token,
                'signature' => $signature,
            ],
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'failed',
                'severity' => 'permanent',
                'recipient' => 'to@mail.com',
                'reason' => 'suppress-bounce',
                'delivery-status' => [
                    'code' => 605,
                    'description' => 'Not delivering to previously bounced address',
                    'message' => 'The email account that you tried to reach does not exist',
                ],
            ],
        ]);

        $model = $model->fresh();
        
        Event::assertDispatched(EmailFailed::class);
    }
}