<?php

namespace TsfCorp\Email\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install email resources';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ( ! $this->confirm('This will install email config file and optionally migration file. Do you wish to continue?'))
        {
            $this->comment('Aborted.');
            return;
        }

        $this->comment('Publishing config file...');
        $this->callSilent('vendor:publish', ['--tag' => ['email-config']]);

        if ($this->confirm('Do you wish to publish migration file?'))
        {
            $this->comment('Publishing migration file...');
            $this->callSilent('vendor:publish', ['--tag' => ['email-migrations']]);
        }

        $this->info('Email package was installed successfully.');
    }
}
