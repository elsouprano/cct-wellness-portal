<x-guest-layout>
    <div style="margin-bottom: 1.5rem; text-align: center;">
        <h2 class="font-heading text-primary" style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem; letter-spacing: -0.025em;">Forgot your password?</h2>
        <p class="text-muted" style="font-size: 0.875rem;">Enter your email to receive a password reset link.</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 0.5rem; background-color: #ecfdf5; border: 1px solid #10b981; color: #065f46; font-size: 0.875rem; font-weight: 500; text-align: center;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" style="display: flex; flex-direction: column; gap: 1rem;">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="text-foreground" style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Email Address') }}</label>
            <input id="email" class="custom-input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@citycollegeoftagaytay.edu.ph" />
            <x-input-error :messages="$errors->get('email')" style="margin-top: 0.25rem; color: #dc2626; font-size: 0.875rem;" />
        </div>

        <!-- Submit Button -->
        <div style="margin-top: 0.5rem;">
            <button type="submit" class="btn-primary" style="width: 100%; padding: 0.75rem; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 6px -1px rgba(139, 16, 20, 0.2); font-weight: 600; font-size: 1rem; border-radius: 0.5rem; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px -1px rgba(139, 16, 20, 0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(139, 16, 20, 0.2)';">
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
        
        <!-- Back to Login Button -->
        <div style="margin-top: 0.25rem;">
            <a href="{{ route('login') }}" style="width: 100%; display: flex; justify-content: center; align-items: center; padding: 0.75rem; border: 1px solid var(--color-primary, #8b1014); color: var(--color-primary, #8b1014); font-weight: 600; font-size: 1rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s; outline: none;" onmouseover="this.style.backgroundColor='rgba(139, 16, 20, 0.05)'" onmouseout="this.style.backgroundColor='transparent'" onfocus="this.style.boxShadow='0 0 0 3px rgba(139, 16, 20, 0.3)'" onblur="this.style.boxShadow='none'">
                Back to Login
            </a>
        </div>
    </form>
</x-guest-layout>
