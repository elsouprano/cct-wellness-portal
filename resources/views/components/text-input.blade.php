@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-[44px] bg-surface border border-border-strong focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-token-md shadow-2xs px-4 py-2.5 text-base text-foreground placeholder:text-muted-foreground/70 disabled:bg-muted/60 disabled:text-muted-foreground disabled:border-border disabled:cursor-not-allowed transition-colors duration-150']) }}>
