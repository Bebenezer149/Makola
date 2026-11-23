<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
    public function start(Request $request)
    {
        $validated = $request->validate([
            'order' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::findOrFail($validated['order']);

        return app(PaymentService::class)->createPayment($order);
    }

    public function receiveCallback(Request $request)
    {
        $data = $request->all();

        $reference = $data['data']['externalref'] ?? null;
        $status = $data['data']['txstatus'] ?? null;

        if (!$reference) {
            return response()->json([
                'message' => 'Could not find reference',
            ], 400);
        }

        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Could not find payment',
            ], 404);
        }

        $order = Order::find($payment->order_id);

        if (!$order) {
            return response()->json([
                'message' => "Couldn't find order",
            ], 404);
        }

        $paymentService = app(PaymentService::class);

        if ((string) $status === '1') {
            $paymentService->markPaymentPaid($payment);
            $payment->refresh();
            return response()->json([
                'message' => 'Payment and order updated successfully',
                'payment' => $payment,
            ]);
        }

        if ((string) $status === '3') {
            $paymentService->markPaymentFailed($payment);
            $payment->refresh();

            return response()->json([
                'message' => $payment->payment_status === 'FAILED'
                    ? 'Payment failed'
                    : 'Payment has already been confirmed',
                'payment' => $payment,
            ]);
        }

        return response()->json([
            'message' => 'Payment status received',
            'payment_status' => $payment->payment_status,
        ]);
    }
    public function checkStatus(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string'],
        ]);

        $checker = app(PaymentService::class);
        $data = $checker->checkStatus($validated['reference']);


        return $data;
    }
}
