<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function isConfigured(): bool
    {
        return (string) env('TWILIO_ACCOUNT_SID', '') !== ''
            && (string) env('TWILIO_AUTH_TOKEN', '') !== ''
            && (string) env('TWILIO_FROM', '') !== '';
    }

    /**
     * Envoi SMS via Twilio (si configuré), sinon false.
     */
    public function send(string $to, string $message): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $sid = (string) env('TWILIO_ACCOUNT_SID');
        $token = (string) env('TWILIO_AUTH_TOKEN');
        $from = (string) env('TWILIO_FROM');

        try {
            $res = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if (! $res->successful()) {
                Log::warning('sms.twilio_failed', [
                    'to' => $to,
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('sms.twilio_exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

