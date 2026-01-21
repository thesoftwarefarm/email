<?php

namespace TsfCorp\Email;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Illuminate\View\View;
use TsfCorp\Email\Models\EmailModel;
use TsfCorp\Email\Models\EmailRecipient;

class Email
{
    private string $provider;
    private array $from = [];
    private array $recipients = [];
    private array $reply_to = [];
    private string $subject = '';
    private mixed $body = null;
    private array $attachments = [];
    private array $metadata = [];
    private array $available_providers = ['mailgun', 'ses', 'google-smtp'];
    private ?EmailModel $model = null;
    private ?string $database_connection = null;

    public function __construct()
    {
        $this->provider = config('email.default_provider');
    }

    public function via(string $provider): static
    {
        if (!in_array($provider, $this->available_providers)) {
            throw new Exception("Unrecognized email provider [{$provider}]");
        }

        $this->provider = $provider;

        return $this;
    }

    public function setDatabaseConnection(string $name): static
    {
        $this->database_connection = $name;

        return $this;
    }

    public function from(?string $from, ?string $name = null): static
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

    public function replyTo(?string $reply_to, ?string $name = null): static
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

    public function addRecipient(string $type, string $email, ?string $name = null): static
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

    public function to(string $to, ?string $name = null): static
    {
        $this->addRecipient(EmailRecipient::TYPE_TO, $to, $name);

        return $this;
    }

    public function cc(string $cc, ?string $name = null): static
    {
        $this->addRecipient(EmailRecipient::TYPE_CC, $cc, $name);

        return $this;
    }

    public function bcc(string $bcc, ?string $name = null): static
    {
        $this->addRecipient(EmailRecipient::TYPE_BCC, $bcc, $name);

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function body(mixed $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function addAttachment(Attachment $attachment): static
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getTo(): array
    {
        return array_filter($this->recipients, fn($recipient) => $recipient['type'] === EmailRecipient::TYPE_TO);
    }

    public function getCc(): array
    {
        return array_filter($this->recipients, fn($recipient) => $recipient['type'] === EmailRecipient::TYPE_CC);
    }

    public function getBcc(): array
    {
        return array_filter($this->recipients, fn($recipient) => $recipient['type'] === EmailRecipient::TYPE_BCC);
    }

    public function getModel(): ?EmailModel
    {
        return $this->model;
    }

    public function enqueue(): static
    {
        if (!count($this->from)) {
            $this->from(config('email.from.address'), config('email.from.name'));
        }

        if (!count($this->getTo())) {
            throw new Exception('Missing to address.');
        }

        $this->model = new EmailModel;
        $this->model->setConnection($this->database_connection);

        $this->model->uuid = Str::uuid();
        $this->model->project = config('email.project');
        $this->model->from = json_encode($this->from);
        $this->model->reply_to = count($this->reply_to) ? json_encode($this->reply_to) : null;
        $this->model->attachments = count($this->attachments) ? json_encode($this->attachments) : null;
        $this->model->metadata = count($this->metadata) ? json_encode($this->metadata) : null;
        $this->model->subject = $this->subject;
        $this->model->body = $this->body;
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

    public function dispatch(?Carbon $delay = null): static
    {
        if (!$this->model) {
            throw new Exception('There is no email to be dispatched.');
        }

        $this->model->dispatchJob($delay);

        return $this;
    }

    public function send(?Carbon $delay = null): static
    {
        return $this->enqueue()->dispatch($delay);
    }

    private function isValidEmailAddress(?string $email_address): bool
    {
        return !empty($email_address) && filter_var($email_address, FILTER_VALIDATE_EMAIL);
    }
}
