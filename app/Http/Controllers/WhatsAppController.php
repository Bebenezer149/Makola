<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    //
  public function verify(Request $request)
{
    return response()->json([
        'all' => $request->all(),
        'mode' => $request->query('hub.mode'),
        'token' => $request->query('hub.verify_token'),
        'challenge' => $request->query('hub.challenge'),
        'expected_token' => config('services.whatsapp.verify_token'),
    ]);
}

    public function webhook(Request $request)
    {
        Log::info('WhatsApp Webhook Received', $request->all());

        return response()->json([
            'success' => true,
        ], 200);
    }
}
