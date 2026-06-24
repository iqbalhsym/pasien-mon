<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $expectedToken = env('PASIEN_HISTORI_API_TOKEN');

        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Invalid API Token.'
            ], 401);
        }

        return $next($request);
    }
}
