<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function isConfigured(): bool
    {
        return (bool) app('settings')->get('sms.gateway.url') && (bool) app('settings')->get('sms.gateway.token');
    }

    /**
     * @param array<int,string> $phones
     */
    public function send(array $phones, string $message): bool
    {
        $url = (string) app('settings')->get('sms.gateway.url', '');
        $token = (string) app('settings')->get('sms.gateway.token', '');
        $sender = (string) app('settings')->get('sms.sender', '');

        if ($url === '' || $token === '' || empty($phones) || $message === '') {
            return false;
        }

        // Generic HTTP JSON gateway
        $payload = [
            'sender' => $sender,
            'to' => array_values(array_unique(array_filter($phones))),
            'message' => $message,
        ];

        $response = Http::withToken($token)->post($url, $payload);

        return $response->successful();
    }
}
