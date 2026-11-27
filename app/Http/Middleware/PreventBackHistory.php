<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreventBackHistory
{
    /**
     * Prevent browsers from caching guest auth pages or authenticated pages
     * so pressing Back after login/logout never serves stale screens.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $disposition = (string) $response->headers->get('Content-Disposition', '');
        if (str_contains(strtolower($disposition), 'attachment')) {
            return $response;
        }

        $isAuthEntryRoute = $request->routeIs('login', 'register', 'password.*', 'verification.*')
            || in_array($request->path(), ['login', 'register', 'forgot-password'], true)
            || str_starts_with($request->path(), 'reset-password');

        if ($isAuthEntryRoute || $request->user() !== null || $response->isRedirection()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
