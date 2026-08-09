<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-6 py-3 bg-transparent border-2 border-primary rounded-lg font-semibold text-sm text-primary tracking-wide hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-200 cursor-pointer shadow-sm']) }}>
    {{ $slot }}
</button>
