@props([
    'caption' => null,
    'label' => 'Data table',
    'stackMobile' => false,
    'compactCards' => false,
    'empty' => false,
    'emptyTitle' => 'No records found',
    'emptyMessage' => 'Try adjusting your filters or check back later.',
])

@php
    $captionId = 'table-caption-' . \Illuminate\Support\Str::random(6);
@endphp

<div {{ $attributes->merge(['class' => 'data-table-shell bg-surface border border-border rounded-token-lg shadow-xs overflow-hidden']) }}>
    @isset($toolbar)
        <div class="px-5 py-3.5 border-b border-border bg-muted/30 flex flex-wrap items-center justify-between gap-3">
            {{ $toolbar }}
        </div>
    @endisset

    @if($empty)
        <div class="px-6 py-12 text-center">
            <x-icon name="document-text" class="w-10 h-10 text-muted-foreground/50 mx-auto mb-3" />
            <h3 class="font-heading text-lg font-semibold text-foreground">{{ $emptyTitle }}</h3>
            <p class="text-sm text-muted-foreground mt-1 max-w-md mx-auto">{{ $emptyMessage }}</p>
            @isset($emptyAction)
                <div class="mt-4">{{ $emptyAction }}</div>
            @endisset
        </div>
    @else
        <div
            tabindex="0"
            role="region"
            @if($caption) aria-labelledby="{{ $captionId }}" @else aria-label="{{ $label }}" @endif
            class="data-table-wrapper flex-1 min-h-0 focus-visible:outline-2 focus-visible:outline-primary"
            @if($stackMobile) data-stack-mobile="true" @endif
            @if($compactCards) data-compact-cards="true" @endif
        >
            <table class="min-w-full divide-y divide-border text-left">
                @if($caption)
                    <caption id="{{ $captionId }}" class="sr-only">{{ $caption }}</caption>
                @endif
                {{ $slot }}
            </table>
        </div>
    @endif

    @isset($footer)
        <div class="border-t border-border bg-muted/20">
            {{ $footer }}
        </div>
    @endisset
</div>
