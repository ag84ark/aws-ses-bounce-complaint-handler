<?php

return [
    // use /amazon-sns/notifications?&secret=mySecret on the declared route for extra protection
    'route_secret' => null,

    'log_requests' => false,

    // Timeout for Bounce transient emails
    'block_bounced_transient_for_minutes' => 300,

    // Via SQS or HTTP
    'via_sqs' => false,
];
