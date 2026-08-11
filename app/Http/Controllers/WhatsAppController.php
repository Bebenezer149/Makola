<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');
        $verifyToken = config('services.whatsapp.verify_token');

        if (
            $mode === 'subscribe' && $token === $verifyToken
        ) {
            Log::info('WhatsApp Webhook Verified.', ['challenge' => $challenge]);
            return response($challenge, 200);
        }

        Log::warning('WhatsApp Webhook Verification Failed.', [
            'received_token' => $token,
            'expected_token' => $verifyToken,
        ]);
        return response('Forbidden', 403);
    }

    public function webhook(Request $request)
    {
        Log::info('WhatsApp Webhook Received', $request->all());

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function sendMessage(WhatsAppService $whatsApp)
    {
        $response = $whatsApp->sendMessage(
            '233539278827',
            'Hello From Blue Space Testing area'
        );

        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json(),
        ]);
    }
}