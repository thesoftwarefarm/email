<?php

namespace TsfCorp\Email\Tests;

use TsfCorp\Email\Email;

class MailgunWebhookTest extends TestCase
{
    public function test_bounce_is_saved()
    {
        $email = (new Email())->to('to@mail.com')->enqueue();

        $model = $email->getModel();
        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->save();

        $time = time();
        $token = str_random();
        $signature = hash_hmac('SHA256', $time.$token, config('email.providers.mailgun.api_key'));

        $this->call('POST', '/webhook-mailgun', [
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
        $bounce = $model->bounces->first();

        $this->assertEquals('1', $model->bounces_count);

        $this->assertEquals($model->id, $bounce->email_id);
        $this->assertEquals('to@mail.com', $bounce->recipient);
        $this->assertEquals(605, $bounce->code);
        $this->assertEquals('suppress-bounce', $bounce->reason);
        $this->assertEquals('Not delivering to previously bounced address', $bounce->description);
        $this->assertEquals('The email account that you tried to reach does not exist', $bounce->message);
    }
}