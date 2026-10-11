@props([
    'title' => null,
    'wide' => false,
    'fit' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $fit ? 'h-screen h-[100dvh] overflow-hidden' : 'h-full' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name', 'CCT Wellness Portal') }}</title>

    <!-- Preload Optimized Background Image -->
    <link rel="preload" as="image" href="{{ asset('images/bg.webp') }}" type="image/webp">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,300..900;1,6..12,300..900&family=Varela+Round&display=swap" rel="stylesheet">

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-[#1F1A1A] bg-[#FDFBF7] {{ $fit ? 'h-screen h-[100dvh] overflow-hidden' : 'min-h-screen min-h-[100dvh] overflow-x-hidden' }} flex flex-col selection:bg-primary/20">

    <div class="{{ $fit ? 'h-screen h-[100dvh] overflow-hidden' : 'min-h-screen min-h-[100dvh]' }} flex flex-col lg:flex-row flex-1 w-full">
        
        <!-- Left Panel: Branded Hero (Desktop ≥1024px) -->
        <aside class="hidden lg:flex flex-col justify-start relative overflow-hidden lg:w-5/12 h-[100dvh] p-10 xl:p-14 select-none shrink-0" aria-label="{{ __('Portal Information') }}">
            <!-- Background WebP Photo -->
            <img 
                src="{{ asset('images/bg.webp') }}" 
                alt="" 
                aria-hidden="true" 
                class="absolute inset-0 w-full h-full object-cover pointer-events-none" 
            />
            
            <!-- Flat Maroon Overlay (35%) -->
            <div class="absolute inset-0 bg-[#4A0A0C]/35 pointer-events-none"></div>

            <!-- Top Header in Left Panel -->
            <div class="relative z-10 flex items-center gap-4">
                <x-logo-badge size="sm" href="/" class="w-14 h-14 shrink-0 shadow-sm" />
                <div>
                    <span class="block text-xs uppercase tracking-wider text-white font-semibold">City College of Tagaytay</span>
                    <span class="block text-sm font-medium text-white/90">Guidance & Counseling Services Unit</span>
                </div>
            </div>
        </aside>

        <!-- Right Panel: Interactive Form Surface -->
        <main class="w-full min-w-0 lg:w-7/12 xl:w-7/12 flex-1 flex flex-col justify-center {{ $fit ? 'py-1.5 sm:py-2.5 lg:py-3.5 px-3 sm:px-6 lg:px-10 h-full min-h-0 overflow-hidden' : 'px-4 sm:px-8 lg:px-12 py-6 sm:py-8 lg:py-12 safe-bottom safe-top' }}">
            
            <!-- Mobile / Tablet Compact Header (<1024px) (shown only if NOT fit) -->
            @if (!$fit)
            <header class="lg:hidden flex flex-col items-center text-center mb-5 w-full max-w-md mx-auto">
                <x-logo-badge size="md" href="/" class="mb-2.5" />
                <h1 class="font-heading text-2xl font-bold text-primary tracking-tight">CCT Wellness Portal</h1>
                <p class="text-xs text-[#1F1A1A]/60 font-medium mt-0.5">City College of Tagaytay • Guidance Services</p>
            </header>
            @endif

            <!-- Card Shell -->
            <div class="w-full min-w-0 {{ $wide ? 'max-w-2xl' : 'max-w-[420px]' }} mx-auto {{ $fit ? 'flex flex-col h-auto max-h-full min-h-0 my-auto' : '' }}">
                <div class="bg-white rounded-2xl border border-[#E8E1D5] shadow-sm transition-all {{ $wide ? ($fit ? 'p-3 sm:p-4.5 lg:p-5' : 'p-4 sm:p-7 lg:p-9') : 'p-8' }} flex flex-col max-h-full min-h-0">
                    {{ $slot }}
                </div>
            </div>

        </main>

    </div>

</body>
</html>
