<x-guest-layout title="Verify Your Email">
    <div class="mb-6 text-center">
        <div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-3 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>
        <h2 class="font-heading text-2xl font-bold text-primary tracking-tight">Verify Your Email</h2>
        <p class="text-sm text-[#1F1A1A]/75 mt-2 leading-relaxed">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5 text-emerald-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</p>
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-3 pt-4 border-t border-[#E8E1D5]/70">
        <form method="POST" action="{{ route('verification.send') }}" class="w-full">
            @csrf

            <button type="submit" class="btn-primary w-full flex items-center justify-center min-h-[48px] text-base font-semibold shadow-md active:scale-[0.99] transition-all">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center min-h-[44px] text-sm text-[#1F1A1A]/70 hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20 rounded-xl py-2">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>

