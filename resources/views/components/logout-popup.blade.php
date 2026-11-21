@php
    $popupData = session('logout_popup');
    $shouldShow = false;
    $message = 'You have been successfully logged out.';
    $name = 'User';
    
    if ($popupData) {
        $shouldShow = true;
        $message = $popupData['message'] ?? $message;
        $name = $popupData['name'] ?? $name;
    }
@endphp

@if($shouldShow)
<div x-data="{
        showModal: true,
        init() {
            setTimeout(() => {
                this.closeModal();
            }, 2000);
        },
        closeModal() {
            this.showModal = false;
        }
     }"
     x-cloak>
    
    <!-- Backdrop -->
    <div x-show="showModal" 
         role="status"
         aria-live="polite"
         class="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
        <!-- Minimal Modal -->
        <div x-show="showModal" 
             class="bg-surface border border-border rounded-token-lg shadow-xl p-6 text-center max-w-sm w-full mx-4"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2">
            
            <!-- Simple Success Icon -->
            <svg aria-hidden="true" class="w-12 h-12 text-success mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            
            <h3 class="font-heading text-xl font-bold text-foreground mb-1">Goodbye, {{ $name }}</h3>
            <p class="text-sm text-muted-foreground">You have been successfully logged out.</p>
        </div>
    </div>
</div>
@endif
