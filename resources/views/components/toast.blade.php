<<<<<<< HEAD
<div x-data="toastNotification()" 
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
     @notify.window="addNotification($event.detail.type, $event.detail.message)">
     
    <template x-for="notification in notifications" :key="notification.id">
        <div x-show="notification.show"
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="min-w-[320px] max-w-sm w-full bg-white shadow-lg rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden flex items-center p-4 border-l-4"
             :class="{
                 'border-green-500': notification.type === 'success',
                 'border-red-500': notification.type === 'error',
                 'border-blue-500': notification.type === 'info',
                 'border-yellow-500': notification.type === 'warning'
             }">
            
            <div class="flex-shrink-0">
                <!-- Success Icon -->
                <svg x-show="notification.type === 'success'" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- Error Icon -->
                <svg x-show="notification.type === 'error'" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- Info Icon -->
                <svg x-show="notification.type === 'info'" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- Warning Icon -->
                <svg x-show="notification.type === 'warning'" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-foreground" x-text="notification.message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="removeNotification(notification.id)" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastNotification', () => ({
            notifications: [],
            
            init() {
                // Listen for PHP session flashes on page load
                @if(session()->has('success'))
                    this.addNotification('success', '{{ session('success') }}');
                @endif
                
                @if(session()->has('error'))
                    this.addNotification('error', '{{ session('error') }}');
                @endif

                @if(session()->has('status'))
                    this.addNotification('info', '{{ session('status') }}');
                @endif
            },
            
            addNotification(type, message) {
                const id = Date.now();
                this.notifications.push({
                    id: id,
                    type: type,
                    message: message,
                    show: true
                });
                
                // Auto remove after 4 seconds
                setTimeout(() => {
                    this.removeNotification(id);
                }, 4000);
            },
            
            removeNotification(id) {
                const index = this.notifications.findIndex(n => n.id === id);
                if (index !== -1) {
                    this.notifications[index].show = false;
                    setTimeout(() => {
                        this.notifications = this.notifications.filter(n => n.id !== id);
                    }, 300); // Wait for animation
                }
            }
        }));
    });
</script>
=======
<div
    x-data="{ show: false, message: '', type: 'success' }"
    x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type || 'success'; setTimeout(() => show = false, 3000)"
    x-init="
        @if(session('success'))
            show = true; message = '{{ addslashes(session('success')) }}'; type = 'success'; setTimeout(() => show = false, 3000);
        @elseif(session('status'))
            show = true; message = '{{ addslashes(session('status')) }}'; type = 'success'; setTimeout(() => show = false, 3000);
        @elseif(session('error'))
            show = true; message = '{{ addslashes(session('error')) }}'; type = 'error'; setTimeout(() => show = false, 3000);
        @endif
    "
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
    class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:items-start z-[100] justify-end"
>
    <div class="max-w-sm w-full bg-white shadow-lg rounded-xl pointer-events-auto ring-1 ring-black/5 overflow-hidden flex items-center p-4 gap-3 border-l-4"
         :class="type === 'success' ? 'border-primary' : 'border-red-500'">
        <div class="flex-shrink-0">
            <svg x-show="type === 'success'" class="h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg style="display: none;" x-show="type === 'error'" class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-2 w-0 flex-1 pt-0.5">
            <p class="text-sm font-semibold text-foreground leading-tight" x-text="message"></p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
            <button @click="show = false" class="bg-white rounded-md inline-flex text-foreground/40 hover:text-foreground/70 focus:outline-none transition-colors">
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</div>
>>>>>>> 2dd3c26381d3a8605dd001bfac524362e84b137d
