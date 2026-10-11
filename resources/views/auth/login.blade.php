<x-auth-layout title="Login" :fit="true">
    
    <!-- Header Inside Card -->
    <div class="text-center mb-6">
        <div class="flex justify-center mb-4 lg:hidden">
            <x-logo-badge size="sm" href="/" class="w-14 h-14" />
        </div>
        <h1 class="font-heading text-2xl font-bold text-primary tracking-tight">Welcome Back</h1>
        <p class="text-sm text-[#1F1A1A]/65 mt-1">Log in to access your wellness dashboard</p>
    </div>

    <!-- Session Status (e.g. Password Reset confirmation) -->
    <x-auth-session-status class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm text-center font-medium" :status="session('status')" />

    <!-- Server Success/Error Session Alert -->
    @if (session('success'))
        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm text-center font-medium" role="status">
            {{ session('success') }}
        </div>
    @endif

    <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <!-- Email Address or Student ID -->
        <div>
            <label for="identifier" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                {{ __('Email Address or Student ID') }}
            </label>
            <input 
                id="identifier" 
                class="auth-input @error('identifier') border-destructive @enderror" 
                type="text" 
                name="identifier" 
                value="{{ old('identifier') }}" 
                required 
                autofocus 
                autocomplete="username" 
                inputmode="email"
                placeholder="name@citycollegeoftagaytay.edu.ph or 2023010305" 
                aria-required="true"
                @error('identifier') aria-invalid="true" aria-describedby="identifier-error" @enderror
            />
            <x-input-error :messages="$errors->get('identifier')" id="identifier-error" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-[#1F1A1A]">
                    {{ __('Password') }}
                </label>
            </div>
            
            <div class="relative">
                <input 
                    id="password" 
                    class="auth-input pr-12 @error('password') border-destructive @enderror" 
                    x-bind:type="show ? 'text' : 'password'" 
                    name="password" 
                    required 
                    autocomplete="current-password" 
                    placeholder="••••••••" 
                    aria-required="true"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                />
                
                <button 
                    type="button" 
                    @click="show = !show" 
                    class="touch-target absolute right-1 top-1/2 -translate-y-1/2 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary rounded-lg" 
                    :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                >
                    <!-- Eye Icon (Show) -->
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <!-- Eye Slash Icon (Hide) -->
                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            
            <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer select-none group touch-target">
                <input id="remember_me" type="checkbox" class="auth-checkbox" name="remember">
                <span class="text-sm font-medium text-[#1F1A1A]/75 group-hover:text-[#1F1A1A] transition-colors">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary hover:text-[#6B0C0F] hover:underline focus:outline-none focus:underline transition-colors py-1">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <div id="ajax-login-error" class="hidden text-sm text-destructive font-medium mb-3 text-center" role="alert"></div>
            
            <button 
                id="login-submit-btn" 
                type="submit" 
                class="btn-primary w-full flex items-center justify-center gap-2 min-h-[48px] text-base font-semibold shadow-md active:scale-[0.99] transition-all disabled:opacity-75 disabled:cursor-not-allowed"
            >
                <span>{{ __('Log in') }}</span>
            </button>
        </div>

        <!-- Register Link (Single thin divider here) -->
        @if (Route::has('register'))
            <div class="text-center text-sm text-[#1F1A1A]/70 pt-4 border-t border-[#E8E1D5]">
                <span>Don't have an account?</span>
                <a href="{{ route('register') }}" class="font-bold text-primary hover:text-[#6B0C0F] hover:underline ml-1 focus:outline-none focus:underline transition-colors">
                    Register here
                </a>
            </div>
        @endif
    </form>

    <!-- Privacy Security Footnote (No duplicate divider) -->
    <div class="mt-4 flex items-center justify-center gap-1.5 text-center text-xs text-[#1F1A1A]/55">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-primary shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
        <span>Protected under RA 10173 (Data Privacy Act of 2012)</span>
    </div>

    <!-- Preserve Logout Popup Component -->
    <x-logout-popup />

</x-auth-layout>
