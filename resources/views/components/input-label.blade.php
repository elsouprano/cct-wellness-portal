@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-foreground mb-1']) }}>
    {{ $value ?? $slot }}
</label>
