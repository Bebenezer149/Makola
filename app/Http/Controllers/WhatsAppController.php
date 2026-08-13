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

    public function sendTextMessage(WhatsAppService $whatsApp)
    {
        try {
            $response = $whatsApp->sendMessage(
                '233539278827',
                'Hello From Blue Space Testing area'
            );

            // Throw an exception if the request was not successful
            $response->throw();

            return response()->json([
                'success' => true,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Catch specific Guzzle/HTTP client exceptions
            Log::error('WhatsApp API request failed.', ['message' => $e->getMessage(), 'response_body' => $e->response->body()]);
            return response()->json(['success' => false, 'message' => 'WhatsApp API request failed.', 'error' => $e->response->json()], $e->response->status());
        } catch (\Exception $e) {
            // Catch any other generic exceptions
            Log::error('Failed to send WhatsApp message.', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }
}