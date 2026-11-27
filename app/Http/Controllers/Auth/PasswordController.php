<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->user()->isAdmin() || $request->user()->isCounselor()) {
            app(\App\Services\ActivityLogService::class)->log(
                action: 'auth.password_updated',
                module: 'Settings',
                description: "{$request->user()->first_name} {$request->user()->last_name} updated their account password.",
                subject: $request->user(),
                actor: $request->user(),
            );
        }

        return back()->with('status', 'password-updated');
    }
}
