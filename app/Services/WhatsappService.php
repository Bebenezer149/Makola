<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function sendMessage(
        string $recipientPhoneNumber,
        string $message
    ) {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        return Http::withToken($accessToken)
            ->acceptJson()
            ->post(
                "https://graph.facebook.com/v25.0/{$phoneNumberId}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhoneNumber,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]
            );
    }
}