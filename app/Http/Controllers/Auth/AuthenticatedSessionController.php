<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user && in_array($user->role, ['system_admin', 'guidance_counselor'], true)) {
            app(\App\Services\ActivityLogService::class)->log(
                action: 'auth.login',
                module: 'Authentication',
                description: "{$user->first_name} {$user->last_name} logged in.",
                subject: $user,
                actor: $user,
            );
        }

        $message = match($user->role) {
            'system_admin' => "Welcome back, {$user->first_name}! Logged in as System Administrator.",
            'guidance_counselor' => "Welcome back, {$user->first_name}! Logged in as Guidance Counselor.",
            default => "Welcome back, {$user->first_name}! You have successfully logged in.",
        };

        $intendedRedirect = redirect()->intended(route('dashboard', absolute: false));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'first_name' => $user->first_name,
                'role' => $user->role,
                'redirect' => $intendedRedirect->getTargetUrl(),
            ]);
        }

        return $intendedRedirect
            ->with('success', $message);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $name = $user?->first_name;
        $role = $user?->role;

        if ($user && in_array($user->role, ['system_admin', 'guidance_counselor'], true)) {
            app(\App\Services\ActivityLogService::class)->log(
                action: 'auth.logout',
                module: 'Authentication',
                description: "{$user->first_name} {$user->last_name} logged out.",
                subject: $user,
                actor: $user,
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $message = match($role) {
            'system_admin' => $name ? "System Administrator session ended. Goodbye, {$name}!" : "System Administrator session ended.",
            'guidance_counselor' => $name ? "Guidance Counselor session ended. Goodbye, {$name}!" : "Guidance Counselor session ended.",
            default => $name ? "Goodbye, {$name}! You have been successfully logged out." : "You have been successfully logged out.",
        };

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'first_name' => $name,
                'redirect' => route('login', absolute: false),
            ]);
        }

        return redirect()->route('login')
            ->with('success', $message)
            ->with('logout_popup', [
                'message' => $message,
                'name' => $name,
            ]);
    }
}
