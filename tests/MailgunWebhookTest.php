<?php

namespace TsfCorp\Email\Tests;

use TsfCorp\Email\Email;
use Illuminate\Support\Facades\Event;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class MailgunWebhookTest extends TestCase
{
    public function test_failed_event_without_reason()
    {
        Event::fake();

        $model = (new Email())->to('to@mail.com')->enqueue()->getModel();

        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->status = EmailModel::STATUS_SENT;
        $model->save();

        $response = $this->call('POST', '/webhook-mailgun', [
            'signature' => $this->createSignature(),
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
                ],
            ],
        ]);


        $model = $model->fresh();
        $recipient = $model->getRecipientByEmail('to@mail.com');

        $response->assertStatus(200);
        $this->assertEquals(EmailRecipient::STATUS_FAILED, $recipient->status);
        $this->assertNull($recipient->notes);
        Event::assertDispatched(EmailFailed::class);
    }

    public function test_failed_event_with_reason_taken_from_message_key()
    {
        Event::fake();

        $model = (new Email())->to('to@mail.com')->enqueue()->getModel();

        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->status = EmailModel::STATUS_SENT;
        $model->save();

        $response = $this->call('POST', '/webhook-mailgun', [
            'signature' => $this->createSignature(),
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
                    'message' => 'message',
                ],
            ],
        ]);


        $model = $model->fresh();
        $recipient = $model->getRecipientByEmail('to@mail.com');

        $response->assertStatus(200);
        $this->assertEquals(EmailRecipient::STATUS_FAILED, $recipient->status);
        $this->assertEquals('message', $recipient->notes);
        Event::assertDispatched(EmailFailed::class);
    }

    public function test_failed_event_with_reason_taken_from_description_key()
    {
        Event::fake();

        $model = (new Email())->to('to@mail.com')->enqueue()->getModel();

        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->status = EmailModel::STATUS_SENT;
        $model->save();

        $response = $this->call('POST', '/webhook-mailgun', [
            'signature' => $this->createSignature(),
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
                    'description' => 'description',
                ],
            ],
        ]);


        $model = $model->fresh();
        $recipient = $model->getRecipientByEmail('to@mail.com');

        $response->assertStatus(200);
        $this->assertEquals(EmailRecipient::STATUS_FAILED, $recipient->status);
        $this->assertEquals('description', $recipient->notes);
        Event::assertDispatched(EmailFailed::class);
    }

    /**
     * @return array
     */
    private function createSignature()
    {
        $time = time();
        $token = 'TOKEN';
        $signature = hash_hmac('SHA256', $time . $token, config('email.providers.mailgun.webhook_secret'));

        return [
            'timestamp' => $time,
            'token' => $token,
            'signature' => $signature,
        ];
    }
}
