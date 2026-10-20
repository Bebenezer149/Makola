<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    //
    public function fetchPaymentHistory(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $payments = Payment::where('vendor_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'success',
            'payments' => $payments,
            
        ]);
    }

    public function fetchBalance(Request $request){
        $user=$request->user();

        $payoutTotal=Payout::where("vendor_id",$user->id)->where("payout_status","RELEASED")->get()->sum('payout_amount');

        $pendingPayment=Payment::where("vendor_id",$user->id)->where("payment_status","PENDING")->get()->sum('amount');
        return response()->json([
            'total_payouts'=>number_format($payoutTotal,'2','.',','),
            "pending_payments"=>number_format($pendingPayment,'2','.',',')
        ]);
    }
}
