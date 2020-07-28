<?php

namespace ag84ark\AwsSesBounceComplaintHandler;

use ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail;
use Illuminate\Support\Collection;

class AwsSesBounceComplaintHandler
{
    public function canSendToEmail(string $email): bool
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

    public function ignoreEmail(string $email): void
    {
        /** @var WrongEmail[]|Collection $emails */
        $emails = WrongEmail::where('email', '=', $email)->get();

        foreach ($emails as $wrongEmail) {
            $wrongEmail->ignore = true;
            $wrongEmail->save();
        }
    }

    public function clearEmail(string $email): void
    {
        /* @var WrongEmail[]|Collection $emails */
        WrongEmail::where('email', '=', $email)->delete();
    }
}
