@props([
    'title' => null,
    'wide' => false,
    'fit' => false,
    'proportional' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $fit ? 'h-screen h-[100dvh] overflow-hidden' : 'h-full' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') | {{ config('app.name') }}@elseif(isset($title) && $title){{ $title }} | {{ config('app.name') }}@else{{ config('app.name') }}@endif</title>

    <!-- Preload Optimized Background Image -->
    <link rel="preload" as="image" href="{{ asset('images/bg.webp') }}" type="image/webp">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,300..900;1,6..12,300..900&family=Varela+Round&display=swap" rel="stylesheet">

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-[#1F1A1A] {{ $fit ? 'h-screen h-[100dvh] overflow-hidden' : 'min-h-screen min-h-[100dvh]' }} flex flex-col selection:bg-primary/20 relative">

    <!-- Full-page Campus Photo Background (Vivid, No Overlay) -->
    <img 
        src="{{ asset('images/bg.webp') }}" 
        alt="" 
        aria-hidden="true" 
        class="fixed inset-0 -z-10 w-full h-full object-cover pointer-events-none" 
    />

    <!-- Main Container: Centered both ways -->
    <main class="w-full h-full flex-1 min-h-0 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        @if ($proportional)
            <!-- Proportional Card Shell (Login) -->
            <div 
                class="login-proportional-card bg-white rounded-2xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.45)] flex flex-col transition-all my-auto"
            >
                {{ $slot }}
            </div>
        @else
            <!-- Card Shell -->
            <div class="w-full min-w-0 {{ $wide ? 'max-w-[720px]' : 'max-w-[460px]' }} mx-auto {{ $fit ? 'max-h-full flex flex-col my-auto' : 'my-auto' }}">
                <div class="bg-white rounded-2xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.45)] transition-all {{ $wide ? 'p-6 sm:p-8 flex flex-col min-h-[clamp(560px,78dvh,720px)] max-h-full flex-1' : 'p-8 sm:p-9' }}">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </main>

</body>
</html>
