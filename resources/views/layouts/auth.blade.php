@props([
    'title' => null,
    'wide' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
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
<body class="font-sans antialiased text-[#1F1A1A] bg-[#FDFBF7] min-h-screen min-h-[100dvh] flex flex-col selection:bg-primary/20 overflow-x-hidden">

    <div class="min-h-screen min-h-[100dvh] flex flex-col lg:flex-row flex-1 w-full">
        
        <!-- Left Panel: Branded Hero (Desktop ≥1024px) -->
        <aside class="hidden lg:flex lg:w-5/12 xl:w-5/12 relative bg-[#8B1C1C] text-white flex-col justify-between p-10 xl:p-14 overflow-hidden shrink-0 select-none" aria-label="{{ __('Portal Information') }}">
            <!-- Background WebP Photo -->
            <img 
                src="{{ asset('images/bg.webp') }}" 
                alt="" 
                aria-hidden="true" 
                class="absolute inset-0 w-full h-full object-cover opacity-25 mix-blend-luminosity pointer-events-none" 
            />
            
            <!-- Deep Maroon Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#8B1C1C]/95 via-[#6B0C0F]/90 to-[#4A0A0C]/95 z-0 pointer-events-none"></div>

            <!-- Top Header in Left Panel -->
            <div class="relative z-10 flex items-center gap-4">
                <x-logo-badge size="md" href="/" />
                <div>
                    <span class="block text-xs uppercase tracking-wider text-white/80 font-semibold">City College of Tagaytay</span>
                    <span class="block text-sm font-medium text-white/90">Guidance & Counseling Services Unit</span>
                </div>
            </div>

            <!-- Central Hero Message -->
            <div class="relative z-10 my-auto py-8">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-white backdrop-blur-md mb-4 border border-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Student Mental Wellness Support
                </span>
                
                <h1 class="font-heading text-3xl xl:text-4xl font-bold tracking-tight text-white leading-tight mb-4">
                    CCT Wellness Portal
                </h1>
                
                <p class="text-white/85 text-base leading-relaxed mb-6 font-normal max-w-md">
                    A safe, compassionate space for counseling schedules, wellness assessments, and student guidance.
                </p>

                <!-- Reassuring Feature Badges -->
                <ul class="space-y-3 text-sm text-white/90">
                    <li class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Confidential and secure student health records</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Direct connection with certified guidance counselors</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Strict compliance with RA 10173 (Data Privacy Act)</span>
                    </li>
                </ul>
            </div>

            <!-- Footer Note in Left Panel -->
            <div class="relative z-10 pt-6 border-t border-white/15 text-xs text-white/60">
                &copy; {{ date('Y') }} City College of Tagaytay. All rights reserved.
            </div>
        </aside>

        <!-- Right Panel: Interactive Form Surface -->
        <main class="w-full min-w-0 lg:w-7/12 xl:w-7/12 flex-1 flex flex-col justify-center px-4 sm:px-8 lg:px-12 py-6 sm:py-8 lg:py-12 safe-bottom safe-top">
            
            <!-- Mobile / Tablet Compact Header (<1024px) -->
            <header class="lg:hidden flex flex-col items-center text-center mb-5 w-full max-w-md mx-auto">
                <x-logo-badge size="md" href="/" class="mb-2.5" />
                <h1 class="font-heading text-2xl font-bold text-primary tracking-tight">CCT Wellness Portal</h1>
                <p class="text-xs text-[#1F1A1A]/60 font-medium mt-0.5">City College of Tagaytay • Guidance Services</p>
            </header>

            <!-- Card Shell -->
            <div class="w-full min-w-0 {{ $wide ? 'max-w-2xl' : 'max-w-md' }} mx-auto">
                <div class="bg-white rounded-2xl border border-[#E8E1D5]/70 shadow-sm p-4 sm:p-7 lg:p-9 transition-all">
                    {{ $slot }}
                </div>
            </div>

        </main>

    </div>

</body>
</html>
