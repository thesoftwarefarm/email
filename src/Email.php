<?php

namespace TsfCorp\Email;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class Email
{
    /**
     * @var string
     */
    private $project;
    /**
     * @var string
     */
    private $uuid;
    /**
     * @var string
     */
    private $provider;
    /**
     * @var array
     */
    private $from = [];
    /**
     * @var array
     */
    private $recipients = [];
    /**
     * @var array
     */
    private $reply_to = [];
    /**
     * @var string
     */
    private $subject;
    /**
     * @var string
     */
    private $body;
    /**
     * @var array
     */
    private $attachments = [];
    /**
     * @var array
     */
    private $available_providers = ['mailgun', 'ses', 'google-smtp'];
    /**
     * @var \TsfCorp\Email\Models\EmailModel|null
     */
    private $model;
    /**
     * @var string
     */
    private $database_connection = null;

    public function __construct()
    {
        $this->project = config('email.project');
        $this->provider = config('email.default_provider');
        $this->uuid = Str::uuid();
    }

    /**
     * @param $provider
     * @return static
     * @throws \Exception
     */
    public function via($provider)
    {
        if (!in_array($provider, $this->available_providers)) {
            throw new Exception('Unrecognized email provider [' . $provider . ']');
        }

        $this->provider = $provider;

        return $this;
    }

    /**
     * @param $name
     * @return static
     */
    public function setDatabaseConnection($name)
    {
        $this->database_connection = $name;

        return $this;
    }

    /**
     * @param $type
     * @param $email
     * @param $name
     * @return static
     * @throws \Exception
     */
    public function addRecipient($type, $email, $name = null)
    {
        if (!$this->isValidEmailAddress($email)) {
            throw new Exception("Invalid {$type} address: {$email}");
        }

        $this->recipients[] = [
            'type' => $type,
            'email' => $email,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * @param $from
     * @param null $name
     * @return static
     * @throws \Exception
     */
    public function from($from, $name = null)
    {
        if (!$this->isValidEmailAddress($from)) {
            throw new Exception("Invalid from address: {$from}");
        }

        $this->from = [
            'email' => $from,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * @param $to
     * @param null $name
     * @return static
     * @throws \Exception
     */
    public function to($to, $name = null)
    {
        $this->addRecipient(EmailRecipient::TYPE_TO, $to, $name);

        return $this;
    }

    /**
     * @param $cc
     * @param null $name
     * @return static
     * @throws \Exception
     */
    public function cc($cc, $name = null)
    {
        $this->addRecipient(EmailRecipient::TYPE_CC, $cc, $name);

        return $this;
    }

    /**
     * @param $bcc
     * @param null $name
     * @return $this
     * @throws \Exception
     */
    public function bcc($bcc, $name = null)
    {
        $this->addRecipient(EmailRecipient::TYPE_BCC, $bcc, $name);

        return $this;
    }

    /**
     * @param $reply_to
     * @param null $name
     * @return static
     * @throws \Exception
     */
    public function replyTo($reply_to, $name = null)
    {
        if (!$this->isValidEmailAddress($reply_to)) {
            throw new Exception("Invalid reply to address: {$reply_to}");
        }

        $this->reply_to[] = [
            'email' => $reply_to,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * @param $subject
     * @return static
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param $body
     * @return static
     */
    public function body($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param $file_path
     * @param string $disk
     * @return static
     */
    public function addAttachment($file_path, $disk = 'local')
    {
        $this->attachments[] = [
            'disk' => $disk,
            'path' => $file_path,
        ];

        return $this;
    }

    /**
     * @return \TsfCorp\Email\Models\EmailModel|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return array|string[]
     */
    public function getAvailableProviders()
    {
        return $this->available_providers;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->body;
    }

    /**
     * Saves new email in database
     *
     * @return static
     * @throws \Exception
     */
    public function enqueue()
    {
        if (!count($this->from)) {
            $this->from(config('email.from.address'), config('email.from.name'));
        }

        $to = array_filter($this->recipients, fn($recipient) => $recipient['type'] === EmailRecipient::TYPE_TO);

        if (!count($to)) {
            throw new Exception('Missing to address.');
        }

        $this->model = new EmailModel;
        $this->model->setConnection($this->database_connection);

        $this->model->project = $this->project;
        $this->model->uuid = $this->uuid;
        $this->model->from = json_encode($this->from);
        $this->model->reply_to = count($this->reply_to) ? json_encode($this->reply_to) : null;
        $this->model->subject = $this->subject;
        $this->model->body = $this->body;
        $this->model->attachments = count($this->attachments) ? json_encode($this->attachments) : null;
        $this->model->provider = $this->provider;
        $this->model->status = EmailModel::STATUS_PENDING;
        $this->model->save();

        $this->model->recipients()->insert(array_map(fn($address) => [
            'email_id' => $this->model->id,
            'type' => $address['type'],
            'email' => $address['email'],
            'name' => $address['name'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], $this->recipients));

        return $this;
    }

    /**
     * Dispatches a job which will send the email
     *
     * @throws \Exception
     */
    public function dispatch(Carbon $delay = null)
    {
        if (!$this->model) {
            throw new Exception('There is no email to be dispatched.');
        }

        $this->model->dispatchJob($delay);

        return $this;
    }

    /**
     * @return static
     * @throws \Exception
     */
    public function send(Carbon $delay = null)
    {
        return $this->enqueue()->dispatch($delay);
    }

    /**
     * @param $email_address
     * @return bool
     */
    private function isValidEmailAddress($email_address)
    {
        return !empty($email_address) && filter_var($email_address, FILTER_VALIDATE_EMAIL);
    }
}
