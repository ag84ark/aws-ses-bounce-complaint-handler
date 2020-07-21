<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Tests\Models;

use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use ag84ark\AwsSesBounceComplaintHandler\Tests\TestCase;

class WrongEmailTest extends TestCase
{
    /** @test */
    public function is_unsubscribed_and_cant_send(): void
    {
        $clientEmail = 'me@example.com';
        factory(WrongEmail::class)->create(['problem_type' => 'Complaint', 'email' => $clientEmail]);

        $wrongEmail = WrongEmail::complained()->where('email', '=', $clientEmail)->first();

        $this->assertEquals($clientEmail, $wrongEmail->email);

        $this->assertTrue($wrongEmail->unsubscribed());
        $this->assertTrue($wrongEmail->dontSend());
    }
}
