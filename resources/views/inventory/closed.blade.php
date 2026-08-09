<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Inventory Assessment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg text-foreground text-center py-16">
                    <div class="mx-auto h-20 w-20 bg-primary/10 rounded-full flex items-center justify-center mb-6">
                        <svg class="h-10 w-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-3xl font-heading font-bold text-foreground">Assessment Closed</h3>
                    <p class="mt-2 text-foreground/70 text-lg max-w-lg mx-auto">
                        {{ $message ?? 'The assessment is not currently open for your year level and program.' }}
                    </p>
                    @if(isset($schedule))
                        <div class="mt-8 text-sm font-medium text-foreground/60 bg-muted inline-block px-6 py-3 rounded-xl border border-border">
                            Scheduled window: {{ \Carbon\Carbon::parse($schedule->open_date)->format('M d, Y') }} {{ $schedule->open_time }} 
                            to {{ \Carbon\Carbon::parse($schedule->close_date)->format('M d, Y') }} {{ $schedule->close_time }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
