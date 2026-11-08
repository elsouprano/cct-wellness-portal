@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-foreground mb-1' . ($required ? ' label-required' : '')]) }}>
    {{ $value ?? $slot }}
</label>
