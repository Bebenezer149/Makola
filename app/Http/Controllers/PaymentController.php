<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
    public function start(Request $request){
        $pay=app(PaymentService::class);

        $data=$pay->createPayment($request->order,$request->amount);
        return $data;
    }
    
     public function receiveCallback(Request $request){
        $data=$request->all();

        return response()->json([
            'status'=>'Success',
            'data'=>$data
        ]);
    }
}
