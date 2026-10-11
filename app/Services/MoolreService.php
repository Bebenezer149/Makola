<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Str;

class MoolreService
{


    public function sendMessage(string $recipient, string $message)
    {
        $apiKey = config('services.moolre.api_vaskey');
        $url = config('services.moolre.api_url');
        $senderId = config('services.moolre.senderid');
        $reference='BS-'.Str::random(8);
        $feedback = Http::withHeaders(
            [
                'X-API-VASKEY' => $apiKey,
                'Content-Type' => 'application/json',
            ]
        )->post($url, [
            'type' => 1,
            'senderid' => $senderId,
            "network"=> "MTN",
            'messages' => [
                [
                    'recipient' => $recipient,
                    'message' => $message
                ]
            ],
            'ref'=>$reference
        ]);

        return response()->json([
            'response'=>$feedback->json()
        ]);
    }
}
