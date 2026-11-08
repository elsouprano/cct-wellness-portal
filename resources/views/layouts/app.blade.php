<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif</title>
        <meta name="description" content="Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
        <meta property="og:title" content="@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif">
        <meta property="og:description" content="Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
        <meta property="og:type" content="website">

        <!-- Performance Hints -->
        <link rel="dns-prefetch" href="//fonts.googleapis.com">
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link rel="preload" as="image" href="{{ asset('images/guidance-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans text-foreground bg-[#f4f7f4] antialiased selection:bg-primary/20">
        @php
            $studentNavItems = [
                [
                    'route' => 'dashboard',
                    'label' => 'Home',
                    'isActive' => request()->routeIs('dashboard'),
                    'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
                ],
                [
                    'route' => 'announcements.index',
                    'label' => 'Announcements',
                    'isActive' => request()->routeIs('announcements.*'),
                    'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46',
                ],
                [
                    'route' => 'inventory.history',
                    'label' => 'Assessment History',
                    'isActive' => request()->routeIs('inventory.history*'),
                    'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                ],
            ];
        @endphp

        <x-portal-shell
            portal-label="Student Portal"
            mobile-title="CCT Student Portal"
            role-label="Student"
            :profile-href="route('profile.edit')"
        >
            <x-slot name="navigation">
                @foreach($studentNavItems as $item)
                    <a href="{{ route($item['route']) }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ $item['isActive'] ? 'bg-primary/10 text-primary border border-primary/20 shadow-sm font-semibold' : 'text-foreground/70 hover:bg-muted hover:text-foreground font-medium border border-transparent' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0;" class="{{ $item['isActive'] ? 'text-primary' : 'text-foreground/50' }}">
                          <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </x-slot>

            @isset($header)
                <x-slot name="header">
                    {{ $header }}
                </x-slot>
            @endisset

            {{ $slot }}
        </x-portal-shell>
        
        <!-- UI Enhancements -->
        <x-toast />
        <x-scroll-to-top />
        <x-logout-popup />
        <x-confirm-action-modal />

        @stack('scripts')
    </body>
</html>
