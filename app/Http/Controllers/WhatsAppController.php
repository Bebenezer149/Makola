<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    //
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if (
            $mode === 'subscribe' &&
            $token === config('services.whatsapp.verify_token')
        ) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function webhook(Request $request)
    {
        Log::info('WhatsApp Webhook Received', $request->all());

        return response()->json([
            'success' => true,
        ], 200);
    }
}
