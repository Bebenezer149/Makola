<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{

    public function createAccount(string $business_name, string $channel, string $account_number)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '. config('services.paystack.api_secret'),
            'Content-Type' => 'application/json'
        ])->post('https://api.paystack.co/subaccount',[
            'business_name'=>$business_name,
            'settlement_bank'=>$channel,
            'account_number'=>$account_number,
            'percentage_charge'=>20
        ]);


        if (!$response->successful()) {
            return response()->json([
                'message' => "again"
            ],433);
        }

        return $response->json();
    }

}
