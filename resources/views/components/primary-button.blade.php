<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3 bg-primary border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-primary/90 focus:bg-primary/90 active:bg-primary/95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-200 shadow-sm cursor-pointer']) }}>
    {{ $slot }}
</button>
