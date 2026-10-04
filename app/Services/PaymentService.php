<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(int $order, int|float|string $amount)
    {
        // Generate reference
        $reference = "BS-{$order}-" . strtoupper(Str::random(6));


        // Make call to generate payment link
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
                'callback' => 'https://makola-pzk5.onrender.com/api/moolre/callback',
                'reusable' => '0',
            ]
        );

        // If call is success create payment in DB and return response
        if ($feedback->successful()) {
            $payment = Payment::create([
                'order_id' => $order,
                'amount' => $amount,
                "reference" => $reference,
                "payment_status" => "PENDING"
            ]);

            return response()->json([
                'moolre_status' => $feedback->status(),
                'moolre_response' => $feedback->json(),
                'reference' => $reference,
            ]);
        }
        // If it fails bring me the error
        else {
            return response()->json([
                'message' => "Something went wrong"
            ]);
        }
    }

    // ------ Checking Status and updating payments and order

    public function checkStatus(string $reference)
    {

        $feedback = Http::withHeaders([
            'X-API-USER' => 'bluespace0fficial',
            'Content-Type' => 'application/json',
        ])->post('https://sandbox.moolre.com/open/transact/status', [
            'type' => 1,
            'idtype' => 1,
            'id' => $reference,
            "accountnumber" => env('ACCOUNTNUMBER')
        ]);

        if (!$feedback->successful()) {
            return response()->json([
                "message" => "Something went wrong"
            ], 502);
        }

        $status = $feedback->json('data.txstatus');
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        if (in_array($status, [1, '1'], true)) {
            $order = Order::find($payment->order_id);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found',
                ], 404);
            }

            DB::transaction(function () use ($payment, $order) {
                $payment->update(['payment_status' => 'PAID']);
                $order->update(['status' => 'PENDING']);
            });

            return response()->json([
                'message' => $feedback->json(),
                'payment_status' => 'PAID',
                'order'=>$order,
                'redirect'=>"/success"
            ]);
        }

        if (in_array($status, [3, '3'], true)) {
            $payment->update(['payment_status' => 'FAILED']);

            return response()->json([
                'message' => 'Payment failed',
                'payment_status' => 'FAILED',
                'redirect'=>"/failed",
            ]);
        }
        if (in_array($status, [0, '0'], true)) {
            $payment->update(['payment_status' => 'PENDING']);

            return response()->json([
                'message' => 'Payment pending',
                'payment_status' => $payment->payment_status,
                 'redirect'=>"/pending",
                
            ]);
        }
        return response()->json([
            'message' => 'Payment is still pending',
            'payment_status' => "PAID",
        ]);
    }

    // public function verifyPayment(string $reference)
    // {
    //     $payment = Payment::where('reference', $reference)->first();
    //     $order = Order::where('id', $payment->order_id)->first();

    //     if ($payment->status === "PAID") {
    //         return response()->json([
    //             'message' => "Payment is already completed",
    //             "status" => $payment->status
    //         ], 200);
    //     } elseif ($payment->status === "DISPUTED") {
    //         return response()->json([
    //             'message' => "Payment Flagged for investigation",
    //             'status' => $payment->status
    //         ], 200);
    //     } elseif ($payment->status === "REFUNDED") {
    //         return response()->json([
    //             'message' => "Money has been refunded",
    //             'status' => $payment->status
    //         ], 200);
    //     } else {
    //         $order->update([
    //             'status' => "PENDING"
    //         ]);
    //     }
    // }
}
