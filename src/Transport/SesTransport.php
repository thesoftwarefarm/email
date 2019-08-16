<?php

namespace TsfCorp\Email\Transport;

use Aws\Ses\SesClient;
use Throwable;
use TsfCorp\Email\Models\EmailModel;

class SesTransport extends Transport
{
    /**
     * @var SesClient
     */
    private $ses;

    /**
     * MailgunTransport constructor.
     * @param \Aws\Ses\SesClient $ses
     */
    public function __construct(SesClient $ses)
    {
        $this->ses = $ses;
    }

    /**
     * @param \TsfCorp\Email\Models\EmailModel $email
     * @throws \Throwable
     */
    public function send(EmailModel $email)
    {
        try
        {
            $response = $this->ses->sendEmail([
                'Source' => $email->prepareFromAddress(),
                'Destination' => [
                    'ToAddresses' => $email->prepareToAddress() ?? [],
                    'CcAddresses' => $email->prepareCcAddress() ?? [],
                    'BccAddresses' => $email->prepareBccAddress() ?? [],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Data' => $email->body,
                        ],
                        'Text' => [
                            'Data' => 'To view the message, please use an HTML compatible email viewer',
                        ],
                    ],
                    'Subject' => [
                        'Data' => $email->subject,
                    ],
                ],
            ]);

            $this->remote_identifier = $response->get('MessageId');
            $this->message = 'Queued.';
        }
        catch (Throwable $t)
        {
            throw $t;
        }
    }
}