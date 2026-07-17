<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOriginsRaw = env('CORS_ALLOWED_ORIGINS', '');
        $frontendRaw = env('FRONTEND_URL', '');
        $fallbackOrigin = 'https://blue-space-gh.vercel.app';

        $allowedOrigins = array_filter(array_map(
            static fn($origin) => rtrim(trim((string) $origin), '/'),
            array_filter(explode(',', $allowedOriginsRaw !== '' ? $allowedOriginsRaw : ($frontendRaw !== '' ? $frontendRaw : $fallbackOrigin)))
        ));

        $origin = rtrim((string) $request->headers->get('Origin', ''), '/');
        $isAllowedOrigin = $origin !== '' && (in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true));

        // Always attach CORS headers (even for OPTIONS / auth errors)
        // Create an empty response for preflight so OPTIONS doesn't fail before headers are added.
        if ($request->getMethod() === 'OPTIONS') {
            $response = response()->noContent(204);
        } else {
            $response = $next($request);
        }

        // IMPORTANT: For wildcard '*' you must set '*' only when you do not send credentials.
        if ($isAllowedOrigin) {
            // If '*' is allowed, do NOT combine with credentials.
            if (in_array('*', $allowedOrigins, true)) {
                $response->headers->set('Access-Control-Allow-Origin', '*');
                $response->headers->set('Access-Control-Allow-Credentials', 'false');
            } else {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Vary', 'Origin');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        } else {
            // Don’t set Allow-Origin when origin is not allowed.
            // (Browser will treat as blocked; but we still send method/header headers.)
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');


        $response->headers->set(
            'Access-Control-Allow-Headers',
            (string) $request->headers->get('Access-Control-Request-Headers', '*')
        );
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}


