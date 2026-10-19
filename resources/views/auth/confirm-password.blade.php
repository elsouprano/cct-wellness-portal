@section('title', 'Confirm Password')
<x-guest-layout title="Confirm Password">
    <div class="text-center mb-6">
        <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-3 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h2 class="font-heading text-2xl font-bold text-primary tracking-tight">Confirm Your Password</h2>
        <p class="text-sm text-[#1F1A1A]/70 mt-1 leading-relaxed">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4" x-data="{ showPassword: false }">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                {{ __('Password') }}
            </label>

            <div class="relative">
                <input 
                    id="password" 
                    class="auth-input pr-12 @error('password') border-destructive @enderror"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required 
                    autocomplete="current-password" 
                    placeholder="Enter your current password"
                    aria-required="true"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                />
                
                <button 
                    type="button" 
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    :aria-pressed="showPassword.toString()"
                    class="absolute inset-y-0 right-0 w-11 h-12 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary"
                >
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-1.5" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full flex items-center justify-center min-h-[48px] text-base font-semibold shadow-md active:scale-[0.99] transition-all">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
</x-guest-layout>

