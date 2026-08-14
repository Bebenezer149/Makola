<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {

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
    $user=$request->user();
    $validated=$request->validate([
        'profile_picture'=>'nullable|url|max:2048',
        'banner'=>'nullable|url|max:2048'
    ]);
    $user->update($validated);

    return response()->json([
        'message'=>"User updated successfully",
        'data'=>$user
    ]);
}

public function fetchUser(Request $request){
    $user = $request->user();
    if (!$user) {
        return response()->json([
            'message'=>"Unauthenticated"
            ],401);
    }
    
    return response()->json([
        "message"=>"User retrieeved",
        "user"=>$user
    ]);
}



}
