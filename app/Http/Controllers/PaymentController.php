<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\MoolreService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //
    public function start(Request $request){
        $pay=app(PaymentService::class);

        $data=$pay->createPayment($request->order,$request->amount);
        return $data;
    }
    
     public function receiveCallback(Request $request){
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

        if ((int) $status === 1) {
            $payment->update(['payment_status' => 'PAID']);
            $order->update(['status' => 'PENDING']);
            $vendor = User::find($order->vendor_id);
            if ($vendor) {
                $sms = app(MoolreService::class);
                $sms->sendMessage($vendor->phone_number, "You have a new order from " . $order->customer_name . " to attend to. Kindly visit your dashboard at https://blue-space-gh.vercel.app/login to review order ");
            }

            return response()->json([
                'message' => 'Payment and order updated successfully',
                'payment'=>$payment
            ]);
        }

        $payment->update(['payment_status' => 'FAILED']);
        $order->update(['status' => 'CANCELLED']);

        return response()->json([
            'message' => 'Payment failed',
            'payment'=>$payment
        ]);
    }
    public function checkStatus(Request $request){
        $validated = $request->validate([
            'reference' => ['required', 'string'],
        ]);

        $checker=app(PaymentService::class);
        $data=$checker->checkStatus($validated['reference']);
        

        return $data;
    }
}
