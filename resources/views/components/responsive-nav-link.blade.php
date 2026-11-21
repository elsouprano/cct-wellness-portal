@props(['active'])

@php
$classes = ($active ?? false)
            ? 'min-h-[44px] px-3.5 py-2.5 rounded-token-md flex items-center gap-3 relative transition-colors duration-150 bg-primary-subtle text-primary font-semibold before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-primary before:rounded-r focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring cursor-pointer'
            : 'min-h-[44px] px-3.5 py-2.5 rounded-token-md flex items-center gap-3 relative transition-colors duration-150 text-muted-foreground font-medium hover:text-foreground hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring cursor-pointer';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
