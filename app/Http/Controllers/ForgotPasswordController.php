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
        $request->validate([
            'email'=>'required|email'
        ]);

        try {
            $status=Password::sendResetLink($request->only('email'));

            if($status === Password::RESET_LINK_SENT){
                return response()->json([
                    'success'=>true,
                    'message'=>'If an account exists for this email, a reset link has been sent.'
                ]);
            }

            return response()->json([
                'success'=>true,
                'message'=>'If an account exists for this email, a reset link has been sent.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Password reset email failed.', ['exception' => $e]);

            return response()->json([
                'success'=>false,
                'message'=>'Unable to send a reset link right now. Please try again later.'
            ], 500);
        }
    }
}
