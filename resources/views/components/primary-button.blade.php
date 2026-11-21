<button x-data="{ loading: false }" 
        @confirm-modal-cancelled.window="loading = false"
        @open-confirm-modal.window="loading = false"
        x-on:click="const f = $el.closest('form'); if (f && f.checkValidity() && (!f.dataset.confirm || f.dataset.confirmed === 'true')) { setTimeout(() => loading = true, 10); }" 
        x-bind:disabled="loading" 
        {{ $attributes->merge(['type' => 'submit', 'class' => 'relative inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 bg-primary border border-transparent rounded-token-md font-semibold text-sm text-on-primary tracking-wide hover:bg-primary-hover active:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors duration-150 shadow-xs cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed']) }}>
    <svg x-show="loading" x-cloak aria-hidden="true" class="absolute animate-spin h-5 w-5 text-on-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span :class="{'opacity-0': loading}" class="inline-flex items-center justify-center transition-opacity duration-150">{{ $slot }}</span>
</button>
