<?php

namespace TsfCorp\Email\Tests;

use Aws\Sns\MessageValidator;
use Mockery;
use TsfCorp\Email\Email;
use Illuminate\Support\Facades\Event;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Models\EmailRecipient;

class SesWebhookTest extends TestCase
{
    public function test_it_returns_error_if_no_payload_was_supplied()
    {
        $payload = null;

        $response = $this->call('POST', '/webhook-ses', [], [], [], [], json_encode($payload));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('No payload supplied.', $response->json());
    }

    public function test_it_returns_error_if_signature_is_invalid()
    {
        $this->instance(MessageValidator::class, Mockery::mock(MessageValidator::class, function ($mock) {
            $mock->shouldReceive('isValid')->once()->andReturn(false);
        }));

        $response = $this->call('POST', '/webhook-ses', [], [], [], [], json_encode([
            'Type' => 'Dummy notification',
            'MessageId' => '89ca5d35-1008-5b4a-89b5-08e157a4aae1',
            'TopicArn' => '1',
            'Message' => json_encode([]),
            'Timestamp' => '2019-08-19T06:45:00.710Z',
            'SignatureVersion' => '1',
            'Signature' => '1',
            'SigningCertURL' => 'url',
        ]));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Invalid Signature.', $response->json());
    }

    public function test_it_returns_error_for_unrecognized_notifications()
    {
        $this->instance(MessageValidator::class, Mockery::mock(MessageValidator::class, function ($mock) {
            $mock->shouldReceive('isValid')->once()->andReturn(true);
        }));

        $response = $this->call('POST', '/webhook-ses', [], [], [], [], json_encode([
            'Type' => 'Dummy notification',
            'MessageId' => '89ca5d35-1008-5b4a-89b5-08e157a4aae1',
            'TopicArn' => '1',
            'Message' => json_encode([]),
            'Timestamp' => '2019-08-19T06:45:00.710Z',
            'SignatureVersion' => '1',
            'Signature' => '1',
            'SigningCertURL' => 'url',
        ]));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Invalid notification type.', $response->json());
    }

    public function test_it_accepts_subscription_confirmation_url()
    {
        $this->instance(MessageValidator::class, Mockery::mock(MessageValidator::class, function ($mock) {
            $mock->shouldReceive('isValid')->once()->andReturn(true);
        }));

        $response = $this->call('POST', '/webhook-ses', [], [], [], [], json_encode([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => '89ca5d35-1008-5b4a-89b5-08e157a4aae1',
            'TopicArn' => '1',
            'Message' => json_encode([]),
            'Timestamp' => '2019-08-19T06:45:00.710Z',
            'SignatureVersion' => '1',
            'Signature' => '1',
            'SigningCertURL' => 'url',
            'SubscribeURL' => 'url',
            'Token' => 'token',
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Confirmation link received.', $response->json());
    }

    public function test_it_returns_error_if_email_not_found_in_database()
    {
        $this->instance(MessageValidator::class, Mockery::mock(MessageValidator::class, function ($mock) {
            $mock->shouldReceive('isValid')->once()->andReturn(true);
        }));

        $response = $this->call('POST', '/webhook-ses', [], [], [], [], json_encode([
            'Type' => 'Notification',
            'MessageId' => '89ca5d35-1008-5b4a-89b5-08e157a4aae1',
            'TopicArn' => '1',
            'Message' => json_encode([
                'notificationType' => 'Bounce',
                'bounce' => [
                    'bounceType' => 'Permanent'
                ],
                'mail' => [
                    'messageId' => 'dummy indentifier'
                ]
            ]),
            'Timestamp' => '2019-08-19T06:45:00.710Z',
            'SignatureVersion' => '1',
            'Signature' => '1',
            'SigningCertURL' => 'url',
        ]));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Record not found.', $response->json());
    }

    public function test_bounce_from_ses()
    {
        Event::fake();
        $email = (new Email())->to('to@mail.com')->enqueue();

        $model = $email->getModel();
        $model->remote_identifier = 'EMAIL_IDENTIFIER';
        $model->save();

        $this->instance(MessageValidator::class, Mockery::mock(MessageValidator::class, function ($mock) {
            $mock->shouldReceive('isValid')->once()->andReturn(true);
        }));

        $response = $this->call('POST', '/webhook-ses', [], [], [], [], json_encode([
            'Type' => 'Notification',
            'MessageId' => '89ca5d35-1008-5b4a-89b5-08e157a4aae1',
            'TopicArn' => '1',
            'Message' => json_encode([
                'notificationType' => 'Bounce',
                'bounce' => [
                    'bounceType' => 'Permanent',
                    'bouncedRecipients' => [
                        [
                            'emailAddress' => 'to@mail.com',
                            'action' => 'failed',
                            'status' => '5.1.1',
                            'diagnosticCode' => 'Diagnostic code',
                        ],
                    ]
                ],
                'mail' => [
                    'messageId' => 'EMAIL_IDENTIFIER'
                ]
            ]),
            'Timestamp' => '2019-08-19T06:45:00.710Z',
            'SignatureVersion' => '1',
            'Signature' => '1',
            'SigningCertURL' => 'url',
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Thank you.', $response->json());

        $model = $model->fresh();
        $recipient = $model->getRecipientByEmail('to@mail.com');

        $this->assertEquals(EmailRecipient::STATUS_FAILED, $recipient->status);
        $this->assertEquals('Diagnostic code', $recipient->notes);
        Event::assertDispatched(EmailFailed::class);
    }
}
