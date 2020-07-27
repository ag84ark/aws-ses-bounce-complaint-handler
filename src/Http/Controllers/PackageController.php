<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Http\Controllers;

use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class PackageController
{
    protected $data = [];
    protected $message = [];
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client ?: new Client();
    }

    public function handleBounceOrComplaint(Request $request): JsonResponse
    {
        if (! $request->json()) {
            return Response::json(['status' => 422, 'message' => 'error'], 422);
        }

        if (! $this->canPass($request)) {
            return Response::json(['status' => 403, 'message' => 'error'], 403);
        }

        $this->data = $request->json()->all() ?? [];

        $this->handleLoggingData();

        if ($info = $this->handleSubscriptionConfirmation()) {
            return Response::json(['status' => 200, 'message' => $info]);
        }

        $this->getMessageData();

        if (! count($this->message)) {
            return Response::json(['status' => 422, 'data' => $this->data], 422);
        }

        if (! isset($this->message['notificationType'])) {
            return Response::json(['status' => 200, 'message' => 'no data']);
        }

        $this->storeEmailToDB();

        return Response::json(['status' => 200, 'message' => 'success']);
    }

    private function canPass(Request $request): bool
    {
        if (config('aws-ses-bounce-complaint-handler.via_sqs')) {
            return true;
        }

        $secret = config('aws-ses-bounce-complaint-handler.route_secret');
        if (! $secret) {
            return true;
        }

        if ($secret !== $request->get('secret')) {
            return false;
        }

        return true;
    }

    private function handleLoggingData(): void
    {
        if (config('aws-ses-bounce-complaint-handler.log_requests')) {
            $dataCollection = collect($this->data);
            Log::info('Logging AWS SES DATA');
            Log::info($dataCollection->toJson());
        }
    }

    private function handleSubscriptionConfirmation(): ?string
    {
        try {
            if (! isset($this->data['Type']) || 'SubscriptionConfirmation' !== $this->data['Type'] || ! config('aws-ses-bounce-complaint-handler.auto_subscribe')) {
                return null;
            }
            Log::info('SubscriptionConfirmation came at: '.$this->data['Timestamp']);

            $res = $this->client->get($this->data['SubscribeURL']);

            if (200 === $res->getStatusCode()) {
                $message = 'SubscriptionConfirmation was auto confirmed!';
                Log::info($message);
            } else {
                $message = 'SubscriptionConfirmation could not be auto confirmed!';
                Log::warning($message);
                Log::info($this->data['SubscribeURL']);
            }

            return $message;
        } catch (\Exception $exception) {
            return  $exception->getMessage();
        }
    }

    private function getMessageData(): void
    {
        if (config('aws-ses-bounce-complaint-handler.via_sqs')) {
            if ('Notification' === $this->data['Type']) {
                $this->message = $this->data['Message'];
            }
        } else {
            $this->message = $this->data;
        }
    }

    private function storeEmailToDB(): void
    {
        $message = $this->message;

        switch ($message['notificationType']) {
            case 'Bounce':
                $bounce = $message['bounce'];
                $this->saveBouncedEmailsToDB($bounce);

                break;

            case 'Complaint':
                $complaint = $message['complaint'];
                $this->saveComplainedEmailsToDB($complaint);

                break;

            default:
                // Do Nothing
                break;
        }
    }

    /**
     * @param $bounce
     */
    private function saveBouncedEmailsToDB($bounce): void
    {
        $subtype = $bounce['bounceType'];
        foreach ($bounce['bouncedRecipients'] as $bouncedRecipient) {
            $emailAddress = $bouncedRecipient['emailAddress'];

            $emailRecord = WrongEmail::firstOrCreate(['email' => $emailAddress, 'problem_type' => 'Bounce', 'problem_subtype' => $subtype]);
            if ($emailRecord) {
                $emailRecord->increment('repeated_attempts', 1);
            }
        }
    }

    /**
     * @param $complaint
     */
    private function saveComplainedEmailsToDB($complaint): void
    {
        $subtype = $complaint['complaintFeedbackType'] ?? '';
        foreach ($complaint['complainedRecipients'] as $complainedRecipient) {
            $emailAddress = $complainedRecipient['emailAddress'];
            $emailRecord = WrongEmail::firstOrCreate(['email' => $emailAddress, 'problem_type' => 'Complaint', 'problem_subtype' => $subtype]);
            if ($emailRecord) {
                $emailRecord->increment('repeated_attempts', 1);
            }
        }
    }
}
