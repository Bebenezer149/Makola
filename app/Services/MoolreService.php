<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MoolreService
{
    public function sendMessage(string $recipient, string $message): array
    {
        if (empty($recipient) || ! preg_match('/^\+?[0-9]{10,14}$/', $recipient)) {
            throw new \InvalidArgumentException('Invalid recipient phone number.');
        }

        if (trim($message) === '') {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }

        $apiKey = config('services.moolre.api_vaskey');
        $url = config('services.moolre.api_url');
        $senderId = config('services.moolre.senderid');

        if (empty($apiKey) || empty($url) || empty($senderId)) {
            throw new \RuntimeException('Moolre SMS configuration is missing.');
        }

        $response = Http::withHeaders([
            'X-API-VASKEY' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'type' => 1,
            'senderid' => $senderId,
            "network"=> "TELECEL",
            'messages' => [
                [
                    'recipient' => $recipient,
                    'message' => $message,
                ],
            ],
            'ref' => 'BS-' . Str::random(8),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Moolre SMS sending failed: ' . $response->body());
        }

        return $response->json();
    }
}
