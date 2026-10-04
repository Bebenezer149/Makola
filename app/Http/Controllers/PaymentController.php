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
    
    //  public function receiveCallback(Request $request){
    //     $data=$request->all();
    //     $status=$request['original.message'];
    //     return response()->json([

    //         'status'=>"Success",
    //         'data'=>$data
    //     ]);
    // }
    public function checkStatus(Request $request){
        $validated = $request->validate([
            'reference' => ['required', 'string'],
        ]);

        $checker=app(PaymentService::class);
        $data=$checker->checkStatus($validated['reference']);
        

        return $data;
    }
}
