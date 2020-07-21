<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Tests;

use ag84ark\AwsSesBounceComplaintHandler\Facades\AwsSesBounceComplaintHandler;
use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AwsSesBounceComplaintHandlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_not_send_to_permanent_bounce(): void
    {
        $email = 'me@me.com';

        factory(WrongEmail::class)->create(['email' => $email, 'problem_type' => 'Bounce', 'problem_subtype' => 'Permanent']);

        $this->assertFalse(AwsSesBounceComplaintHandler::canSendToEmail($email));
    }

    /** @test */
    public function it_can_send_email(): void
    {
        $email = 'me@me.com';

        factory(WrongEmail::class, 5)->create(['problem_type' => 'Bounce', 'problem_subtype' => 'Permanent']);

        $this->assertTrue(AwsSesBounceComplaintHandler::canSendToEmail($email));
    }

    /** @test */
    public function it_handles_incoming_bounce_data_via_https(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.route_secret', null);
        \Config::set('aws-ses-bounce-complaint-handler.via_sqs', false);

        $this->postJson('amazon-sns/notifications', $this->getAWSBounceData())
            ->assertSuccessful();

        $this->assertDatabaseHas((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    private function getAWSBounceData(): array
    {
        $requestData = file_get_contents(__DIR__.'/json/aws_bounce_response.json');

        return json_decode($requestData, true);
    }

    /** @test */
    public function it_handles_incoming_complaint_data_via_https(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.route_secret', null);
        \Config::set('aws-ses-bounce-complaint-handler.via_sqs', false);

        $this->postJson('amazon-sns/notifications', $this->getAWSComplaintData())
            ->assertSuccessful();

        $this->assertDatabaseHas((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    private function getAWSComplaintData(): array
    {
        $requestData = file_get_contents(__DIR__.'/json/aws_complaint_response.json');

        return json_decode($requestData, true);
    }

    /** @test */
    public function it_is_protected_by_secret_on_https_call(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.route_secret', 'someSecret');
        \Config::set('aws-ses-bounce-complaint-handler.via_sqs', false);

        $this->postJson('amazon-sns/notifications', $this->getAWSBounceData())
            ->assertForbidden();

        $this->assertDatabaseMissing((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    /** @test */
    public function it_is_not_forbidden_when_password_is_set_and_via_sqs(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.route_secret', 'someSecret');
        \Config::set('aws-ses-bounce-complaint-handler.via_sqs', true);

        $this->postJson('amazon-sns/notifications', $this->getAWSBounceSQSData())
            ->assertStatus(200);

        $this->assertDatabaseHas((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    private function getAWSBounceSQSData(): array
    {
        $requestData = file_get_contents(__DIR__.'/json/sqs_aws_bounce_response.json');

        return json_decode($requestData, true);
    }

    /** @test */
    public function it_is_protected_by_secret_and_passes_on_https_call(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.route_secret', 'someSecret');
        \Config::set('aws-ses-bounce-complaint-handler.via_sqs', false);

        $this->postJson('amazon-sns/notifications?secret=someSecret', $this->getAWSBounceData())
            ->assertSuccessful();

        $this->assertDatabaseHas((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    /** @test */
    public function log_the_data(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.log_requests', true);

        $this->postJson('amazon-sns/notifications?secret=someSecret', $this->getAWSBounceData())
            ->assertSuccessful();
    }

    /** @test */
    public function it_auto_confirms_the_subscription_to_https(): void
    {
        $this->postJson('amazon-sns/notifications', $this->getAWSSubscriptionConfirmationData())
            ->assertSuccessful()
            ->assertJson(['message' => 'SubscriptionConfirmation was auto confirmed!']);
    }

    private function getAWSSubscriptionConfirmationData(): array
    {
        $requestData = file_get_contents(__DIR__.'/json/aws_sns_subscription_confirmation.json');

        return json_decode($requestData, true);
    }

    /** @test */
    public function does_not_auto_confirms_the_subscription_to_https(): void
    {
        \Config::set('aws-ses-bounce-complaint-handler.auto_subscribe', false);

        $this->postJson('amazon-sns/notifications', $this->getAWSSubscriptionConfirmationData())
            ->assertSuccessful()
            ->assertJson(['message' => 'no data']);
    }
}
