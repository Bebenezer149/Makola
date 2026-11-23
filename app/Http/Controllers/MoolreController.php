<?php

namespace App\Http\Controllers;

use App\Services\MoolreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MoolreController extends Controller
{
    //
    public function sendSms(Request $request)
    {
      $validate=$request->validate([
        'phone'=>'string|max:14|min:10|required',
        'message'=>"string|required"
      ]);

      $response=MoolreService::sendMessage($validate['phone'], $validate['message']);

      return response()->json([
        'message'=>"Here",
       
      ]);

     
    }
}
