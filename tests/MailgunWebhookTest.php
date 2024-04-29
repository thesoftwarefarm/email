<?php

namespace TsfCorp\Email\Tests;

use TsfCorp\Email\Email;
use Illuminate\Support\Facades\Event;
use TsfCorp\Email\Events\EmailDelivered;
use TsfCorp\Email\Events\EmailFailed;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;
use TsfCorp\Email\Webhooks\ClickedWebhook;
use TsfCorp\Email\Webhooks\ComplainedWebhook;
use TsfCorp\Email\Webhooks\DeliveredWebhook;
use TsfCorp\Email\Webhooks\BouncedWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunClickedWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunComplainedWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunDeliveredWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunBouncedWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunOpenedWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunUnsubscribedWebhook;
use TsfCorp\Email\Webhooks\Mailgun\MailgunWebhookFactory;
use TsfCorp\Email\Webhooks\OpenedWebhook;
use TsfCorp\Email\Webhooks\UnknownWebhook;
use TsfCorp\Email\Webhooks\UnsubscribedWebhook;
use TsfCorp\Email\Webhooks\WebhookRecipient;

class MailgunWebhookTest extends TestCase
{
    public function test_parsing_unknown_webhook()
    {
        $unknown_webhook = MailgunWebhookFactory::make([]);

        $this->assertInstanceOf(UnknownWebhook::class, $unknown_webhook);
    }

    public function test_parsing_delivered_webhook()
    {
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'delivered',
                'recipient' => 'to@mail.com',
                'user-variables' => [
                    'key_1' => 'value_1',
                ],
            ],
        ];

        $delivered_webhook = MailgunWebhookFactory::make($payload);

        $this->assertInstanceOf(MailgunDeliveredWebhook::class, $delivered_webhook);
        $this->assertInstanceOf(DeliveredWebhook::class, $delivered_webhook);
        $this->assertEquals('<EMAIL_IDENTIFIER>', $delivered_webhook->getRemoteIdentifier());
        $this->assertEquals([WebhookRecipient::makeForDelivered('to@mail.com')], $delivered_webhook->getRecipients());
        $this->assertEquals(['key_1' => 'value_1'], $delivered_webhook->getMetadata());
        $this->assertEquals($payload, $delivered_webhook->getPayload());
    }

    public function test_parsing_bounced_webhook()
    {
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'failed',
                'recipient' => 'to@mail.com',
                'user-variables' => [
                    'key_1' => 'value_1',
                ],
            ],
        ];

        $failed_webhook = MailgunWebhookFactory::make($payload);

        $this->assertInstanceOf(MailgunBouncedWebhook::class, $failed_webhook);
        $this->assertInstanceOf(BouncedWebhook::class, $failed_webhook);
        $this->assertEquals('<EMAIL_IDENTIFIER>', $failed_webhook->getRemoteIdentifier());
        $this->assertEquals([WebhookRecipient::makeForFailed('to@mail.com')], $failed_webhook->getRecipients());
        $this->assertEquals(['key_1' => 'value_1'], $failed_webhook->getMetadata());
        $this->assertEquals($payload, $failed_webhook->getPayload());

        // test the reason is taken from "description" property when exist
        $payload = [
            'event-data' => [
                'event' => 'failed',
                'recipient' => 'to@mail.com',
                'delivery-status' => [
                    'code' => 605,
                    'description' => 'description',
                ],
            ],
        ];

        $this->assertEquals([WebhookRecipient::makeForFailed('to@mail.com', 'description')], MailgunWebhookFactory::make($payload)->getRecipients());

        // test the reason is taken from "message" property when exist
        $payload = [
            'event-data' => [
                'event' => 'failed',
                'recipient' => 'to@mail.com',
                'delivery-status' => [
                    'code' => 605,
                    'message' => 'message',
                ],
            ],
        ];

        $this->assertEquals([WebhookRecipient::makeForFailed('to@mail.com', 'message')], MailgunWebhookFactory::make($payload)->getRecipients());
    }

    public function test_parsing_opened_webhook()
    {
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'opened',
                'recipient' => 'to@mail.com',
                'user-variables' => [
                    'key_1' => 'value_1',
                ],
            ],
        ];

        $opened_webhook = MailgunWebhookFactory::make($payload);

        $this->assertInstanceOf(MailgunOpenedWebhook::class, $opened_webhook);
        $this->assertInstanceOf(OpenedWebhook::class, $opened_webhook);
        $this->assertEquals('<EMAIL_IDENTIFIER>', $opened_webhook->getRemoteIdentifier());
        $this->assertEquals([WebhookRecipient::makeForOpened('to@mail.com')], $opened_webhook->getRecipients());
        $this->assertEquals(['key_1' => 'value_1'], $opened_webhook->getMetadata());
        $this->assertEquals($payload, $opened_webhook->getPayload());
    }

    public function test_parsing_clicked_webhook()
    {
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'clicked',
                'recipient' => 'to@mail.com',
                'user-variables' => [
                    'key_1' => 'value_1',
                ],
            ],
        ];

        $clicked_webhook = MailgunWebhookFactory::make($payload);

        $this->assertInstanceOf(MailgunClickedWebhook::class, $clicked_webhook);
        $this->assertInstanceOf(ClickedWebhook::class, $clicked_webhook);
        $this->assertEquals('<EMAIL_IDENTIFIER>', $clicked_webhook->getRemoteIdentifier());
        $this->assertEquals([WebhookRecipient::makeForClicked('to@mail.com')], $clicked_webhook->getRecipients());
        $this->assertEquals(['key_1' => 'value_1'], $clicked_webhook->getMetadata());
        $this->assertEquals($payload, $clicked_webhook->getPayload());
    }

    public function test_parsing_unsubscribed_webhook()
    {
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'unsubscribed',
                'recipient' => 'to@mail.com',
                'user-variables' => [
                    'key_1' => 'value_1',
                ],
            ],
        ];

        $unsubscribed_webhook = MailgunWebhookFactory::make($payload);

        $this->assertInstanceOf(MailgunUnsubscribedWebhook::class, $unsubscribed_webhook);
        $this->assertInstanceOf(UnsubscribedWebhook::class, $unsubscribed_webhook);
        $this->assertEquals('<EMAIL_IDENTIFIER>', $unsubscribed_webhook->getRemoteIdentifier());
        $this->assertEquals([WebhookRecipient::makeForUnsubscribed('to@mail.com')], $unsubscribed_webhook->getRecipients());
        $this->assertEquals(['key_1' => 'value_1'], $unsubscribed_webhook->getMetadata());
        $this->assertEquals($payload, $unsubscribed_webhook->getPayload());
    }

    public function test_parsing_complained_webhook()
    {
        $payload = [
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'complained',
                'recipient' => 'to@mail.com',
                'user-variables' => [
                    'key_1' => 'value_1',
                ],
            ],
        ];

        $complained_webhook = MailgunWebhookFactory::make($payload);

        $this->assertInstanceOf(MailgunComplainedWebhook::class, $complained_webhook);
        $this->assertInstanceOf(ComplainedWebhook::class, $complained_webhook);
        $this->assertEquals('<EMAIL_IDENTIFIER>', $complained_webhook->getRemoteIdentifier());
        $this->assertEquals([WebhookRecipient::makeForComplained('to@mail.com')], $complained_webhook->getRecipients());
        $this->assertEquals(['key_1' => 'value_1'], $complained_webhook->getMetadata());
        $this->assertEquals($payload, $complained_webhook->getPayload());
    }

    public function test_request_for_delivered_webhook()
    {
        Event::fake();

        $model = (new Email())->to('to@mail.com')->enqueue()->getModel();

        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->status = EmailModel::STATUS_SENT;
        $model->save();

        $response = $this->post('/webhook-mailgun', [
            'signature' => $this->createSignature(),
            'event-data' => [
                'message' => [
                    'headers' => [
                        'message-id' => 'EMAIL_IDENTIFIER'
                    ]
                ],
                'event' => 'delivered',
                'recipient' => 'to@mail.com',
            ],
        ]);


        $model = $model->fresh();
        $recipient = $model->getRecipientByEmail('to@mail.com');

        $response->assertStatus(200);
        $this->assertEquals(EmailRecipient::STATUS_DELIVERED, $recipient->status);
        Event::assertDispatched(EmailDelivered::class);
    }

    public function test_request_for_bounced_webhook()
    {
        Event::fake();

        $model = (new Email())->to('to@mail.com')->enqueue()->getModel();

        $model->remote_identifier = '<EMAIL_IDENTIFIER>';
        $model->status = EmailModel::STATUS_SENT;
        $model->save();

        $response = $this->post('/webhook-mailgun', [
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
