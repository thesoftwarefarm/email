<?php

namespace TsfCorp\Email;

use Illuminate\Support\ServiceProvider;
use TsfCorp\Email\Commands\DispatchJobs;
use TsfCorp\Email\Commands\InstallCommand;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->app->routesAreCached())
        {
            require __DIR__.'/Http/routes.php';
        }

        if($this->app->runningInConsole())
        {
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

    /**
     * Added for L.5.1 compatibility
     */
    public function register()
    {

    }

    public function provides()
    {
        return ['email'];
    }
}
