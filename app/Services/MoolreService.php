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
                'X-API-VASKEY' => env('MOOLRE_API_VASKEY'),
                'Content-Type' => 'application/json',
            ]
        )->post("https://api.moolre.com/open/sms/send", [
            'type' => 1,
            'senderid' => "BlueSpace",
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
