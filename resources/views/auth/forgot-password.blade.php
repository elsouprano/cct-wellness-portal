<x-guest-layout title="Forgot Password">
    <div class="text-center mb-6">
        <h2 class="font-heading text-2xl font-bold text-primary tracking-tight">Forgot your password?</h2>
        <p class="text-sm text-[#1F1A1A]/65 mt-1">Enter your institutional email to receive a password reset link.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm text-center font-medium" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

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
                value="{{ old('email') }}" 
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

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="btn-primary w-full flex items-center justify-center min-h-[48px] text-base font-semibold shadow-md active:scale-[0.99] transition-all">
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
        
        <!-- Back to Login Button -->
        <div class="pt-1">
            <a href="{{ route('login') }}" class="w-full flex items-center justify-center min-h-[48px] px-4 border border-primary/30 text-primary font-semibold rounded-2xl hover:bg-primary/5 focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                Back to Login
            </a>
        </div>
    </form>
</x-guest-layout>
