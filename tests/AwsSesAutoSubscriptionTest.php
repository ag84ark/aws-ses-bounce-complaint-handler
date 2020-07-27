<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class AwsSesAutoSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_auto_confirms_the_subscription_to_https(): void
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
            new Response(202, ['Content-Length' => 0]),
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->app->instance(Client::class, $client);

        $this->postJson('amazon-sns/notifications', $this->getAWSSubscriptionConfirmationData())
            ->assertSuccessful()
            ->assertJson(['message' => 'SubscriptionConfirmation was auto confirmed!']);
    }

    /** @test */
    public function does_not_auto_confirms_the_subscription_to_https(): void
    {
        Config::set('aws-ses-bounce-complaint-handler.auto_subscribe', false);

        $this->postJson('amazon-sns/notifications', $this->getAWSSubscriptionConfirmationData())
            ->assertSuccessful()
            ->assertJson(['message' => 'no data']);
    }

    private function getAWSSubscriptionConfirmationData(): array
    {
        $requestData = file_get_contents(__DIR__.'/json/aws_sns_subscription_confirmation.json');

        return json_decode($requestData, true);
    }
}
