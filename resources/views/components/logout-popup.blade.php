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
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
        <!-- Minimal Modal -->
        <div x-show="showModal" 
             class="bg-white rounded-xl shadow-xl p-6 text-center max-w-sm w-full mx-4"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <!-- Simple Success Icon -->
            <svg class="w-12 h-12 text-emerald-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            
            <h3 class="text-xl font-bold text-gray-900 mb-1">Goodbye, {{ $name }}</h3>
            <p class="text-sm text-gray-500">You have been successfully logged out.</p>
        </div>
    </div>
</div>
@endif
