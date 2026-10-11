<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoolreService
{

    public static function sendMessage(string $phone, string $message)
    {
        $response = Http::get(
            'https://api.moolre.com/open/sms/send?type=1&network=TELECEL&X-API-VASKEY='.config('services.moolre.api_vaskey').'&recipient='.$phone.'&message='.$message
        );

        if($response->successful()){
            return response()->json([
                'message'=>'sent',
                'data'=>$response->json()
            ]);
        }
             return response()->json([
                'message'=>'could not send',
                'data'=>$response->json()
            ]);
        }
}
