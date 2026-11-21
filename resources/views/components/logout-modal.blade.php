<x-modal name="confirm-logout" focusable maxWidth="sm">
    <form method="POST" action="{{ route('logout') }}" class="p-6">
        @csrf

        <h2 class="font-heading text-lg font-bold text-foreground">
            {{ __('Confirm Logout') }}
        </h2>

        <p class="mt-1.5 text-sm text-muted-foreground">
            {{ __('Are you sure you want to log out of your account?') }}
        </p>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button>
                {{ __('Log Out') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
