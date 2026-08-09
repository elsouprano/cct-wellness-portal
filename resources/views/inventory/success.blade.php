<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Submission Successful') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border text-center">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                
                <h1 class="font-heading text-3xl font-bold text-foreground mb-4">Thank You</h1>
                
                <p class="text-foreground/70 mb-8 text-lg">
                    Your responses have been successfully recorded. We appreciate you taking the time to complete the Individual Inventory.
                </p>

                <a href="{{ route('dashboard') }}" class="btn-primary inline-block">Return to Dashboard</a>
            </div>
        </div>
    </div>
</x-app-layout>
