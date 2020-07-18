<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Tests;

use ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandlerServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AwsSesBounceComplaintHandlerServiceProvider::class];
    }
}
