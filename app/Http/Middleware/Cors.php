<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $allowedOriginsRaw = env('CORS_ALLOWED_ORIGINS', '');
        $frontendRaw = env('FRONTEND_URL', '');
        $fallbackOrigin = 'https://blue-space-gh.vercel.app';

        $allowedOrigins = array_filter(array_map(
            static fn($origin) => rtrim(trim((string) $origin), '/'),
            array_filter(explode(',', $allowedOriginsRaw !== '' ? $allowedOriginsRaw : ($frontendRaw !== '' ? $frontendRaw : $fallbackOrigin)))
        ));

        $origin = rtrim((string) $request->headers->get('Origin', ''), '/');

        if ($origin !== '' && (in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true))) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', $request->headers->get('Access-Control-Request-Headers', '*'));
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}

