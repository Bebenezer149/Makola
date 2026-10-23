<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OtpController extends Controller
{
    //
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:10,14'
        ]);

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->phone_number, $otp, now()->addMinutes(10));

        $response = Http::withHeaders([
            'X-API-VASKEY' => config('services.moolre.api_vaskey'),
            'Content-Type' => "application/json"
        ])->post("https://sandbox.moolre.com/open/sms/send", [
            "type" => 1,
            "senderid" => "BlueSpace",
            "messages" => [
                [
                    "recipient" => $request->phone_number,
                    "message" => "Your OTP is " . $otp . ". Do not share it with anyone.",

                ]
            ]
        ]);

        if ($response->successful()) {
            return response()->json([
                "message" => "Otp sent successfully",
                "response" => $response->json()
            ], 200);
        } else {
            return response()->json([
                "message" => "An error occurred"
            ]);
        }
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
            'phone_number' => 'required'
        ]);

        $cachedOtp = Cache::get('otp_' . $request->phone_number);

        if (!$cachedOtp) {
            return response()->json([
                "message" => "Otp has expired please try again"
            ]);
        } elseif ((int)$request->otp !== $cachedOtp) {
            return response()->json([
                "message" => "Otp is invalid please try again"
            ]);
        } else {

            $user = User::where('phone_number', $request->phone_number)->first();
            $user->update([
                'is_verified'=>true
            ]);

            return response()->json([
                "message" => "Otp verified successfully",
                "status" => $user->is_verified
            ]);
            $cachedOtp = Cache::forget('otp_' . $request->phone_number);
        }
    }
}
