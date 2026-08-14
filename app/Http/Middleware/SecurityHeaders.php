<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply security headers to every HTTP response.
     *
     * Content-Security-Policy notes:
     *  - script-src uses 'unsafe-inline' because the app has inline <script> blocks in
     *    multiple Blade views (analytics Chart.js init, inventory form, question bank,
     *    profile picture preview, etc.). Migrating to nonces is the correct next step.
     *    TODO (PRODUCTION): Replace 'unsafe-inline' with a per-request nonce.
     *  - style-src uses 'unsafe-inline' because Tailwind/Alpine write inline styles and
     *    several Blade views have inline <style> blocks.
     *  - Trusted CDN hosts are explicitly allow-listed alongside SRI hashes in templates.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a random nonce for this request
        $nonce = \Illuminate\Support\Str::random(32);
        
        // Share the nonce with all views
        \Illuminate\Support\Facades\View::share('cspNonce', $nonce);

        $response = $next($request);

        // Content-Security-Policy
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' cdn.jsdelivr.net unpkg.com",
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com unpkg.com",
            "font-src 'self' fonts.gstatic.com data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if (app()->environment('production')) {
            $cspDirectives[] = "upgrade-insecure-requests";
        }

        $csp = implode('; ', $cspDirectives);

        $response->headers->set('Content-Security-Policy', $csp);

        // Prevent clickjacking - no iframe use in this app
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-type sniffing attacks
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Do not leak URL to third parties
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Remove PHP/framework version disclosure
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
