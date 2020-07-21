<?php

use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use Faker\Generator;

$factory->define(WrongEmail::class, function (Generator $faker) {
    return [
       'email' => $faker->email,
       'repeated_attempts' => $faker->randomNumber(1),
       'problem_type' => $faker->randomElement(['Bounce', 'Complaint']),
       'problem_subtype' => $faker->randomElement(['Permanent', 'abuse', 'General']),
       'ignore' => false,
   ];
});
