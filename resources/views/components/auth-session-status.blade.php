@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-success-foreground', 'role' => 'status']) }}>
        {{ $status }}
    </div>
@endif
