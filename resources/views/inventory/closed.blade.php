@section('title', 'Assessment Closed')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Student Individual Inventory') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl border border-border p-8 text-center shadow-xs">
            <div class="mx-auto h-16 w-16 bg-primary/10 rounded-full flex items-center justify-center mb-5">
                <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h3 class="text-2xl font-heading font-bold text-foreground">Assessment Closed</h3>
            <p class="mt-2 text-foreground/70 text-sm sm:text-base max-w-lg mx-auto">
                {{ $message ?? 'The assessment is not currently open for your year level and program.' }}
            </p>
            @if(isset($schedule))
                <div class="mt-6 text-sm font-medium text-foreground/70 bg-muted inline-block px-5 py-2.5 rounded-xl border border-border">
                    Scheduled window: {{ \Carbon\Carbon::parse($schedule->open_date)->format('M d, Y') }} {{ $schedule->open_time }} 
                    to {{ \Carbon\Carbon::parse($schedule->close_date)->format('M d, Y') }} {{ $schedule->close_time }}
                </div>
            @endif
            <div class="mt-6">
                <a href="{{ route('dashboard') }}" class="btn-primary inline-block text-sm">Return to Home</a>
            </div>
        </div>
    </div>
</x-app-layout>
