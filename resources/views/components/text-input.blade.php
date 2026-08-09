@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-border focus:border-primary focus:ring-primary rounded-lg shadow-sm px-4 py-3 text-foreground transition-colors duration-200']) }}>
