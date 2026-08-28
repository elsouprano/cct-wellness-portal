<x-guest-layout>
    <div x-data="{ logoutModalOpen: false }">
    <div class="mb-6 text-center">
        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>
        <h2 class="font-heading text-2xl font-bold text-primary">Verify Your Email</h2>
        <p class="text-sm text-foreground/80 mt-3 leading-relaxed">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-sm text-green-800 flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</p>
        </div>
    @endif

    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-4 border-t border-border">
        <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
            @csrf

            <button type="submit" class="btn-primary w-full sm:w-auto">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <button type="button" x-on:click.prevent="logoutModalOpen = true" class="w-full sm:w-auto text-center sm:text-right text-sm text-secondary hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-md py-2">
            {{ __('Log Out') }}
        </button>
    </div>

    <!-- Logout Modal Built-in -->
    <div x-show="logoutModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
        <div x-show="logoutModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 transform transition-all" 
             @click="logoutModalOpen = false">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div x-show="logoutModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-sm mx-auto relative z-50">
            <form method="POST" action="{{ route('logout') }}" class="p-6">
                @csrf
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Confirm Logout') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Are you sure you want to log out of your account?') }}
                </p>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button @click="logoutModalOpen = false" type="button">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-danger-button class="ms-3" type="submit">
                        {{ __('Log Out') }}
                    </x-danger-button>
                </div>
            </form>
        </div>
    </div>
    </div>
</x-guest-layout>
