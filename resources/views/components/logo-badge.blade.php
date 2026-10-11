@props([
    'size' => 'md',
    'href' => '/',
])

@php
    $sizeClasses = match($size) {
        'sm' => 'w-14 h-14',
        'lg' => 'w-[5.5rem] h-[5.5rem]',
        default => 'w-[4.5rem] h-[4.5rem]',
    };

    $imgDim = match($size) {
        'sm' => '56',
        'lg' => '88',
        default => '72',
    };
@endphp

<div {{ $attributes->merge(['class' => "relative rounded-full overflow-hidden bg-[#FFF2D0] border-2 border-white shadow-md flex items-center justify-center shrink-0 {$sizeClasses}"]) }}>
    @if ($href)
        <a href="{{ $href }}" class="block w-full h-full rounded-full overflow-hidden focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" aria-label="{{ __('City College of Tagaytay Wellness Portal Home') }}">
            <img 
                src="{{ asset('images/guidance-logo.png') }}" 
                alt="{{ __('CCT Guidance Logo') }}" 
                width="{{ $imgDim }}" 
                height="{{ $imgDim }}" 
                loading="eager" 
                class="w-full h-full object-cover rounded-full block transform scale-[1.2] origin-center pointer-events-none select-none"
            />
        </a>
    @else
        <img 
            src="{{ asset('images/guidance-logo.png') }}" 
            alt="{{ __('CCT Guidance Logo') }}" 
            width="{{ $imgDim }}" 
            height="{{ $imgDim }}" 
            loading="eager" 
            class="w-full h-full object-cover rounded-full block transform scale-[1.2] origin-center pointer-events-none select-none"
        />
    @endif
</div>
