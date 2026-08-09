<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCounselorOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || (! $request->user()->isAdmin() && ! $request->user()->isCounselor())) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        return $next($request);
    }
}
