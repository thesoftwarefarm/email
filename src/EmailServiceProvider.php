<?php

namespace TsfCorp\Email;

use Illuminate\Support\ServiceProvider;
use TsfCorp\Email\Commands\DispatchJobs;
use TsfCorp\Email\Commands\InstallCommand;

class EmailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/Http/routes.php';
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/email.php' => config_path('email.php')
            ], 'email-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations')
            ], 'email-migrations');

            $this->commands([
                InstallCommand::class,
                DispatchJobs::class,
            ]);
        }
    }
}
