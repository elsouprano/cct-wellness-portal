<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSectionConfirmed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->role === 'student' && $user->needs_section_confirmation) {
                // Allow confirmation routes and logout so student cannot be locked in without way out
                if ($request->routeIs('student.section-confirmation.*') || $request->routeIs('logout')) {
                    return $next($request);
                }

                return redirect()->route('student.section-confirmation.show');
            }

            // If confirmation is already complete, prevent accessing confirmation page directly
            if ($request->routeIs('student.section-confirmation.*')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
