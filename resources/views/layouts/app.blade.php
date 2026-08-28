<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CCT Wellness Portal') }}</title>
        <meta name="description" content="CCT Wellness Portal for Student Assessments and Mental Health Monitoring.">
        <meta property="og:title" content="{{ config('app.name', 'CCT Wellness Portal') }}">
        <meta property="og:description" content="CCT Wellness Portal for Student Assessments and Mental Health Monitoring.">
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
    <body class="font-sans text-foreground bg-background antialiased selection:bg-primary/20">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm border-b border-border">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main x-data="{ pageLoaded: false }" x-init="setTimeout(() => pageLoaded = true, 10)">
                <div class="transition-all duration-500 ease-out"
                     :class="pageLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                    {{ $slot }}
                </div>
            </main>
        </div>
        
        <!-- UI Enhancements -->
        <x-toast />
        <x-scroll-to-top />
        <x-logout-modal />

        @stack('scripts')
        <x-toast />
    </body>
</html>
