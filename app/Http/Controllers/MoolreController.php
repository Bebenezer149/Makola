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
      $sms=app(MoolreService::class);

      $sms->sendMessage($request->recipient, $request->message);

      return $sms;
    }
}
