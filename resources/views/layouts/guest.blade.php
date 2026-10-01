<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .text-muted { color: rgba(44, 26, 26, 0.65); }
            .text-foreground { color: rgba(44, 26, 26, 1); }
            .custom-input { 
                width: 100%; 
                border: 1px solid rgba(44, 26, 26, 0.2);
                border-radius: 0.5rem; 
                padding: 0.75rem 1rem; 
                background-color: #ffffff; 
                transition: all 0.2s ease; 
                outline: none;
                color: var(--color-foreground, #0f172a);
                font-size: 0.95rem;
            }
            .custom-input:focus { 
                border-color: var(--color-primary, #8b1014); 
                box-shadow: 0 0 0 3px rgba(139, 16, 20, 0.2); 
            }
            .btn-primary {
                background-color: var(--color-primary, #8b1014);
                color: white;
            }
            .btn-primary:hover {
                background-color: #6b0c0f;
            }
        </style>
    </head>
    <body class="font-sans antialiased selection:bg-primary/20">
        <div class="login-layout">
            <picture>
                <source srcset="{{ asset('images/bg.webp') }}" type="image/webp">
                <img src="{{ asset('images/bg.png') }}" alt="Background" class="absolute inset-0 w-full h-full object-cover z-0 fixed" style="position: fixed;">
            </picture>
            <div class="login-overlay"></div>

            <div class="login-card">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <a href="/" style="display: inline-block;">
                        <x-application-logo class="h-20 w-auto rounded-xl object-contain" />
                    </a>
                </div>
                {{ $slot }}
            </div>
        </div>
        <x-logout-modal />
    </body>
</html>
