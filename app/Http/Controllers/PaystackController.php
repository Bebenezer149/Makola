<?php

namespace App\Http\Controllers;

use App\Services\PaystackService;
use Illuminate\Http\Request;

class PaystackController extends Controller
{
    //
    public function createAccount(Request $request, PaystackService $paystack){
            $validate=$request->validate([
                'business_name'=>'string|required',
                'channel'=>"string|required",
                'account_number'=>'string|required'
            ]);

          $feedback= $paystack->createAccount($validate['business_name'],$validate['channel'],$validate['account_number']);

          return response()->json([
            'message'=>"Account is created",
            'account'=>$feedback
          ]);
    }
}
