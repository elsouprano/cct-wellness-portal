<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Your account has been deactivated.'], 403);
                }

                return redirect()->route('login')->withErrors([
                    'identifier' => 'Your account has been deactivated. Contact your system administrator.',
                ]);
            }
        }

        return $next($request);
    }
}

