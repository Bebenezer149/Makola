<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    //
    public function sendResetLink(Request $request){
        $user=$request->validate([
            'email'=>'required|string'
        ]);

        try {
            $status=Password::sendResetLink($request->only('email'));

            if($status === Password::RESET_LINK_SENT){
                Log::info('Password reset link sent successfully to: '.$request->email);
                return response()->json([
                    'success'=>true,
                    'message'=>'Password link sent'
                ]);
            }

            return response()->json([
                'success'=>false,
                'message'=>__($status)
            ]);
        } catch (\Throwable $e) {
            Log::error('Password reset email failed: '.$e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success'=>false,
                'message'=>'Mail delivery failed: '.$e->getMessage()
            ], 500);
        }
    }
}
