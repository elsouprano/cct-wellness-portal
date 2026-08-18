<button x-data="{ loading: false }" 
        x-on:click="if($el.closest('form') && $el.closest('form').checkValidity()) { setTimeout(() => loading = true, 10); }" 
        x-bind:disabled="loading" 
        {{ $attributes->merge(['type' => 'submit', 'class' => 'relative inline-flex items-center justify-center px-6 py-3 bg-primary border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-primary/90 focus:bg-primary/90 active:bg-primary/95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-200 shadow-sm cursor-pointer disabled:opacity-75 disabled:cursor-wait']) }}>
    <svg x-show="loading" class="absolute animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span :class="{'opacity-0': loading}" class="inline-flex items-center justify-center transition-opacity duration-200">{{ $slot }}</span>
</button>
