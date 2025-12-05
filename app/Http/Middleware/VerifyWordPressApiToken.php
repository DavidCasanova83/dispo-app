<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWordPressApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[CF7] Requête reçue', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        $token = $request->bearerToken();
        $expectedToken = config('services.wordpress.api_token');

        if (empty($expectedToken)) {
            Log::error('[CF7] API non configurée - token manquant dans config');
            return response()->json([
                'success' => false,
                'error' => 'API not configured'
            ], 500);
        }

        if (empty($token)) {
            Log::warning('[CF7] Token Bearer manquant', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        if (!hash_equals($expectedToken, $token)) {
            Log::warning('[CF7] Token invalide', [
                'ip' => $request->ip(),
                'token_recu' => substr($token, 0, 10) . '...',
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        Log::info('[CF7] Authentification réussie', ['ip' => $request->ip()]);
        return $next($request);
    }
}
