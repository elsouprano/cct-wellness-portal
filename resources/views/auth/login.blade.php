<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CCT Wellness Portal') }} - Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Full-Bleed Background Layout */
        .login-layout { 
            display: flex; 
            min-height: 100vh; 
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            background-image: url('{{ asset('images/bg.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        
        /* Deep Overlay to ensure card readability */
        .login-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom right, rgba(15, 23, 42, 0.7), rgba(21, 128, 61, 0.4));
            z-index: 0;
        }

        .text-muted { color: rgba(15, 23, 42, 0.65); }
        .text-foreground { color: rgba(15, 23, 42, 1); }

        /* Consistent robust form inputs */
        .custom-input { 
            width: 100%; 
            border: 1px solid rgba(15, 23, 42, 0.2); /* Explicitly visible border */
            border-radius: 0.5rem; 
            padding: 0.75rem 1rem; 
            background-color: #ffffff; 
            transition: all 0.2s ease; 
            outline: none;
            color: var(--color-foreground, #0f172a);
            font-size: 0.95rem;
        }
        .custom-input:focus { 
            border-color: var(--color-primary); 
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2); 
        }
        
        .custom-checkbox {
            accent-color: var(--color-primary);
            width: 1.125rem;
            height: 1.125rem;
            cursor: pointer;
            border: 1px solid rgba(15, 23, 42, 0.3);
            border-radius: 0.25rem;
        }
        
        /* Premium Glassmorphism Card */
        .login-card { 
            width: 100%; 
            max-width: 28rem; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2.25rem; 
            border-radius: 1.25rem; 
            border: 1px solid rgba(255, 255, 255, 0.8); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.3) inset;
            position: relative;
            z-index: 10;
        }
        
        /* Deliberate Logo Badge to absorb the cream background smoothly */
        .logo-badge { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 1rem; 
            width: 4rem;
            height: 4rem;
            background-color: var(--color-muted, #f8f9fa); /* Cream/Soft background */
            border-radius: 50%;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 2px solid #ffffff;
        }
        
        .logo-img { 
            height: 2.5rem; 
            width: auto; 
            mix-blend-mode: multiply; /* Blends cream image bg perfectly into the badge bg */
        }
    </style>
</head>
<body class="font-sans antialiased selection:bg-primary/20">

    <div class="login-layout">
        <!-- Overlay -->
        <div class="login-overlay"></div>
        
        <!-- Login Form Card -->
        <div class="login-card">
            
            <!-- Logo Badge -->
            <div class="logo-badge">
                <a href="/">
                    <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" class="logo-img" />
                </a>
            </div>
            
            <div style="margin-bottom: 1.5rem; text-align: center;">
                <h2 class="font-heading text-primary" style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem; letter-spacing: -0.025em;">CCT Wellness Portal</h2>
                <p class="text-foreground" style="font-weight: 600; font-size: 1rem; margin-bottom: 0.125rem;">Welcome Back</p>
                <p class="text-muted" style="font-size: 0.875rem;">Log in to access your dashboard</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status style="margin-bottom: 1rem;" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf

                <!-- Identifier Address -->
                <div>
                    <label for="identifier" class="text-foreground" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Email Address or Student ID') }}</label>
                    <input id="identifier" class="custom-input" type="text" name="identifier" value="{{ old('identifier') }}" required autofocus autocomplete="username" placeholder="name@cct.edu.ph or 2023010305" />
                    <x-input-error :messages="$errors->get('identifier')" style="margin-top: 0.25rem;" />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="text-foreground" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Password') }}</label>
                    <input id="password" class="custom-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" style="margin-top: 0.25rem;" />
                </div>

                <!-- Remember Me & Forgot Password Row -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.125rem; margin-bottom: 0.25rem;">
                    <label for="remember_me" style="display: inline-flex; align-items: center; cursor: pointer;" class="group">
                        <input id="remember_me" type="checkbox" class="custom-checkbox" name="remember">
                        <span class="text-muted" style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500;">{{ __('Remember me') }}</span>
                    </label>
                    
                    @if (Route::has('password.request'))
                        <a style="color: var(--color-primary); font-size: 0.875rem; font-weight: 600; text-decoration: none;" href="{{ route('password.request') }}" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <div style="margin-top: 0.25rem;">
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 0.75rem; display: flex; justify-content: center; box-shadow: 0 4px 6px -1px rgba(21, 128, 61, 0.2); font-weight: 600; font-size: 1rem; border-radius: 0.5rem; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px -1px rgba(21, 128, 61, 0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(21, 128, 61, 0.2)';">
                        {{ __('Log in') }}
                    </button>
                </div>
                
                <!-- Register Link -->
                @if (Route::has('register'))
                <p style="text-align: center; font-size: 0.875rem; margin-top: 0.25rem; color: rgba(15, 23, 42, 0.65);">
                    Don't have an account? 
                    <a href="{{ route('register') }}" style="color: var(--color-primary); font-weight: 600; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Register here</a>
                </p>
                @endif
            </form>
            
            <!-- Privacy Security Footnote -->
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(15, 23, 42, 0.1); display: flex; align-items: flex-start; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1rem; height: 1rem; color: var(--color-primary); flex-shrink: 0; margin-top: 0.125rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <p style="font-size: 0.75rem; color: rgba(15, 23, 42, 0.55); line-height: 1.4; margin: 0;">
                    Secure access. All data is handled in strict compliance with RA 10173 (Data Privacy Act of 2012).
                </p>
            </div>
            
        </div>
    </div>

</body>
</html>
