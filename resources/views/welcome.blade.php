<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CCT Wellness Portal') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-foreground bg-background min-h-screen flex flex-col items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-border p-8 text-center">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
            </svg>
        </div>
        
        <h1 class="font-heading text-3xl font-bold text-primary mb-4">CCT Wellness Portal</h1>
        <p class="text-foreground/70 mb-8">Access your psychological assessments, wellness resources, and campus announcements.</p>
        
        <div class="space-y-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-primary w-full block">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary w-full block">Log In</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-secondary w-full block mt-4">Register</a>
                    @endif
                @endauth
            @endif
        </div>
    </div>
</body>
</html>
