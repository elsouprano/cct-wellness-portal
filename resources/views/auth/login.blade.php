@section('title', 'Login')
<x-auth-layout title="Login" :fit="true" :proportional="true">
    
    <div class="flex flex-col h-full w-full justify-between">
        <!-- 1. Header (Top): Logo + Titles -->
        <div class="flex flex-col items-center text-center shrink-0">
            <!-- Logo circle -->
            <x-logo-badge size="md" href="/" class="w-[4.75em] h-[4.75em] mb-[0.6em]" />
            
            <!-- "CCT Wellness Portal": Big & Bold -->
            <h1 class="font-heading text-[1.9em] font-bold text-[#8B1C1C] tracking-tight leading-tight">
                CCT Wellness Portal
            </h1>

            <!-- "Welcome Back" -->
            <h2 class="text-[1.25em] font-bold text-[#1F1A1A] mt-[0.2em] leading-tight">
                Welcome Back
            </h2>

            <!-- Subtitle -->
            <p class="text-[0.95em] text-[#6B6360] mt-[0.2em] mb-[1.2em] leading-tight">
                Log in to access your dashboard
            </p>
        </div>

        <!-- Session Status (e.g. Password Reset confirmation) -->
        <x-auth-session-status class="mb-[0.75em] p-[0.5em] rounded-[0.5em] bg-emerald-50 border border-emerald-200 text-emerald-800 text-[0.9em] text-center font-medium" :status="session('status')" />

        <!-- Server Success/Error Session Alert -->
        @if (session('success'))
            <div class="mb-[0.75em] p-[0.5em] rounded-[0.5em] bg-emerald-50 border border-emerald-200 text-emerald-800 text-[0.9em] text-center font-medium" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- 2. Form Block in the middle with my-auto -->
        <form id="login-form" method="POST" action="{{ route('login') }}" class="my-auto flex flex-col justify-center shrink-0" x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <!-- Email Address or Student ID -->
            <div>
                <label for="identifier" class="block text-[0.95em] font-semibold text-[#1F1A1A] mb-[0.35em]">
                    {{ __('Email Address or Student ID') }}
                </label>
                <input 
                    id="identifier" 
                    class="auth-input h-[2.9em] min-h-[46px] rounded-[0.5em] text-[1em] px-[1em] @error('identifier') border-destructive @enderror" 
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
                <x-input-error :messages="$errors->get('identifier')" id="identifier-error" class="mt-[0.25em] text-[0.85em]" />
            </div>

            <!-- Password -->
            <div class="mt-[1.1em]" x-data="{ show: false }">
                <div class="flex items-center justify-between mb-[0.35em]">
                    <label for="password" class="block text-[0.95em] font-semibold text-[#1F1A1A]">
                        {{ __('Password') }}
                    </label>
                </div>
                
                <div class="relative">
                    <input 
                        id="password" 
                        class="auth-input h-[2.9em] min-h-[46px] rounded-[0.5em] text-[1em] px-[1em] pr-[3em] @error('password') border-destructive @enderror" 
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
                        class="absolute right-[0.25em] top-1/2 -translate-y-1/2 w-[2.5em] h-[2.5em] flex items-center justify-center text-[#1F1A1A]/40 hover:text-[#8B1C1C] transition-colors focus:outline-none focus:text-[#8B1C1C] rounded-[0.5em] cursor-pointer" 
                        :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                    >
                        <!-- Eye Icon (Show) -->
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[1.25em] h-[1.25em]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <!-- Eye Slash Icon (Hide) -->
                        <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[1.25em] h-[1.25em]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                
                <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-[0.25em] text-[0.85em]" />
            </div>

            <!-- Remember Me & Forgot Password Row -->
            <div class="flex items-center justify-between mt-[0.9em]">
                <label for="remember_me" class="inline-flex items-center gap-[0.5em] cursor-pointer select-none group">
                    <input id="remember_me" type="checkbox" class="auth-checkbox w-[1.2em] h-[1.2em] rounded-[0.25em]" name="remember">
                    <span class="text-[0.95em] font-medium text-[#6B6360] group-hover:text-[#1F1A1A] transition-colors">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[0.95em] font-semibold text-[#8B1C1C] hover:text-[#6B0C0F] hover:underline focus:outline-none focus:underline transition-colors py-[0.1em]">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="mt-[1.15em]">
                <div id="ajax-login-error" class="hidden text-[0.9em] text-destructive font-medium mb-[0.6em] text-center" role="alert"></div>
                
                <button 
                    id="login-submit-btn" 
                    type="submit" 
                    class="w-full h-[2.9em] min-h-[46px] rounded-[0.5em] bg-[#8B1C1C] hover:bg-[#6B0C0F] text-white text-[1.1em] font-bold shadow-md active:scale-[0.99] transition-colors flex items-center justify-center cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-[#8B1C1C]/20"
                >
                    <span>{{ __('Log in') }}</span>
                </button>
            </div>

            <!-- Register Link -->
            @if (Route::has('register'))
                <div class="text-center text-[0.95em] text-[#6B6360] mt-[0.9em]">
                    <span>Don't have an account?</span>
                    <a href="{{ route('register') }}" class="font-bold text-[#8B1C1C] hover:text-[#6B0C0F] hover:underline ml-[0.25em] focus:outline-none focus:underline transition-colors">
                        Register here
                    </a>
                </div>
            @endif
        </form>

        <!-- 3. Footer (Bottom, mt-auto): Divider with privacy line -->
        <div class="mt-auto shrink-0">
            <div class="border-t border-[#E8E1D5] my-[0.85em]"></div>
            <div class="flex items-center justify-center gap-[0.5em] text-center text-[0.8em] text-[#6B6360] leading-snug">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[1.25em] h-[1.25em] text-[#8B1C1C] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span>Secure access. All data is handled in strict compliance with RA 10173 (Data Privacy Act of 2012).</span>
            </div>
        </div>
    </div>

    <!-- Preserve Logout Popup Component -->
    <x-logout-popup />

</x-auth-layout>
