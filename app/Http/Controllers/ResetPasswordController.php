<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    //

    public function resetPassword(Request $request){
        $request->validate([
            'token'=>'required',
            'email'=>'required|string',
            'password'=>'required|string|min:8|confirmed'
        ]);

        $status=Password::reset($request->only([
            'email',
            'password',
            'password_confirmation',
            'token'
        ]),
        function (User $user, string $password){
            $user->forceFill([
                'password'=>Hash::make($password),
                'remember_token'=>Str::random(60)
                
            ])->save();
            event(new PasswordReset($user));
        }
        );
        if($status  === Password::PASSWORD_RESET)
            {
                return response()->json([
                    'success'=>true,
                    'message'=>__($status)
                ]);
            }

        return response()->json([
            'success'=>false,
            'message'=>__($status)
        ],400);
    }
}
