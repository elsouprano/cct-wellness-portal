<template x-teleport="body">
    <div
        x-data="{ 
            isOpen: false, 
            deleteUrl: '', 
            title: '', 
            message: ''
        }"
        @open-delete-modal.window="
            isOpen = true;
            deleteUrl = $event.detail.url;
            title = $event.detail.title;
            message = $event.detail.message;
            document.body.classList.add('overflow-y-hidden');
        "
        @close-delete-modal.window="
            isOpen = false;
            document.body.classList.remove('overflow-y-hidden');
        "
        @keydown.escape.window="$dispatch('close-delete-modal')"
        x-show="isOpen"
        style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0"
    >
    <!-- Backdrop -->
    <div 
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
        @click="$dispatch('close-delete-modal')"
    ></div>

    <!-- Modal Panel -->
    <div 
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative bg-white rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md border border-border"
        @click.stop
    >
        <div class="p-8">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-destructive/10 rounded-full flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-destructive">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-heading font-bold text-foreground" x-text="title"></h3>
            </div>
            
            <p class="text-foreground/70 text-lg leading-relaxed mb-8" x-text="message"></p>

            <form method="POST" :action="deleteUrl">
                @csrf
                @method('DELETE')
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full">
                    <button type="button" @click="$dispatch('close-delete-modal')" class="btn-secondary w-full">
                        Cancel
                    </button>
                    <button type="submit" class="w-full bg-destructive/10 text-destructive border-2 border-destructive/20 hover:bg-destructive hover:text-white px-4 py-3 rounded-xl font-semibold transition-all duration-200 cursor-pointer flex justify-center items-center">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</template>
