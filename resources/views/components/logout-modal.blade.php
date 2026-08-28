<x-modal name="confirm-logout" focusable maxWidth="sm">
    <form method="POST" action="{{ route('logout') }}" class="p-6">
        @csrf

        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Confirm Logout') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Are you sure you want to log out of your account?') }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ __('Log Out') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
