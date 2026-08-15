<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SmessService
{

    private $apiKey;
    private $url;

    public function __construct()
    {
        $this->apiKey = config('services.smess.api_key');
        $this->url = config('services.smess.smess_url');
    }
    public function sendMessage(string $recipient, string $message)
    {


        $feedback = Http::withHeaders([
            'X-API_Key' => $this->apiKey,
            'Accept'=>'application/json',
            
        ])->post($this->url, [
            'recipient' => $recipient,
            'text' => $message
        ]);

        return $feedback;
    }
}
