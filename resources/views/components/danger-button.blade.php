<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3 bg-destructive border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-destructive/90 focus:bg-destructive/90 active:bg-destructive/95 focus:outline-none focus:ring-2 focus:ring-destructive focus:ring-offset-2 transition ease-in-out duration-200 shadow-sm cursor-pointer']) }}>
    {{ $slot }}
</button>
