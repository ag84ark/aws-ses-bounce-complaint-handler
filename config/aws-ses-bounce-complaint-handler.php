<?php

return [
    /*
     * use /amazon-sns/notifications?&secret=mySecret on the declared route for extra protection
     */
    'route_secret' => null,

    /*
     * Log the information received to Laravel Log
     */
    'log_requests' => false,

    /*
     * Timeout for Bounce transient emails
     */
    'block_bounced_transient_for_minutes' => 300,

    /*
     * Use Amazon SQS or HTTP(s)
     */
    'via_sqs' => false,

    /*
     * Auto subscribe to SNS
     * Sends a get request to SubscribeURL from AWS SNS to confirm the subscription
     */
    'auto_subscribe' => true,
];
