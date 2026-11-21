@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center min-h-[44px] px-1 pt-1 border-b-2 border-primary text-sm lg:text-[15px] xl:text-base font-semibold leading-5 text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors duration-150 cursor-pointer'
            : 'inline-flex items-center min-h-[44px] px-1 pt-1 border-b-2 border-transparent text-sm lg:text-[15px] xl:text-base font-medium leading-5 text-muted-foreground hover:text-foreground hover:border-border-strong focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors duration-150 cursor-pointer';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
