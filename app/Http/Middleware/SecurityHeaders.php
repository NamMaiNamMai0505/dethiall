<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers to the response.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Frame-Options: Prevent clickjacking attacks
        // SAMEORIGIN allows framing only from same domain
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options: Prevent MIME-sniffing attacks
        // nosniff forces browsers to respect declared Content-Type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer-Policy: Control information sent in Referer header
        // strict-origin-when-cross-origin: Send origin only for cross-origin requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // X-XSS-Protection: Legacy XSS filter for older browsers
        // 1; mode=block enables the filter and blocks the page if XSS detected
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Permissions-Policy: chặn camera/mic; cho geolocation same-origin (P2 điểm danh GPS)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        // Strict-Transport-Security (HSTS): Force HTTPS connections
        // Only enable in production when APP_HTTPS is true
        if (config('app.https', true) && app()->environment('production')) {
            // max-age=31536000: Cache for 1 year
            // includeSubDomains: Apply to all subdomains
            // preload: Allow inclusion in browser HSTS preload lists
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
