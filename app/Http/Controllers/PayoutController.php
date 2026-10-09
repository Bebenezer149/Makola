<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    //
    public function verifyName(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => "string|required|max:10",
            'channel' => "string|required"
        ]);

        $verify = app(PaymentService::class);

        $response = $verify->verifyName($validated['phone_number'], $validated['channel']);
        return response()->json([
            'message' => "Success",
            "data" => $response
        ]);
    }
    public function initiateTransfer(Request $request)
    {
        $validated = $request->validate([

            'amount' => 'required|numeric|gt:0|decimal:0,2',
            'receiver' => 'required|string|max:32',
            'channel' => 'required|string|max:50',
        ]);

        $payout = app(PaymentService::class);
        $response=$payout->initiateTransfer($validated['amount'],$validated['channel'],$validated['receiver']);

        return $response;
    }
}
