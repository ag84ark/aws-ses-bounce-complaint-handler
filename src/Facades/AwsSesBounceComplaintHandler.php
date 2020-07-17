<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Facades;

use Illuminate\Support\Facades\Facade;

class AwsSesBounceComplaintHandler extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'aws-ses-bounce-complaint-handler';
    }
}
