@props([
    'size' => 'md',
    'href' => '/',
])

@php
    $hasCustomWidth = str_contains($attributes->get('class', ''), 'w-');

    $sizeClasses = $hasCustomWidth ? '' : match($size) {
        'sm' => 'w-14 h-14',
        'lg' => 'w-[88px] h-[88px]',
        'md' => 'w-[88px] h-[88px]',
        default => 'w-[88px] h-[88px]',
    };

    $imgDim = match($size) {
        'sm' => '56',
        default => '88',
    };
@endphp

<div {{ $attributes->merge(['class' => "relative rounded-xl overflow-hidden bg-[#FFF2D0] ring-1 ring-[#8B1C1C]/15 shadow-sm flex items-center justify-center shrink-0 {$sizeClasses}"]) }}>
    @if ($href)
        <a href="{{ $href }}" class="block w-full h-full rounded-xl overflow-hidden focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" aria-label="{{ __('City College of Tagaytay Wellness Portal Home') }}">
            <img 
                src="{{ asset('images/guidance-logo.png') }}" 
                alt="{{ __('CCT Guidance Logo') }}" 
                width="{{ $imgDim }}" 
                height="{{ $imgDim }}" 
                loading="eager" 
                class="w-full h-full object-contain block pointer-events-none select-none" 
            />
        </a>
    @else
        <img 
            src="{{ asset('images/guidance-logo.png') }}" 
            alt="{{ __('CCT Guidance Logo') }}" 
            width="{{ $imgDim }}" 
            height="{{ $imgDim }}" 
            loading="eager" 
            class="w-full h-full object-contain block pointer-events-none select-none" 
        />
    @endif
</div>

