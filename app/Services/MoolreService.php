<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoolreService
{


    public function sendMessage(string $recipient, string $message)
    {
        $apiKey = config('services.moolre.api_vaskey');
        $url = config('services.moolre.api_url');
        $senderId = config('services.moolre.senderid');
        $feedback = Http::withHeaders(
            [
                'X-API-VASKEY' => $apiKey,
                'Content-Type' => 'application/json',
            ]
        )->post($url, [
            'type' => 1,
            'senderid' => $senderId,
            'messages' => [
                [
                    'recipient' => $recipient,
                    'message' => $message
                ]
            ]
        ]);

        return $feedback;
    }
}
