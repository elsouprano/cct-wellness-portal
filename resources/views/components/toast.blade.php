<div x-data="toastNotification()" 
     role="region"
     aria-label="Notifications"
     aria-live="polite"
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
     @notify.window="addNotification($event.detail.type, $event.detail.message)">
     
    <template x-for="notification in notifications" :key="notification.id">
        <div x-show="notification.show"
             role="status"
             x-transition:enter="transform ease-out duration-200 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="min-w-[300px] max-w-sm w-full bg-surface shadow-md rounded-token-md pointer-events-auto border border-border overflow-hidden flex items-center p-4 border-l-4"
             :class="{
                 'border-l-success': notification.type === 'success',
                 'border-l-destructive': notification.type === 'error',
                 'border-l-info': notification.type === 'info',
                 'border-l-warning': notification.type === 'warning'
             }">
            
            <div class="flex-shrink-0">
                <svg x-show="notification.type === 'success'" aria-hidden="true" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg x-show="notification.type === 'error'" aria-hidden="true" class="h-5 w-5 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg x-show="notification.type === 'info'" aria-hidden="true" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg x-show="notification.type === 'warning'" aria-hidden="true" class="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-medium text-foreground" x-text="notification.message"></p>
            </div>
            <div class="ml-3 flex-shrink-0 flex">
                <button type="button" @click="removeNotification(notification.id)" class="min-w-[36px] min-h-[36px] bg-surface rounded-token-sm inline-flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-muted cursor-pointer transition-colors">
                    <span class="sr-only">Close notification</span>
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    (function() {
        const registerToast = () => {
            if (window.Alpine && !window._alpineToastRegistered) {
                window._alpineToastRegistered = true;
                Alpine.data('toastNotification', () => ({
                    notifications: [],
                    
                    init() {
                        // Listen for PHP session flashes on page load
                        @if(session()->has('success'))
                            @if(!str_contains(session('success'), 'Welcome back'))
                                this.addNotification('success', @js(session('success')));
                            @endif
                        @endif
                        
                        @if(session()->has('error'))
                            this.addNotification('error', @js(session('error')));
                        @endif

                        @if(session()->has('warning'))
                            this.addNotification('warning', @js(session('warning')));
                        @endif

                        @if(session()->has('status') && session('status') !== session('success'))
                            this.addNotification('info', @js(session('status')));
                        @endif

                        @if(session()->has('info') && session('info') !== session('success'))
                            this.addNotification('info', @js(session('info')));
                        @endif
                    },
                    
                    addNotification(type, message) {
                        const id = Date.now() + Math.random();
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
            }
        };

        if (window.Alpine) {
            registerToast();
        } else {
            document.addEventListener('alpine:init', registerToast);
        }
    })();
</script>
