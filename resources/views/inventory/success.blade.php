@section('title', 'Student Individual Inventory Submitted')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Student Individual Inventory') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl border border-border p-8 text-center shadow-xs">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-primary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h1 class="font-heading text-2xl font-bold text-foreground mb-3">Thank You</h1>
            
            <p class="text-foreground/70 mb-6 text-sm sm:text-base max-w-lg mx-auto">
                Your responses have been successfully recorded. We appreciate you taking the time to complete the Student Individual Inventory.
            </p>

            <a href="{{ route('dashboard') }}" class="btn-primary inline-block text-sm">Return to Home</a>
        </div>
    </div>
</x-app-layout>
