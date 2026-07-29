<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    //

    public function registerVendor(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',

        ]);

        $link= Str::slug($validated['business_name']);
        $count=1;

        $originalLink=$link;

        while(User::where('link',$link )->exists()){
            $link=$originalLink.'-'.$count;
            $count++;
        }


        $validated['password'] = Hash::make(
            $validated['password']
        );

        $user = User::create([
            'link'=>$link,
            ...$validated
        ]);

        $token = $user->createToken('vendor-token')->plainTextToken;
        return response()->json([
            'message' => 'User Created Successfully',
            'user'=>$user,
            'token' => $token
        ]);
    }

    public function loginVendor(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'message' => 'Invalid credentials',

            ], 401);
        }

        $token = $user->createToken('vendor-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

 public function logoutVendor(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    $user->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logged out successfully'
    ]);
}

public function updateUser(Request $request){
    try{
        $user=$request->user();

        if(!$user){
             return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
        }

    $validated=$request->validate([
        'profile_picture'=>'string|nullable',
        'banner'=>'nullable|string'
    ]);
    $user->update($validated);

    return response()->json([
        'message'=>"User updated successfully",
        'data'=>$user
    ]);
    }
    catch(\Exception $e){
        return response()->json([
            "message"=>"Unable to update user",
            'error'=>$e
        ]);
    };

    
}

public function fetchUser(Request $request){
    $check=$request->user();
    if(!$check){
        response()->json([
            'message'=>"Unauthenticated"
            ],401);
    }

    $user=User::find($check->id);
    
    return response()->json([
        "message"=>"User retrieeved",
        "user"=>$user
    ]);
}
}
