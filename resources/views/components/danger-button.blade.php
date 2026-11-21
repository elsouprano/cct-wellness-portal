<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 bg-destructive border border-transparent rounded-token-md font-semibold text-sm text-on-primary tracking-wide hover:bg-destructive/90 active:bg-destructive focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-destructive focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150 shadow-xs cursor-pointer']) }}>
    {{ $slot }}
</button>
