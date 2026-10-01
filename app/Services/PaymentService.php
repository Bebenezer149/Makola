<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(int $order, int $amount)
    {
        $reference = "BS-{$order}-" . strtoupper(Str::random(6));

        $feedback = Http::withHeaders([
            'X-API-USER' => 'bluespace0fficial',
            'Content-Type' => 'application/json',
        ])->post(
            'https://sandbox.moolre.com/embed/link',
            [
                'type' => 1,
                'amount' => $amount,
                'currency' => 'GHS',
                'accountnumber' => env('ACCOUNTNUMBER'),
                'email' => env('MOOLRE_EMAIL'),
                'externalref' => $reference,
                'reusable' => '0',
            ]
        );

        return response()->json([
            'moolre_status' => $feedback->status(),
            'moolre_response' => $feedback->json(),
            'reference' => $reference,
        ]);
    }
}