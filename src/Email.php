<?php

namespace TsfCorp\Email;

use Exception;
use TsfCorp\Email\Models\EmailModel;

class Email
{
    /**
     * @var string
     */
    private $project;
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
    private $to = [];
    /**
     * @var array
     */
    private $cc = [];
    /**
     * @var array
     */
    private $bcc = [];
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

    public function __construct()
    {
        $this->project = config('email.project');
        $this->provider = config('email.default_provider');
    }

    /**
     * @param $provider
     * @return \TsfCorp\Email\Email
     * @throws \Exception
     */
    public function via($provider)
    {
        if ( ! in_array($provider, $this->available_providers))
        {
            throw new Exception('Unrecognized email provider [' . $provider . ']');
        }

        $this->provider = $provider;

        return $this;
    }

    /**
     * @param $from
     * @param null $name
     * @return \TsfCorp\Email\Email
     * @throws \Exception
     */
    public function from($from, $name = null)
	{
	    if (empty($from) || ! filter_var($from, FILTER_VALIDATE_EMAIL))
            throw new Exception('Invalid from address: ' . $from);

		$this->from = [
		    'email' => $from,
		    'name' => $name,
        ];

		return $this;
	}

    /**
     * @param $to
     * @param null $name
     * @return \TsfCorp\Email\Email
     * @throws \Exception
     */
    public function to($to, $name = null)
	{
	    if (empty($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL))
            throw new Exception('Invalid to address: ' . $to);

		$this->to[] = [
		    'email' => $to,
		    'name' => $name,
        ];

		return $this;
	}

    /**
     * @param $cc
     * @param null $name
     * @return \TsfCorp\Email\Email
     * @throws \Exception
     */
    public function cc($cc, $name = null)
	{
	    if (empty($cc) || ! filter_var($cc, FILTER_VALIDATE_EMAIL))
            throw new Exception('Invalid cc address: ' . $cc);

		$this->cc[] = [
		    'email' => $cc,
		    'name' => $name,
        ];

		return $this;
	}

    /**
     * @param $bcc
     * @param null $name
     * @return \TsfCorp\Email\Email
     * @throws \Exception
     */
    public function bcc($bcc, $name = null)
	{
	    if (empty($bcc) || ! filter_var($bcc, FILTER_VALIDATE_EMAIL))
            throw new Exception('Invalid bcc address: ' . $bcc);

		$this->bcc[] = [
		    'email' => $bcc,
		    'name' => $name,
        ];

		return $this;
	}

    /**
     * @param $subject
     * @return \TsfCorp\Email\Email
     */
    public function subject($subject)
	{
		$this->subject = $subject;

		return $this;
	}

    /**
     * @param $body
     * @return \TsfCorp\Email\Email
     */
    public function body($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param $file_path
     * @return \TsfCorp\Email\Email
     */
    public function addAttachment($file_path)
    {
        $this->attachments[] = $file_path;

        return $this;
    }

    /**
     * @param mixed ...$file_paths
     * @return \TsfCorp\Email\Email
     */
    public function addAttachments(...$file_paths)
    {
        foreach ($file_paths as $file_path)
        {
            $this->addAttachment($file_path);
        }

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
     * @return array
     */
    public function getTo()
    {
        return $this->to;
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
    public function render()
    {
        return $this->body;
    }

    /**
     * Saves new email in database
     *
     * @return \TsfCorp\Email\Email
     * @throws \Exception
     */
    public function enqueue()
    {
        if ( ! count($this->from))
        {
            $this->from(config('email.from.address'), config('email.from.name'));
        }

        if ( ! count($this->to))
        {
            throw new Exception('Missing to address.');
        }

        $this->model = new EmailModel;
        $this->model->project = $this->project;
        $this->model->from = json_encode($this->from);
        $this->model->to = json_encode($this->to);
        $this->model->cc = count($this->cc) ? json_encode($this->cc) : null;
        $this->model->bcc = count($this->bcc) ? json_encode($this->bcc) : null;
        $this->model->subject = $this->subject;
        $this->model->body = $this->body;
        $this->model->attachments = count($this->attachments) ? json_encode($this->attachments) : null;
        $this->model->provider = $this->provider;
        $this->model->status = 'pending';
        $this->model->save();

        return $this;
    }

    /**
     * Dispatches a job which will send the email
     *
     * @throws \Exception
     */
    public function dispatch()
    {
        if ( ! $this->model)
        {
            throw new Exception('There is no email to be dispatched.');
        }

        $this->model->dispatchJob();

        return $this;
    }
}