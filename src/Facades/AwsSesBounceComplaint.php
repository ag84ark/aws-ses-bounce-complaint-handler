<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Facades;

use Illuminate\Support\Facades\Facade;


/**

 * @method static bool canSendToEmail(string $email)
 *
 * @see \ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandler
 */
class AwsSesBounceComplaint extends Facade
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
