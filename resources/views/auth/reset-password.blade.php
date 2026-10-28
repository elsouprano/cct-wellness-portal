@section('title', 'Reset Password')
<x-guest-layout title="Reset Password">
    <div class="text-center mb-6">
        <h2 class="font-heading text-2xl font-bold text-primary tracking-tight">Set New Password</h2>
        <p class="text-sm text-[#1F1A1A]/65 mt-1">Please create a strong password to secure your account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                {{ __('Email Address') }}
            </label>
            <input 
                id="email" 
                class="auth-input @error('email') border-destructive @enderror" 
                type="email" 
                name="email" 
                value="{{ old('email', $request->email) }}" 
                required 
                autofocus 
                autocomplete="username" 
                inputmode="email"
                placeholder="name@citycollegeoftagaytay.edu.ph"
                aria-required="true"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            />
            <x-input-error :messages="$errors->get('email')" id="email-error" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                {{ __('New Password') }}
            </label>
            <div class="relative">
                <input 
                    id="password" 
                    class="auth-input pr-12 @error('password') border-destructive @enderror" 
                    x-bind:type="show ? 'text' : 'password'" 
                    name="password" 
                    required 
                    autocomplete="new-password" 
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
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div x-data="{ show: false }">
            <label for="password_confirmation" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                {{ __('Confirm Password') }}
            </label>
            <div class="relative">
                <input 
                    id="password_confirmation" 
                    class="auth-input pr-12 @error('password_confirmation') border-destructive @enderror" 
                    x-bind:type="show ? 'text' : 'password'" 
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password" 
                    placeholder="••••••••" 
                    aria-required="true"
                    @error('password_confirmation') aria-invalid="true" aria-describedby="password-confirm-error" @enderror
                />
                <button 
                    type="button" 
                    @click="show = !show" 
                    class="touch-target absolute right-1 top-1/2 -translate-y-1/2 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary rounded-lg" 
                    :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                >
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" id="password-confirm-error" class="mt-1.5" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full flex items-center justify-center min-h-[48px] text-base font-semibold shadow-md active:scale-[0.99] transition-all">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
</x-guest-layout>
