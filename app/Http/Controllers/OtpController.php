<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MoolreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OtpController extends Controller
{
    //
    public function sendOtp(Request $request)
    {
        $validate =  $request->validate([
            'phone' => 'string|required|max:14'
        ]);

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->phone, $otp, now()->addMinutes(10));
         $message="Your OTP is " . $otp . ". Do not share it with anyone.";
        $response = MoolreService::sendMessage($validate['phone'],$message );

        return response()->json([
            "message" => "Otp sent successfully",
            "response" => $response->json()
        ], 200);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
            'phone' => 'required'
        ]);

        $cachedOtp = Cache::get('otp_' . $request->phone);

        if (!$cachedOtp) {
            return response()->json([
                "message" => "Otp has expired please try again"
            ]);
        } elseif ((int)$request->otp !== $cachedOtp) {
            return response()->json([
                "message" => "Otp is invalid please try again"
            ]);
        } else {

            $user = User::where('phone_number', $request->phone)->first();
            $user->update([
                'is_verified' => true
            ]);

            return response()->json([
                "message" => "Otp verified successfully",
                "status" => $user->is_verified
            ]);
            $cachedOtp = Cache::forget('otp_' . $request->phone);
        }
    }
}
