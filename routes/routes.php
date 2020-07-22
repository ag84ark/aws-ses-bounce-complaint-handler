<?php

use ag84ark\AwsSesBounceComplaintHandler\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

if (! config('aws-ses-bounce-complaint-handler.via_sqs')) {
    Route::post('amazon-sns/notifications', [PackageController::class, 'handleBounceOrComplaint'])->middleware('api');
}
