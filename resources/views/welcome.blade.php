@section('title', 'Welcome')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif</title>
    <meta name="description" content="Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
    <meta property="og:title" content="@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta property="og:description" content="Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
    <meta property="og:type" content="website">

    <!-- Performance Hints -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preload" as="image" href="{{ asset('images/guidance-logo.png') }}">
    <link rel="preload" as="image" href="{{ asset('images/bg.webp') }}" type="image/webp">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800&family=Varela+Round&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media (prefers-reduced-motion: no-preference) {
            .reveal-on-scroll {
                opacity: 0;
                transform: translateY(1.5rem);
                transition: opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1), transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
            }
            .reveal-on-scroll.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-background text-foreground min-h-screen flex flex-col overflow-x-hidden selection:bg-primary/20 selection:text-primary" x-data="{ mobileMenuOpen: false }">

    <!-- Top Institutional Navigation -->
    <header class="sticky top-0 z-40 w-full bg-background/90 backdrop-blur-md border-b border-border/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Brand Lockup -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-muted border border-border flex items-center justify-center p-1 shadow-sm group-hover:border-primary/40 transition-colors">
                        <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" width="40" height="40" class="w-full h-full object-contain" />
                    </div>
                    <div class="leading-tight">
                        <span class="block font-heading font-bold text-base sm:text-lg text-foreground tracking-tight group-hover:text-primary transition-colors">
                            CCT Wellness
                        </span>
                        <span class="hidden sm:block text-xs font-medium text-foreground/60">
                            Guidance &amp; Counseling Services
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav aria-label="Primary" class="hidden md:flex items-center gap-8 text-sm font-semibold text-foreground/75">
                    <a href="#about" class="hover:text-primary transition-colors">About</a>
                    <a href="#how-it-works" class="hover:text-primary transition-colors">How It Works</a>
                    <a href="#privacy" class="hover:text-primary transition-colors">Data Privacy</a>
                </nav>

                <!-- Desktop Auth Actions -->
                <div class="hidden md:flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 bg-primary text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm hover:bg-primary/90 hover:-translate-y-0.5 transition-all duration-200">
                                <span>Go to Dashboard</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center text-sm font-semibold text-primary hover:text-primary/80 px-4 py-2.5 rounded-xl hover:bg-muted/70 transition-colors">
                                Log In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-primary text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm hover:bg-primary/90 hover:-translate-y-0.5 transition-all duration-200">
                                    Register Account
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen.toString()"
                    aria-controls="mobile-nav-menu"
                    aria-label="Toggle navigation menu"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-xl text-foreground/75 hover:text-primary hover:bg-muted transition-colors focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                    <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div
            id="mobile-nav-menu"
            x-show="mobileMenuOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="md:hidden border-t border-border bg-white/95 backdrop-blur-md"
        >
            <div class="px-4 py-4 space-y-2">
                <a href="#about" @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-foreground/80 hover:text-primary hover:bg-muted/60 transition-colors">About CCT Wellness</a>
                <a href="#how-it-works" @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-foreground/80 hover:text-primary hover:bg-muted/60 transition-colors">How It Works</a>
                <a href="#privacy" @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-foreground/80 hover:text-primary hover:bg-muted/60 transition-colors">Data Privacy</a>

                @if (Route::has('login'))
                    <div class="pt-3 mt-2 border-t border-border flex flex-col gap-2.5">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full inline-flex items-center justify-center gap-2 bg-primary text-white text-sm font-semibold px-4 py-3 rounded-xl shadow-sm hover:bg-primary/90 transition-colors">
                                <span>Go to Dashboard</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center bg-primary text-white text-sm font-semibold px-4 py-3 rounded-xl shadow-sm hover:bg-primary/90 transition-colors">
                                Log In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center border-2 border-primary text-primary text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-primary/5 transition-colors">
                                    Register Account
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-grow">

        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-b from-white via-background to-background border-b border-border/60">
            <!-- Subtle Institutional Ambient Accents -->
            <div aria-hidden="true" class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 rounded-full bg-primary/5 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute top-1/2 -right-24 w-80 h-80 rounded-full bg-accent/10 blur-3xl"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 lg:py-24 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-14 items-center">

                    <!-- Left: Institutional Copy & CTAs -->
                    <div class="lg:col-span-7 space-y-6 reveal-on-scroll">
                        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-muted border border-border text-xs sm:text-sm font-semibold text-primary">
                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                            <span>City College of Tagaytay &bull; Guidance &amp; Counseling Services</span>
                        </div>

                        <div class="border-l-4 border-primary pl-4 text-sm sm:text-base font-heading font-semibold text-foreground/80 leading-snug">
                            Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay
                        </div>

                        <h1 class="font-heading font-bold text-foreground tracking-tight leading-[1.1] text-4xl sm:text-5xl lg:text-[3.5rem]">
                            Empowering Student
                            <span class="text-primary block sm:inline">Well-Being</span>
                        </h1>

                        <p class="text-base sm:text-lg text-foreground/70 leading-relaxed max-w-2xl">
                            The official psychological assessment platform for City College of Tagaytay. Complete your scheduled, year-level-specific self-assessments and stay informed with official guidance announcements.
                        </p>

                        <!-- Hero Action Buttons -->
                        <div class="flex flex-wrap items-center gap-3.5 pt-2">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center gap-2.5 bg-primary text-white font-semibold text-base px-7 py-3.5 rounded-2xl shadow-md hover:bg-primary/90 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                                        <span>Go to Dashboard</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                            <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2.5 bg-primary text-white font-semibold text-base px-7 py-3.5 rounded-2xl shadow-md hover:bg-primary/90 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                                        <span>Log In</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                                            <path fill-rule="evenodd" d="M10 2a.75.75 0 01.75.75v7.5a.75.75 0 01-1.5 0v-7.5A.75.75 0 0110 2zM5.404 4.108a.75.75 0 010 1.06 7.5 7.5 0 109.192 0 .75.75 0 111.06-1.06 9 9 0 11-11.312 0 .75.75 0 011.06 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center border-2 border-primary text-primary bg-white/80 font-semibold text-base px-7 py-3 rounded-2xl hover:bg-primary/5 hover:-translate-y-0.5 transition-all duration-200">
                                            Register Account
                                        </a>
                                    @endif
                                @endauth
                            @endif
                        </div>

                        <!-- Subtle Institutional Trust Bar -->
                        <div class="pt-4 border-t border-border/70 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs sm:text-sm font-medium text-foreground/65">
                            <span class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-primary">
                                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                </svg>
                                RA 10173 Protected
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-secondary">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                                Year-Level Tailored Inventories
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-accent">
                                    <path d="M10 2a6 6 0 00-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 00.515 1.076 32.91 32.91 0 003.256.508 3.5 3.5 0 006.972 0 32.903 32.903 0 003.256-.508.75.75 0 00.515-1.076A11.448 11.448 0 0116 8a6 6 0 00-6-6z" />
                                </svg>
                                Official Guidance Updates
                            </span>
                        </div>
                    </div>

                    <!-- Right: Framed Institutional Visual Showcase -->
                    <div class="lg:col-span-5 reveal-on-scroll">
                        <div class="relative max-w-md mx-auto lg:ml-auto">
                            <!-- Subtle Decorative Frame -->
                            <div aria-hidden="true" class="hidden sm:block absolute -inset-3 rounded-[2rem] border border-primary/15 bg-muted/40 -rotate-2"></div>

                            <div class="relative rounded-3xl overflow-hidden border border-border bg-white shadow-xl">
                                <div class="aspect-[4/3] relative overflow-hidden">
                                    <img
                                        src="{{ asset('images/bg.webp') }}"
                                        alt="City College of Tagaytay Campus"
                                        width="800"
                                        height="600"
                                        loading="eager"
                                        decoding="async"
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-[#1F1A1A]/75 via-[#1F1A1A]/20 to-transparent"></div>

                                    <!-- Top Corner Institutional Seal -->
                                    <div class="absolute top-4 left-4 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-[#FFF2D0]/95 backdrop-blur-sm border border-white/60 shadow-lg p-1.5 flex items-center justify-center">
                                        <img
                                            src="{{ asset('images/guidance-logo.png') }}"
                                            alt="CCT Guidance Logo"
                                            width="80"
                                            height="80"
                                            loading="eager"
                                            decoding="async"
                                            class="w-full h-full object-contain"
                                        />
                                    </div>

                                    <!-- Bottom Caption Overlay -->
                                    <div class="absolute bottom-4 left-4 right-4 text-white">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-white/80">
                                            Official Student Portal
                                        </p>
                                        <p class="font-heading font-bold text-lg sm:text-xl leading-snug">
                                            CCT Guidance &amp; Counseling Services Unit
                                        </p>
                                    </div>
                                </div>

                                <div class="p-5 sm:p-6 bg-white flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-primary">
                                            Confidential &amp; Guided
                                        </p>
                                        <p class="text-sm text-foreground/75 mt-0.5">
                                            Supporting student well-being and psychological health.
                                        </p>
                                    </div>
                                    <div class="shrink-0 w-10 h-10 rounded-xl bg-muted flex items-center justify-center text-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- About CCT Wellness & Core Features -->
        <section id="about" class="py-16 sm:py-24 bg-background">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center mb-12 sm:mb-16 reveal-on-scroll">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">
                        About CCT Wellness
                    </span>
                    <h2 class="font-heading font-bold text-3xl sm:text-4xl text-foreground tracking-tight">
                        Designed for Student Support &amp; Guidance
                    </h2>
                    <div class="w-16 h-1 bg-primary/30 rounded-full mx-auto mt-4"></div>
                    <p class="mt-4 text-base text-foreground/70 leading-relaxed">
                        Built for City College of Tagaytay students to complete scheduled individual inventories and stay connected with the Guidance Office.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 max-w-5xl mx-auto reveal-on-scroll">
                    <!-- Feature Card 1 -->
                    <article class="group bg-white rounded-3xl p-8 sm:p-10 border border-border shadow-sm hover:shadow-lg hover:border-primary/25 hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 rounded-2xl bg-muted text-primary flex items-center justify-center mb-6 group-hover:bg-primary group-hover:text-white transition-colors duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="font-heading text-xl sm:text-2xl font-bold text-foreground mb-3">
                            Tailored Self-Assessments
                        </h3>
                        <p class="text-foreground/70 leading-relaxed text-base">
                            Complete scheduled psychological and wellness self-assessments specifically tailored to your current year level.
                        </p>
                    </article>

                    <!-- Feature Card 2 -->
                    <article class="group bg-white rounded-3xl p-8 sm:p-10 border border-border shadow-sm hover:shadow-lg hover:border-primary/25 hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 rounded-2xl bg-muted text-primary flex items-center justify-center mb-6 group-hover:bg-primary group-hover:text-white transition-colors duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
                            </svg>
                        </div>
                        <h3 class="font-heading text-xl sm:text-2xl font-bold text-foreground mb-3">
                            Campus Announcements
                        </h3>
                        <p class="text-foreground/70 leading-relaxed text-base">
                            Stay up to date with the latest official announcements, notices, and updates directly from the CCT Guidance Office.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-16 sm:py-24 bg-muted/50 border-y border-border/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center mb-12 sm:mb-16 reveal-on-scroll">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary mb-2">
                        Student Workflow
                    </span>
                    <h2 class="font-heading font-bold text-3xl sm:text-4xl text-foreground tracking-tight">
                        How It Works
                    </h2>
                    <div class="w-16 h-1 bg-primary/30 rounded-full mx-auto mt-4"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto reveal-on-scroll">
                    <!-- Step 1 -->
                    <div class="group relative bg-white rounded-3xl p-8 border border-border shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-muted text-primary text-xs font-bold tracking-wider uppercase mb-5">
                            Step 01
                        </span>
                        <div class="w-16 h-16 rounded-2xl bg-muted text-primary flex items-center justify-center mb-5 group-hover:bg-primary group-hover:text-white transition-colors duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <h3 class="font-heading text-xl font-bold text-foreground mb-2.5">1. Register</h3>
                        <p class="text-foreground/70 text-sm sm:text-base leading-relaxed">
                            Create your secure account using your official CCT credentials.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="group relative bg-white rounded-3xl p-8 border border-border shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-muted text-primary text-xs font-bold tracking-wider uppercase mb-5">
                            Step 02
                        </span>
                        <div class="w-16 h-16 rounded-2xl bg-muted text-primary flex items-center justify-center mb-5 group-hover:bg-primary group-hover:text-white transition-colors duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                            </svg>
                        </div>
                        <h3 class="font-heading text-xl font-bold text-foreground mb-2.5">2. Take Assessment</h3>
                        <p class="text-foreground/70 text-sm sm:text-base leading-relaxed">
                            Complete your scheduled, year-level specific self-assessment.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="group relative bg-white rounded-3xl p-8 border border-border shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-muted text-primary text-xs font-bold tracking-wider uppercase mb-5">
                            Step 03
                        </span>
                        <div class="w-16 h-16 rounded-2xl bg-muted text-primary flex items-center justify-center mb-5 group-hover:bg-primary group-hover:text-white transition-colors duration-300 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </div>
                        <h3 class="font-heading text-xl font-bold text-foreground mb-2.5">3. Stay Updated</h3>
                        <p class="text-foreground/70 text-sm sm:text-base leading-relaxed">
                            Receive official announcements and updates from the Guidance Office.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Data Privacy & Confidentiality Section -->
        <section id="privacy" class="py-16 sm:py-24 bg-background">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 reveal-on-scroll">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-[#671111] text-white p-8 sm:p-12 lg:p-14 shadow-xl">
                    <div aria-hidden="true" class="pointer-events-none absolute -top-20 -right-20 w-72 h-72 rounded-full bg-white/10 blur-2xl"></div>
                    <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 -left-16 w-64 h-64 rounded-full bg-accent/15 blur-2xl"></div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-7 sm:gap-10 text-center md:text-left">
                        <div class="w-20 h-20 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/25 flex items-center justify-center shrink-0 text-white shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </div>

                        <div class="flex-1 space-y-3">
                            <span class="inline-block text-xs font-bold uppercase tracking-widest text-white/80">
                                Confidentiality Commitment
                            </span>
                            <h2 class="font-heading font-bold text-2xl sm:text-3xl tracking-tight">
                                Your Privacy Matters
                            </h2>
                            <p class="text-white/90 text-sm sm:text-base leading-relaxed">
                                All responses and records are strictly confidential and handled in full accordance with RA 10173 (Data Privacy Act of 2012). Your assessment data is secured and reviewed exclusively by authorized City College of Tagaytay Guidance staff.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Institutional Footer -->
    <footer class="bg-white border-t border-border mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                <!-- Brand & Unit Info -->
                <div class="md:col-span-6 space-y-3 text-center md:text-left">
                    <div class="inline-flex items-center gap-3 justify-center md:justify-start">
                        <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" width="44" height="44" loading="lazy" decoding="async" class="h-11 w-11 object-contain" />
                        <div class="text-left">
                            <h3 class="font-heading font-bold text-sm sm:text-base text-foreground">
                                City College of Tagaytay Guidance and Counseling Services Unit
                            </h3>
                            <p class="text-xs sm:text-sm text-foreground/65">
                                Supporting student well-being and psychological health.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="md:col-span-3 text-center md:text-left">
                    <h4 class="font-heading font-bold text-xs uppercase tracking-wider text-foreground/60 mb-3">
                        Information
                    </h4>
                    <ul class="space-y-2 text-sm font-medium text-foreground/75">
                        <li><a href="#about" class="hover:text-primary transition-colors">About CCT Wellness</a></li>
                        <li><a href="#how-it-works" class="hover:text-primary transition-colors">How It Works</a></li>
                        <li><a href="#privacy" class="hover:text-primary transition-colors">Data Privacy (RA 10173)</a></li>
                    </ul>
                </div>

                <!-- Portal Access -->
                <div class="md:col-span-3 text-center md:text-left">
                    <h4 class="font-heading font-bold text-xs uppercase tracking-wider text-foreground/60 mb-3">
                        Student Portal
                    </h4>
                    <ul class="space-y-2 text-sm font-medium text-foreground/75">
                        @if (Route::has('login'))
                            @auth
                                <li><a href="{{ url('/dashboard') }}" class="hover:text-primary transition-colors">Go to Dashboard</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="hover:text-primary transition-colors">Log In</a></li>
                                @if (Route::has('register'))
                                    <li><a href="{{ route('register') }}" class="hover:text-primary transition-colors">Register Account</a></li>
                                @endif
                            @endauth
                        @endif
                    </ul>
                </div>
            </div>

            <div class="border-t border-border/70 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-foreground/60">
                <p class="font-semibold tracking-wide uppercase">
                    &copy; {{ date('Y') }} City College of Tagaytay. All rights reserved.
                </p>
                <p>
                    Guidance and Counseling Services Unit
                </p>
            </div>
        </div>
    </footer>

    <!-- Intersection Observer Script for Scroll Animations -->
    <script nonce="{{ $cspNonce }}">
        document.addEventListener("DOMContentLoaded", function() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.querySelectorAll('.reveal-on-scroll').forEach(function(el) {
                    el.classList.add('is-visible');
                });
                return;
            }

            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            }, {
                root: null,
                threshold: 0.12,
                rootMargin: "0px 0px -40px 0px"
            });

            document.querySelectorAll('.reveal-on-scroll').forEach((el) => {
                observer.observe(el);
            });
        });
    </script>

    <!-- Flash Toast Notifications -->
    <x-toast />
</body>
</html>
