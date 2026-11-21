@section('title', 'Welcome')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif</title>
    <meta name="description" content="Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
    <meta property="og:title" content="@hasSection('title')@yield('title') | {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta property="og:description" content="Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
    <meta property="og:type" content="website">

    <!-- Performance Hints -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preload" as="image" href="{{ asset('images/guidance-logo.png') }}">
    <link rel="preload" as="image" href="{{ asset('images/bg.webp') }}" type="image/webp">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-background text-foreground min-h-screen flex flex-col overflow-x-hidden selection:bg-primary/20 selection:text-primary" x-data="{ mobileMenuOpen: false }">

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Institutional Header -->
    <header class="sticky top-0 z-40 w-full bg-surface border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between min-h-[4.5rem] py-2">
                <!-- Brand Lockup -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-token-md bg-seal border border-border flex items-center justify-center p-1">
                        <img src="{{ asset('images/guidance-logo.png') }}" alt="City College of Tagaytay Guidance Seal" width="36" height="36" class="w-full h-full object-contain" />
                    </div>
                    <div class="leading-tight">
                        <span class="block font-heading font-bold text-lg text-foreground tracking-tight group-hover:text-primary transition-colors">
                            CCT Wellness Portal
                        </span>
                        <span class="hidden sm:block text-xs font-medium text-muted-foreground">
                            Guidance &amp; Counseling Services Unit
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation -->
                <nav aria-label="Primary" class="hidden md:flex items-center gap-7 text-sm font-semibold text-muted-foreground">
                    <a href="#services" class="hover:text-primary transition-colors py-1">Portal Services</a>
                    <a href="#process" class="hover:text-primary transition-colors py-1">How It Works</a>
                    <a href="#privacy" class="hover:text-primary transition-colors py-1">Data Privacy</a>
                </nav>

                <!-- Desktop Auth Actions -->
                <div class="hidden md:flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary text-sm px-5 py-2">
                                <span>Open Dashboard</span>
                                <x-icon name="arrow-right" class="w-4 h-4" />
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center text-sm font-semibold text-primary hover:bg-muted px-4 py-2 rounded-token-md transition-colors">
                                Sign In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-primary text-sm px-5 py-2">
                                    Student Registration
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile Menu Toggle -->
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen.toString()"
                    aria-controls="mobile-nav-menu"
                    aria-label="Toggle navigation menu"
                    class="md:hidden inline-flex items-center justify-center p-2.5 rounded-token-md text-foreground hover:bg-muted transition-colors"
                >
                    <svg x-show="!mobileMenuOpen" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div id="mobile-nav-menu" x-show="mobileMenuOpen" x-cloak class="md:hidden border-t border-border bg-surface">
            <div class="px-4 py-4 space-y-2">
                <a href="#services" @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-token-md text-base font-medium text-foreground hover:bg-muted">Portal Services</a>
                <a href="#process" @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-token-md text-base font-medium text-foreground hover:bg-muted">How It Works</a>
                <a href="#privacy" @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-token-md text-base font-medium text-foreground hover:bg-muted">Data Privacy</a>

                @if (Route::has('login'))
                    <div class="pt-3 mt-2 border-t border-border flex flex-col gap-2.5">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary w-full text-base">
                                <span>Open Dashboard</span>
                                <x-icon name="arrow-right" class="w-4 h-4" />
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-primary w-full text-base">Sign In</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-secondary w-full text-base">Student Registration</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="flex-grow focus:outline-none">

        <!-- Asymmetric Editorial Hero -->
        <section class="bg-surface border-b border-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">

                    <!-- Left Column: Direct Institutional Orientation -->
                    <div class="lg:col-span-7 space-y-6">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-primary">
                            City College of Tagaytay &middot; Guidance &amp; Counseling Services
                        </p>

                        <h1 class="font-heading font-bold text-foreground tracking-tight leading-[1.12] text-3xl sm:text-5xl">
                            Student Individual Inventory &amp; Wellness Portal
                        </h1>

                        <p class="text-base sm:text-lg text-muted-foreground leading-relaxed max-w-2xl">
                            Complete your scheduled year-level psychological inventory, review past submissions, and read official notices from the City College of Tagaytay Guidance Office.
                        </p>

                        <div class="flex flex-wrap items-center gap-3.5 pt-2">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="btn-primary px-6 py-3">
                                        <span>Open Dashboard</span>
                                        <x-icon name="arrow-right" class="w-4 h-4" />
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn-primary px-6 py-3">
                                        <span>Sign In to Portal</span>
                                        <x-icon name="arrow-right" class="w-4 h-4" />
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="btn-secondary px-6 py-3">
                                            Create Student Account
                                        </a>
                                    @endif
                                @endauth
                            @endif
                        </div>

                        <!-- Key Facts Strip -->
                        <dl class="pt-6 border-t border-border grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                            <div class="flex items-start gap-2.5">
                                <x-icon name="lock-closed" class="w-5 h-5 text-primary mt-0.5" />
                                <div>
                                    <dt class="font-semibold text-foreground">RA 10173 Compliant</dt>
                                    <dd class="text-muted-foreground text-xs mt-0.5">Confidential counseling records</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <x-icon name="clipboard-document-check" class="w-5 h-5 text-secondary mt-0.5" />
                                <div>
                                    <dt class="font-semibold text-foreground">Year-Level Specific</dt>
                                    <dd class="text-muted-foreground text-xs mt-0.5">Assigned per academic year</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <x-icon name="megaphone" class="w-5 h-5 text-accent mt-0.5" />
                                <div>
                                    <dt class="font-semibold text-foreground">Official Bulletins</dt>
                                    <dd class="text-muted-foreground text-xs mt-0.5">Direct Guidance Office updates</dd>
                                </div>
                            </div>
                        </dl>
                    </div>

                    <!-- Right Column: Framed Campus Record Card -->
                    <aside class="lg:col-span-5">
                        <figure class="rounded-token-lg overflow-hidden border border-border bg-background shadow-sm">
                            <div class="aspect-[4/3] overflow-hidden border-b border-border bg-muted">
                                <img
                                    src="{{ asset('images/bg.webp') }}"
                                    alt="City College of Tagaytay campus building"
                                    width="800"
                                    height="600"
                                    loading="eager"
                                    decoding="async"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <figcaption class="p-5 flex items-center gap-4 bg-surface">
                                <div class="w-12 h-12 rounded-token-md bg-seal border border-border p-1.5 shrink-0 flex items-center justify-center">
                                    <img
                                        src="{{ asset('images/guidance-logo.png') }}"
                                        alt=""
                                        aria-hidden="true"
                                        width="48"
                                        height="48"
                                        class="w-full h-full object-contain"
                                    />
                                </div>
                                <div>
                                    <p class="font-heading font-bold text-base text-foreground leading-snug">
                                        CCT Guidance &amp; Counseling Unit
                                    </p>
                                    <p class="text-sm text-muted-foreground mt-0.5">
                                        Official portal for enrolled students and guidance staff.
                                    </p>
                                </div>
                            </figcaption>
                        </figure>
                    </aside>

                </div>
            </div>
        </section>

        <!-- Asymmetric Services Breakdown -->
        <section id="services" class="py-14 sm:py-20 bg-background">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
                    <div class="lg:col-span-4 space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-widest text-primary">
                            Portal Services
                        </p>
                        <h2 class="font-heading font-bold text-2xl sm:text-3xl text-foreground tracking-tight">
                            Built for Student Guidance Workflows
                        </h2>
                        <p class="text-base text-muted-foreground leading-relaxed">
                            Everything needed for annual individual inventory collection and campus guidance communication in one place.
                        </p>
                    </div>

                    <div class="lg:col-span-8 divide-y divide-border border-y border-border bg-surface rounded-token-lg">
                        <article class="p-6 sm:p-8 flex items-start gap-5">
                            <div class="w-11 h-11 rounded-token-md bg-muted text-primary flex items-center justify-center shrink-0 border border-border">
                                <x-icon name="document-text" class="w-6 h-6" />
                            </div>
                            <div class="space-y-1.5">
                                <h3 class="font-heading text-xl font-bold text-foreground">
                                    Scheduled Psychological &amp; Wellness Inventories
                                </h3>
                                <p class="text-base text-muted-foreground leading-relaxed">
                                    Submit your assigned year-level assessment during open institutional schedules, with progress saved automatically across sections.
                                </p>
                            </div>
                        </article>

                        <article class="p-6 sm:p-8 flex items-start gap-5">
                            <div class="w-11 h-11 rounded-token-md bg-muted text-primary flex items-center justify-center shrink-0 border border-border">
                                <x-icon name="megaphone" class="w-6 h-6" />
                            </div>
                            <div class="space-y-1.5">
                                <h3 class="font-heading text-xl font-bold text-foreground">
                                    Guidance Office Announcements &amp; Schedules
                                </h3>
                                <p class="text-base text-muted-foreground leading-relaxed">
                                    Read verified notices, counseling advisories, and assessment window deadlines published directly by guidance staff.
                                </p>
                            </div>
                        </article>

                        <article class="p-6 sm:p-8 flex items-start gap-5">
                            <div class="w-11 h-11 rounded-token-md bg-muted text-primary flex items-center justify-center shrink-0 border border-border">
                                <x-icon name="clock" class="w-6 h-6" />
                            </div>
                            <div class="space-y-1.5">
                                <h3 class="font-heading text-xl font-bold text-foreground">
                                    Personal Submission History
                                </h3>
                                <p class="text-base text-muted-foreground leading-relaxed">
                                    Access a dated record of your completed academic-year inventories and verify your submitted responses at any time.
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- Structured Process Band -->
        <section id="process" class="py-14 sm:py-20 bg-muted/50 border-y border-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-10">
                    <p class="text-xs font-semibold uppercase tracking-widest text-primary mb-2">
                        Student Workflow
                    </p>
                    <h2 class="font-heading font-bold text-2xl sm:text-3xl text-foreground tracking-tight">
                        Three Steps to Complete Your Inventory
                    </h2>
                </div>

                <ol class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <li class="bg-surface border border-border rounded-token-lg p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary-subtle px-2.5 py-1 rounded-token-sm border border-primary/20">
                                Step 01
                            </span>
                            <x-icon name="user-plus" class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Register Your Institutional Account</h3>
                        <p class="text-base text-muted-foreground leading-relaxed">
                            Sign up using your official City College of Tagaytay email address, student ID, program, and section.
                        </p>
                    </li>

                    <li class="bg-surface border border-border rounded-token-lg p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary-subtle px-2.5 py-1 rounded-token-sm border border-primary/20">
                                Step 02
                            </span>
                            <x-icon name="clipboard-document-check" class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Complete the Assigned Inventory</h3>
                        <p class="text-base text-muted-foreground leading-relaxed">
                            When your program's assessment window opens, review the privacy consent and answer each section honestly.
                        </p>
                    </li>

                    <li class="bg-surface border border-border rounded-token-lg p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-primary bg-primary-subtle px-2.5 py-1 rounded-token-sm border border-primary/20">
                                Step 03
                            </span>
                            <x-icon name="megaphone" class="w-5 h-5 text-muted-foreground" />
                        </div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Follow Guidance Announcements</h3>
                        <p class="text-base text-muted-foreground leading-relaxed">
                            Check your dashboard for counseling office advisories, follow-up schedules, and next-year section updates.
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Data Privacy Commitment -->
        <section id="privacy" class="py-14 sm:py-20 bg-background">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-token-lg bg-primary text-on-primary p-8 sm:p-10 lg:p-12 border border-primary-hover">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                        <div class="lg:col-span-8 space-y-3">
                            <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-on-primary/85">
                                <x-icon name="shield-check" class="w-4 h-4" />
                                <span>Confidentiality &amp; Data Protection</span>
                            </div>
                            <h2 class="font-heading font-bold text-2xl sm:text-3xl tracking-tight">
                                Protected Under the Data Privacy Act of 2012 (RA 10173)
                            </h2>
                            <p class="text-base text-on-primary/90 leading-relaxed max-w-3xl">
                                All student inventory responses and guidance profiles are handled with strict confidentiality and are accessible solely to authorized City College of Tagaytay Guidance and Counseling personnel.
                            </p>
                        </div>
                        <div class="lg:col-span-4 lg:text-right">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center gap-2 bg-surface text-primary font-semibold text-base px-6 py-3 rounded-token-md hover:bg-muted transition-colors">
                                        <span>Go to Dashboard</span>
                                        <x-icon name="arrow-right" class="w-4 h-4" />
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 bg-surface text-primary font-semibold text-base px-6 py-3 rounded-token-md hover:bg-muted transition-colors">
                                        <span>Sign In</span>
                                        <x-icon name="arrow-right" class="w-4 h-4" />
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Institutional Footer -->
    <footer class="bg-surface border-t border-border mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                <div class="md:col-span-6 space-y-2">
                    <div class="inline-flex items-center gap-3">
                        <img src="{{ asset('images/guidance-logo.png') }}" alt="City College of Tagaytay Guidance Seal" width="40" height="40" loading="lazy" decoding="async" class="h-10 w-10 object-contain" />
                        <div>
                            <h3 class="font-heading font-bold text-base text-foreground">
                                City College of Tagaytay Guidance and Counseling Services Unit
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Student Individual Inventory Management System
                            </p>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <h4 class="font-semibold text-xs uppercase tracking-wider text-muted-foreground mb-3">
                        Sections
                    </h4>
                    <ul class="space-y-2 text-sm font-medium text-foreground">
                        <li><a href="#services" class="hover:text-primary transition-colors">Portal Services</a></li>
                        <li><a href="#process" class="hover:text-primary transition-colors">How It Works</a></li>
                        <li><a href="#privacy" class="hover:text-primary transition-colors">Data Privacy (RA 10173)</a></li>
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h4 class="font-semibold text-xs uppercase tracking-wider text-muted-foreground mb-3">
                        Portal Access
                    </h4>
                    <ul class="space-y-2 text-sm font-medium text-foreground">
                        @if (Route::has('login'))
                            @auth
                                <li><a href="{{ url('/dashboard') }}" class="hover:text-primary transition-colors">Open Dashboard</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="hover:text-primary transition-colors">Sign In</a></li>
                                @if (Route::has('register'))
                                    <li><a href="{{ route('register') }}" class="hover:text-primary transition-colors">Student Registration</a></li>
                                @endif
                            @endauth
                        @endif
                    </ul>
                </div>
            </div>

            <div class="border-t border-border mt-8 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-muted-foreground">
                <p class="font-medium">
                    &copy; {{ date('Y') }} City College of Tagaytay. All rights reserved.
                </p>
                <p>
                    Guidance and Counseling Services Unit
                </p>
            </div>
        </div>
    </footer>

    <x-toast />
</body>
</html>
