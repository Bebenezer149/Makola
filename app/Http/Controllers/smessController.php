<?php

namespace App\Http\Controllers;

use App\Services\MoolreService;
use App\Services\SmessService;
use Illuminate\Http\Request;

class smessController extends Controller
{
    //
    public function Test(Request $request){
        $sms=app(SmessService::class);
        $response = $sms->sendMessage($request->recipient, $request->message);

        if ($response->successful()) {
            return response()->json([
                'message' => 'Message sent successfully',
                'data' => $response->json(),
            ]);
        }

        return response()->json([
            'message' => 'Failed to send message',
            'error' => $response->json() ?? $response->body(),
        ], $response->status());
    }

    public function TestSms(Request $request){
        $sms=new MoolreService();
        
        $response=$sms->sendMessage($request->recipient, $request->message);

        return response($response);

    }
}
