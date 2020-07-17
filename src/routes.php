<?php
use Illuminate\Support\Facades\Route;

if(! config('aws-ses-bounce-complaint-handler.via_sqs')) {
    Route::post('amazon-sns/notifications', '\ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandler@handleBounceOrComplaint')->middleware('api');
}
