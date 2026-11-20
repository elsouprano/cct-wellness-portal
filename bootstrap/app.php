<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust explicit reverse-proxy IPs (defaults to local loopback for cloudflared tunnel)
        $trustedProxiesEnv = env('TRUSTED_PROXIES', '127.0.0.1,::1');
        $trustedProxies = $trustedProxiesEnv === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', (string) $trustedProxiesEnv))));
        $middleware->trustProxies(at: $trustedProxies, headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureAccountIsActive::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureSectionConfirmed::class);
        $middleware->alias([
            'counselor_or_admin' => \App\Http\Middleware\EnsureUserIsCounselorOrAdmin::class,
            'ensure_section_confirmed' => \App\Http\Middleware\EnsureSectionConfirmed::class,
            'ensure_account_active' => \App\Http\Middleware\EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
