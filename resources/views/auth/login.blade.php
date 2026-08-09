<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="font-heading text-2xl font-bold text-primary">Welcome Back</h2>
        <p class="text-sm text-foreground/70 mt-2">Log in to your account</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-[var(--space-md)]">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Email') }}</label>
            <input id="email" class="input-field w-full" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Password') }}</label>
            <input id="password" class="input-field w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Remember Me -->
        <div class="block pt-[var(--space-xs)]">
            <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                <input id="remember_me" type="checkbox" class="rounded border-border text-primary shadow-sm focus:ring-primary focus:ring-offset-2 transition-colors cursor-pointer" name="remember">
                <span class="ms-2 text-sm text-foreground/80 group-hover:text-foreground transition-colors">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-[var(--space-md)] gap-4">
            @if (Route::has('password.request'))
                <a class="text-sm text-secondary hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-md" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <button type="submit" class="btn-primary w-full sm:w-auto">
                {{ __('Log in') }}
            </button>
        </div>
    </form>
</x-guest-layout>
