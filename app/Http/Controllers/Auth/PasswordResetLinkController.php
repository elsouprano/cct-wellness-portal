<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (TransportExceptionInterface $e) {
            Log::error('Password reset email failed due to mail transport exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "We couldn't send the email right now. Please try again later."]);
        } catch (\Throwable $e) {
            Log::error('Password reset email failed due to unexpected exception: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "We couldn't send the email right now. Please try again later."]);
        }

        // Keep existing success behavior and do not reveal whether an email exists in the system
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
