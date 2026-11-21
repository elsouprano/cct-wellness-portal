@props([
    'variant' => 'neutral', // neutral, primary, success, warning, danger, info
    'icon' => null,
])

@php
    $variantClasses = match($variant) {
        'primary' => 'bg-primary-subtle text-primary border-primary/20',
        'success' => 'bg-success-subtle text-success-foreground border-success-border',
        'warning' => 'bg-warning-subtle text-warning-foreground border-warning-border',
        'danger' => 'bg-destructive-subtle text-destructive-foreground border-destructive-border',
        'info' => 'bg-info-subtle text-info-foreground border-info-border',
        default => 'bg-muted text-muted-foreground border-border',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-token-sm text-xs font-semibold border {$variantClasses}"]) }}>
    @if($icon)
        <x-icon :name="$icon" class="w-3.5 h-3.5 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</span>
