<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers Enable/Disable
    |--------------------------------------------------------------------------
    |
    | Enable or disable adding security headers globally.
    |
    */

    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security (HSTS)
    |--------------------------------------------------------------------------
    |
    | Strict-Transport-Security enforces secure (HTTPS) connections.
    | It should be enabled in production environments.
    |
    */

    'hsts' => [
        'enabled' => env('SECURITY_HSTS_ENABLED', false),
        'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', true),
        'preload' => env('SECURITY_HSTS_PRELOAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | CSP prevents various injection vulnerabilities, including Cross-Site Scripting.
    |
    */

    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'policy' => env('SECURITY_CSP_POLICY', "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:; frame-src 'self' https:; object-src 'none';"),
    ],

    /*
    |--------------------------------------------------------------------------
    | X-Frame-Options
    |--------------------------------------------------------------------------
    |
    | X-Frame-Options controls whether the site can be embedded in an iframe.
    | Set to 'SAMEORIGIN' or 'DENY'.
    |
    */

    'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),

    /*
    |--------------------------------------------------------------------------
    | X-Content-Type-Options
    |--------------------------------------------------------------------------
    |
    | X-Content-Type-Options: nosniff prevents browsers from MIME-sniffing
    | responses away from the declared content-type.
    |
    */

    'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),

    /*
    |--------------------------------------------------------------------------
    | Referrer-Policy
    |--------------------------------------------------------------------------
    |
    | Referrer-Policy controls how much referrer info the browser includes.
    |
    */

    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

    /*
    |--------------------------------------------------------------------------
    | Permissions-Policy
    |--------------------------------------------------------------------------
    |
    | Permissions-Policy allows you to control which browser features are enabled.
    |
    */

    'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=(), interest-cohort=()'),

];
