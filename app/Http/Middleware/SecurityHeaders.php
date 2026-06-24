<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!config('security.enabled', true)) {
            return $response;
        }

        // 1. Strict-Transport-Security (HSTS)
        if (config('security.hsts.enabled', false) && $request->secure()) {
            $hstsParts = ["max-age=" . config('security.hsts.max_age', 31536000)];
            if (config('security.hsts.include_subdomains', true)) {
                $hstsParts[] = "includeSubDomains";
            }
            if (config('security.hsts.preload', true)) {
                $hstsParts[] = "preload";
            }
            $response->headers->set('Strict-Transport-Security', implode('; ', $hstsParts));
        }

        // 2. Content-Security-Policy (CSP)
        if (config('security.csp.enabled', true)) {
            $response->headers->set('Content-Security-Policy', config('security.csp.policy'));
        }

        // 3. X-Frame-Options
        if ($xFrameOptions = config('security.x_frame_options')) {
            $response->headers->set('X-Frame-Options', $xFrameOptions);
        }

        // 4. X-Content-Type-Options
        if ($xContentTypeOptions = config('security.x_content_type_options')) {
            $response->headers->set('X-Content-Type-Options', $xContentTypeOptions);
        }

        // 5. Referrer-Policy
        if ($referrerPolicy = config('security.referrer_policy')) {
            $response->headers->set('Referrer-Policy', $referrerPolicy);
        }

        // 6. Permissions-Policy
        if ($permissionsPolicy = config('security.permissions_policy')) {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        return $response;
    }
}
