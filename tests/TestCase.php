<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Tests;

use ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandlerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AwsSesBounceComplaintHandlerServiceProvider::class];
    }

    public function getEnvironmentSetUp($app) : void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
           'driver' => 'sqlite',
           'database' => ':memory:',
           'prefix' => '',
        ]);

        include_once __DIR__ . '/../database/migrations/2020_07_17_204234_create_wrong_emails_table.php';
        (new \CreateWrongEmailsTable())->up();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/../database/factories');
    }
}
