<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoolreService
{

   public static function sendMessage(string  $phone, string $message){
        $response=Http::withHeaders([
            'X-API-VASKEY'=>env('MOOLRE_API_VASKEY'),
            'Content-Type'=>'application/json'
        ])->post('https://api.moolre.com/open/sms/send',[
            'type'=>1,
            'senderid'=>'BlueSpace',
            'network'=>"MTN",
            "messages"=>[
                [
                    'recipient'=>$phone,
                    'message'=>$message
                ]
            ]
        ]);

        if(!$response->successful()){
            return  response()->json([
                'message=>"Error'
            ],433);
        }

        return $response;
    }
}
