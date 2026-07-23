<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    //
    public function sendResetLink(Request $request){
        $user=$request->validate([
            'email'=>'required|string'
        ]);

        $status=Password::sendResetLink($request->only('email'));

        if($status === Password::RESET_LINK_SENT){
            return response()->json([
                'success'=>true,
                'message'=>'Password link sent'

            ]);
        }

        return response()->json([
            'success'=>false,
            'message'=>__($status)
        ]);
    }
}
