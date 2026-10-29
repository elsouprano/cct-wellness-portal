@props(['active'])

@php
$classes = ($active ?? false)
            ? 'h-12 min-h-[48px] px-3.5 rounded-xl flex items-center gap-3 relative transition-colors bg-primary/5 text-primary font-semibold before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-primary before:rounded-r focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/20'
            : 'h-12 min-h-[48px] px-3.5 rounded-xl flex items-center gap-3 relative transition-colors text-foreground/70 font-medium [@media(hover:hover)]:hover:text-foreground [@media(hover:hover)]:hover:bg-muted active:bg-muted/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/20';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
