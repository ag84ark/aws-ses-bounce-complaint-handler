<?php

namespace ag84ark\AwsSesBounceComplaintHandler;

use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Log;

class AwsSesBounceComplaintHandler
{
    public static function test(): string
    {
        return 'It Works!';
    }

    public function handleBounceOrComplaint(Request $request): JsonResponse
    {
        if (! $request->json()) {
            return Response::json(['status' => 422, 'message' => 'error'], 422);
        }

        if (! $this->canPass($request)) {
            return Response::json(['status' => 403, 'message' => 'error'], 403);
        }

        $data = $request->json()->all() ?? [];

        $this->handleLoggingData($data);

        $this->handleSubscriptionConfirmation($request, $data);

        $message = $this->getMessageData($request, $data);

        if (! count($message)) {
            return Response::json(['status' => 422, 'data' => $data], 422);
        }

        if (! isset($message['notificationType'])) {
            return Response::json(['status' => 200, 'message' => 'no data']);
        }

        $this->storeEmailToDB($message);

        return Response::json(['status' => 200, 'message' => 'success']);
    }

    public static function canSendToEmail(string $email): bool
    {
        /** @var WrongEmail[]|Collection $emails */
        $emails = WrongEmail::active()
            ->bounced()
            ->where('email', '=', $email)
            ->get();

        foreach ($emails as $wrongEmail) {
            if (! $wrongEmail->canBouncedSend()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Request $request
     * @param array $data
     * @return array|mixed|\Symfony\Component\HttpFoundation\ParameterBag|null
     */
    private function getMessageData(Request $request, array $data)
    {
        $message = [];

        if (config('aws-ses-bounce-complaint-handler.via_sqs')) {
            if ($request->json('Type') === 'Notification') {
                $message = $request->json('Message');
            }
        } else {
            $message = $data;
        }

        return $message;
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function canPass(Request $request): bool
    {
        if (config('aws-ses-bounce-complaint-handler.via_sqs')) {
            return  true;
        }

        $secret = config('aws-ses-bounce-complaint-handler.route_secret');
        if (! $secret) {
            return  true;
        }

        if ($secret !== $request->get('secret')) {
            return  false;
        }

        return true;
    }

    /**
     * @param Request $request
     * @param array $data
     */
    private function handleSubscriptionConfirmation(Request $request, array $data): void
    {
        if ($request->json('Type') !== 'SubscriptionConfirmation') {
            return;
        }
        Log::info('SubscriptionConfirmation came at: ' . $data['Timestamp']);

        $client = new Client();
        $res = $client->get($data['SubscribeURL']);

        if ($res->getStatusCode() === 200) {
            Log::info("SubscriptionConfirmation was auto confirmed!");
        } else {
            Log::warning("SubscriptionConfirmation could not be auto confirmed!");
            Log::info($data['SubscribeURL']);
        }
    }

    /**
     * @param $message
     */
    private function storeEmailToDB($message): void
    {
        switch ($message['notificationType']) {

            case 'Bounce':
                $bounce = $message['bounce'];
                $subtype = $bounce['bounceType'];
                foreach ($bounce['bouncedRecipients'] as $bouncedRecipient) {
                    $emailAddress = $bouncedRecipient['emailAddress'];

                    $emailRecord = WrongEmail::firstOrCreate(['email' => $emailAddress, 'problem_type' => 'Bounce', 'problem_subtype' => $subtype]);
                    if ($emailRecord) {
                        $emailRecord->increment('repeated_attempts', 1);
                    }
                }

                break;

            case 'Complaint':
                $complaint = $message;
                $subtype = $complaint['complaintFeedbackType'] ?? '';
                foreach ($complaint['complainedRecipients'] as $complainedRecipient) {
                    $emailAddress = $complainedRecipient['emailAddress'];
                    $emailRecord = WrongEmail::firstOrCreate(['email' => $emailAddress, 'problem_type' => 'Complaint', 'problem_subtype' => $subtype]);
                    if ($emailRecord) {
                        $emailRecord->increment('repeated_attempts', 1);
                    }
                }

                break;

            default:
                // Do Nothing
                break;

        }
    }

    /**
     * @param array $data
     */
    private function handleLoggingData(array $data): void
    {
        if (config('aws-ses-bounce-complaint-handler.log_requests')) {
            $dataCollection = collect($data);
            Log::info('Logging AWS SES DATA');
            Log::info($dataCollection->toJson());
        }
    }
}
