<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $token = $request->bearerToken();
        $expectedToken = config('services.wordpress.api_token');

        if (empty($expectedToken)) {
            return response()->json([
                'success' => false,
                'error' => 'API not configured'
            ], 500);
        }

        if (empty($token) || !hash_equals($expectedToken, $token)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}
