<?php

namespace TsfCorp\Email\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../database/migrations/2018_12_01_000000_create_emails_table.php';
        include_once __DIR__.'/../database/migrations/2018_12_01_000000_create_email_bounces_table.php';

        (new \CreateEmailsTable())->up();
        (new \CreateEmailBouncesTable())->up();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.env', 'production');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('email.database_connection', 'sqlite');
        $app['config']->set('email.project', 'MY_PROJECT');
        $app['config']->set('email.default_provider', 'mailgun');

        $app['config']->set('email.providers', [
            'mailgun' => [
                'api_url' => '',
                'api_key' => 'mailgun_api_key',
                'domain' => '',
            ],
            'ses' => [
                'key' => 'ses_api_secret',
                'secret' => 'ses_api_secret',
                'region' => 'eu-west-1',
            ]
        ]);

        $app['config']->set('email.from', [
            'address' => 'default@address.com',
            'name' => 'Default Name'
        ]);

        $app['config']->set('email.max_retries', 10);
    }

    protected function getPackageProviders($app)
    {
        return ['TsfCorp\Email\EmailServiceProvider'];
    }
}