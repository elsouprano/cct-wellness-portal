<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CCT Wellness Portal') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Bulletproof Layout Styles (Bypassing Tailwind JIT Compiler) */
        body {
            background: radial-gradient(circle at top left, #FFFFFF, var(--color-background) 80%);
            background-attachment: fixed;
        }
        .subtle-bg { background-color: var(--color-muted); }
        .text-muted { color: rgba(44, 26, 26, 0.65); }
        
        /* Spacing & Grids */
        .page-container { padding: 4rem 1.5rem; max-width: 80rem; margin: 0 auto; }
        @media (min-width: 640px) { .page-container { padding: 4rem 3rem; } }
        @media (min-width: 1024px) { .page-container { padding: 6rem 6rem; } }
        
        .hero-layout { display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center; margin-bottom: 6rem; }
        @media (min-width: 1024px) { .hero-layout { grid-template-columns: 1fr 1fr; gap: 6rem; } }
        
        .features-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 6rem; max-width: 64rem; margin-left: auto; margin-right: auto; }
        @media (min-width: 768px) { .features-grid { grid-template-columns: 1fr 1fr; gap: 3rem; } }
        
        .steps-container { display: flex; flex-direction: column; gap: 2rem; max-width: 72rem; margin: 0 auto; }
        @media (min-width: 768px) { .steps-container { flex-direction: row; gap: 2rem; } }
        
        .privacy-box { display: flex; flex-direction: column; align-items: center; gap: 2rem; text-align: center; margin: 6rem auto; max-width: 64rem; padding: 3rem; background: #ffffff; border-radius: 2rem; border: 1px solid var(--color-border); box-shadow: var(--shadow-md); }
        @media (min-width: 768px) { .privacy-box { flex-direction: row; text-align: left; padding: 4rem; gap: 3rem; } }
        
        /* Component Specifics */
        .feature-card { background: #ffffff; border-radius: 2rem; padding: 2.5rem; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); transition: all 0.3s ease; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        
        .step-card { flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; background: #ffffff; padding: 2.5rem 1.5rem; border-radius: 2rem; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); transition: all 0.3s ease; }
        .step-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        
        .icon-box { width: 4rem; height: 4rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; color: var(--color-primary); background-color: var(--color-muted); }
        .step-icon-box { width: 5rem; height: 5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; color: var(--color-primary); background-color: var(--color-muted); transition: all 0.3s ease; }
        .step-card:hover .step-icon-box { background-color: var(--color-primary); color: #ffffff; }
        
        .privacy-icon { width: 5rem; height: 5rem; border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--color-primary); background-color: var(--color-muted); flex-shrink: 0; }
        
        .hero-graphic { width: 18rem; height: 18rem; border-radius: 50%; background: #ffffff; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: center; position: relative; z-index: 10; margin-left: auto; }
        @media (min-width: 1024px) { .hero-graphic { width: 22rem; height: 22rem; } }
        
        /* Scroll Reveal Animation */
        @media (prefers-reduced-motion: no-preference) {
            .reveal-on-scroll {
                opacity: 0;
                transform: translateY(2rem);
                transition: opacity 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            }
            .reveal-on-scroll.is-visible {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="font-sans antialiased text-foreground min-h-screen flex flex-col relative selection:bg-primary/20 selection:text-primary">

    <!-- Main Content wrapper -->
    <main class="flex-grow page-container relative z-10 w-full">
        
        <!-- Hero Section -->
        <div class="hero-layout reveal-on-scroll">
            
            <!-- Left: Typography & CTAs -->
            <div class="text-left" style="display: flex; flex-direction: column; gap: 2rem;">
                <div class="inline-flex items-center">
                    <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" style="height: 4rem; width: auto; mix-blend-mode: multiply;" />
                </div>
                
                <h1 class="font-heading font-bold text-foreground leading-tight tracking-tight" style="font-size: clamp(2.5rem, 5vw, 4rem);">
                    Empowering Student <br/>
                    <span class="text-primary relative inline-block">
                        Well-Being
                    </span>
                </h1>
                
                <p class="text-muted leading-relaxed" style="font-size: 1.125rem; max-width: 36rem;">
                    The official psychological assessment platform for City College of Tagaytay. Complete your scheduled, year-level-specific self-assessments and stay informed with official guidance announcements.
                </p>
                
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem;">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn-primary flex items-center justify-center text-center" style="padding: 1rem 2rem; box-shadow: var(--shadow-md);">
                                Go to Dashboard
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem; margin-left: 0.5rem;"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-primary flex items-center justify-center text-center" style="padding: 1rem 2rem; box-shadow: var(--shadow-md);">
                                Log In
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem; margin-left: 0.5rem;"><path fill-rule="evenodd" d="M10 2a.75.75 0 01.75.75v7.5a.75.75 0 01-1.5 0v-7.5A.75.75 0 0110 2zM5.404 4.108a.75.75 0 010 1.06 7.5 7.5 0 109.192 0 .75.75 0 111.06-1.06 9 9 0 11-11.312 0 .75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-secondary flex items-center justify-center text-center" style="padding: 1rem 2rem;">
                                    Register Account
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
            
            <!-- Right: Minimalist Graphic / Illustration -->
            <div class="hidden lg:flex justify-end relative">
                <div class="hero-graphic transition-transform hover:scale-105 duration-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="text-primary" style="width: 8rem; height: 8rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                    </svg>
                </div>
                <!-- Decorative rings around graphic -->
                <div style="position: absolute; width: 26rem; height: 26rem; border-radius: 50%; border: 1px solid rgba(139, 16, 20, 0.15); right: -2rem; bottom: -2rem; z-index: 0;"></div>
                <div style="position: absolute; width: 30rem; height: 30rem; border-radius: 50%; border: 1px solid rgba(139, 16, 20, 0.05); right: -4rem; bottom: -4rem; z-index: 0;"></div>
            </div>
        </div>
        
        <!-- Value Prop Cards -->
        <div class="features-grid reveal-on-scroll">
            <!-- Card 1 -->
            <div class="feature-card">
                <div class="icon-box shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="font-heading text-xl font-bold text-foreground mb-3">Tailored Self-Assessments</h3>
                <p class="text-muted leading-relaxed" style="font-size: 0.95rem;">
                    Complete scheduled psychological and wellness self-assessments specifically tailored to your current year level.
                </p>
            </div>
            
            <!-- Card 2 -->
            <div class="feature-card">
                <div class="icon-box shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
                    </svg>
                </div>
                <h3 class="font-heading text-xl font-bold text-foreground mb-3">Campus Announcements</h3>
                <p class="text-muted leading-relaxed" style="font-size: 0.95rem;">
                    Stay up to date with the latest official announcements, notices, and updates directly from the CCT Guidance Office.
                </p>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="reveal-on-scroll" style="margin-top: 8rem;">
            <div class="text-center" style="margin-bottom: 4rem;">
                <h2 class="font-heading font-bold text-foreground" style="font-size: 2.25rem;">How it Works</h2>
                <div style="width: 4rem; height: 4px; background: rgba(139, 16, 20, 0.3); margin: 1rem auto 0; border-radius: 99px;"></div>
            </div>
            
            <div class="steps-container">
                <!-- Step 1 -->
                <div class="step-card group">
                    <div class="step-icon-box shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 2rem; height: 2rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <h3 class="font-heading text-lg font-bold text-foreground mb-2">1. Register</h3>
                    <p class="text-muted" style="font-size: 0.95rem;">Create your secure account using your official CCT credentials.</p>
                </div>
                
                <!-- Step 2 -->
                <div class="step-card group">
                    <div class="step-icon-box shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 2rem; height: 2rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                        </svg>
                    </div>
                    <h3 class="font-heading text-lg font-bold text-foreground mb-2">2. Take Assessment</h3>
                    <p class="text-muted" style="font-size: 0.95rem;">Complete your scheduled, year-level specific self-assessment.</p>
                </div>
                
                <!-- Step 3 -->
                <div class="step-card group">
                    <div class="step-icon-box shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 2rem; height: 2rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                    <h3 class="font-heading text-lg font-bold text-foreground mb-2">3. Stay Updated</h3>
                    <p class="text-muted" style="font-size: 0.95rem;">Receive official announcements and updates from the Guidance Office.</p>
                </div>
            </div>
        </div>

        <!-- Privacy Section -->
        <div class="privacy-box reveal-on-scroll">
            <div class="privacy-icon shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 2.5rem; height: 2.5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
            </div>
            
            <div style="flex-grow: 1;">
                <h3 class="font-heading font-bold text-foreground" style="font-size: 1.5rem; margin-bottom: 0.75rem;">Your Privacy Matters</h3>
                <p class="text-muted leading-relaxed" style="font-size: 0.95rem; margin: 0;">
                    All responses and records are strictly confidential and handled in full accordance with RA 10173 (Data Privacy Act of 2012). Your assessment data is secured and reviewed exclusively by authorized City College of Tagaytay Guidance staff.
                </p>
            </div>
        </div>
        
    </main>
    
    <footer class="relative z-10 text-center bg-white/40 backdrop-blur-sm" style="padding: 4rem 1rem 3rem; border-top: 1px solid var(--color-border); margin-top: auto;">
        <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
            <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" style="height: 3rem; width: auto; mix-blend-mode: multiply; opacity: 0.8; filter: grayscale(100%); transition: all 0.3s ease;" onmouseover="this.style.filter='grayscale(0%)'" onmouseout="this.style.filter='grayscale(100%)'" />
        </div>
        <h4 class="font-heading font-bold text-foreground/90" style="font-size: 0.875rem; margin-bottom: 0.5rem;">City College of Tagaytay Guidance and Counseling Services Unit</h4>
        <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 2rem; max-width: 28rem; margin-left: auto; margin-right: auto;">Supporting student well-being and psychological health.</p>
        <p class="text-muted/60 tracking-wide uppercase font-semibold" style="font-size: 0.7rem; margin: 0;">&copy; {{ date('Y') }} City College of Tagaytay. All rights reserved.</p>
    </footer>

    <!-- Intersection Observer Script for Scroll Animations -->
    <script nonce="{{ $cspNonce }}">
        document.addEventListener("DOMContentLoaded", function() {
            // Respect prefers-reduced-motion
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.querySelectorAll('.reveal-on-scroll').forEach(function(el) {
                    el.classList.add('is-visible');
                    el.style.opacity = '1';
                    el.style.transform = 'none';
                });
                return;
            }

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { 
                root: null,
                threshold: 0.15,
                rootMargin: "0px 0px -50px 0px"
            });
            
            document.querySelectorAll('.reveal-on-scroll').forEach((el) => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
