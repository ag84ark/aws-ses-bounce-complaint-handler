<?php


namespace ag84ark\AwsSesBounceComplaintHandler\Tests;

use ag84ark\AwsSesBounceComplaintHandler\Facades\AwsSesBounceComplaintHandler;
use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use Illuminate\Foundation\Testing\DatabaseMigrations;


class AwsSesBounceComplaintHandlerTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function it_can_not_send_to_permanent_bounce(): void
    {
        $email = 'me@me.com';

        WrongEmail::create(['email' => $email, 'problem_type' => 'Bounce', 'problem_subtype' => 'Permanent']);

        $this->assertFalse(AwsSesBounceComplaintHandler::canSendToEmail($email));

    }

    /** @test */
    public function it_handles_incoming_bounce_data_via_https(): void
    {
        \Config::set("aws-ses-bounce-complaint-handler.route_secret", null);
        \Config::set("aws-ses-bounce-complaint-handler.via_sqs", false);

        $this->postJson('amazon-sns/notifications', $this->getAWSBounceData())
            ->assertSuccessful();

        $this->assertDatabaseHas((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    /** @test */
    public function it_is_protected_by_secret_on_https_call(): void
    {
        \Config::set("aws-ses-bounce-complaint-handler.route_secret", "someSecret");
        \Config::set("aws-ses-bounce-complaint-handler.via_sqs", false);

        $this->postJson('amazon-sns/notifications', $this->getAWSBounceData())
            ->assertForbidden();

        $this->assertDatabaseMissing((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    /** @test */
    public function it_is_not_forbidden_when_password_is_set_and_via_sqs(): void
    {
        \Config::set("aws-ses-bounce-complaint-handler.route_secret", "someSecret");
        \Config::set("aws-ses-bounce-complaint-handler.via_sqs", true);

        $this->postJson('amazon-sns/notifications', $this->getAWSBounceData())
            ->assertStatus(422);

        $this->assertDatabaseMissing((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    /** @test */
    public function it_is_protected_by_secret_and_passes_on_https_call(): void
    {
        \Config::set("aws-ses-bounce-complaint-handler.route_secret", "someSecret");
        \Config::set("aws-ses-bounce-complaint-handler.via_sqs", false);

        $this->postJson('amazon-sns/notifications?secret=someSecret', $this->getAWSBounceData())
            ->assertSuccessful();

        $this->assertDatabaseHas((new WrongEmail())->getTable(), ['email' => 'bounce@simulator.amazonses.com']);
    }

    /**
     * @return array
     */
    private function getAWSBounceData(): array
    {
        $requestData = '{
   "notificationType":"Bounce",
   "bounce":{
      "bounceType":"Permanent",
      "bounceSubType":"General",
      "bouncedRecipients":[
         {
            "emailAddress":"bounce@simulator.amazonses.com",
            "action":"failed",
            "status":"5.1.1",
            "diagnosticCode":"smtp; 550 5.1.1 user unknown"
         }
      ],
      "timestamp":"2017-09-05T02:30:25.645Z",
      "feedbackId":"0101015e4fdfe031-818e3e45-3db7-4546-8bbf-6da232afc8e9-000000",
      "remoteMtaIp":"207.171.163.188",
      "reportingMTA":"dsn; a27-24.smtp-out.us-west-2.amazonses.com"
   },
   "mail":{
      "timestamp":"2017-09-05T02:30:24.000Z",
      "source":"bounce111@eder.com.au",
      "sourceArn":"arn:aws:ses:us-west-2:154131996403:identity/eder.com",
      "sourceIp":"54.240.230.242",
      "sendingAccountId":"154131996403",
      "messageId":"0101015e4fdfdb7f-a3dcaf07-8fb3-459f-9343-7859c0398c3f-000000",
      "destination":[
         "bounce@simulator.amazonses.com"
      ],
      "headersTruncated":false,
      "headers":[
         {
            "name":"From",
            "value":"bounce111@eder.com.au"
         },
         {
            "name":"To",
            "value":"bounce@simulator.amazonses.com"
         },
         {
            "name":"Subject",
            "value":"bounce@simulator.amazonses.com"
         },
         {
            "name":"MIME-Version",
            "value":"1.0"
         },
         {
            "name":"Content-Type",
            "value":"text/plain; charset=UTF-8"
         },
         {
            "name":"Content-Transfer-Encoding",
            "value":"7bit"
         }
      ],
      "commonHeaders":{
         "from":[
            "bounce111@eder.com.au"
         ],
         "to":[
            "bounce@simulator.amazonses.com"
         ],
         "subject":"bounce@simulator.amazonses.com"
      }
   }
}';

        try {
            return json_decode($requestData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }
    }

}
