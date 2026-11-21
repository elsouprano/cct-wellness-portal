<?php

namespace App\Providers;

use App\Mail\BrevoTransport;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config = []) {
            $apiKey = trim((string) ($config['key'] ?? config('services.brevo.key') ?? ''));

            if ($apiKey === '') {
                throw new \RuntimeException('Brevo API key is not configured.');
            }

            return new BrevoTransport($apiKey);
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify Your Email Address — CCT Wellness')
                ->view(
                    ['emails.verify-email', 'emails.verify-email-text'],
                    [
                        'user' => $notifiable,
                        'verificationUrl' => $url,
                        'expireMinutes' => (int) config('auth.verification.expire', 60),
                        'logoUrl' => asset('images/guidance-logo.png'),
                    ]
                );
        });
    }
}
