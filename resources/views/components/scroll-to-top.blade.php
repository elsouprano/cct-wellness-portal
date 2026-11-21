<div x-data="scrollToTop()" 
     x-show="isVisible"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-6 right-6 z-[40]"
     x-cloak>
    <button type="button" @click="scrollToTop" 
            class="flex items-center justify-center min-w-[44px] min-h-[44px] w-11 h-11 bg-primary text-on-primary rounded-full shadow-md hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-ring transition-colors duration-150 cursor-pointer"
            aria-label="Scroll to top"
            title="Scroll to top">
        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('alpine:init', () => {
        Alpine.data('scrollToTop', () => ({
            isVisible: false,
            // The scrollable container might be window or a specific main element.
            // In staff layout, it's the main container. We'll listen to the window or closest scroll parent.
            init() {
                // Determine scroll container. If inside a flex-1 overflow-y-auto, use that. Otherwise use window.
                const scrollContainer = this.$el.closest('main') || document.querySelector('main.overflow-y-auto') || window;
                
                const checkScroll = (e) => {
                    const scrollPos = e.target.scrollTop || window.scrollY;
                    this.isVisible = scrollPos > 300;
                };

                // Add event listeners to both window and the main scroll container just to be safe
                window.addEventListener('scroll', checkScroll, { passive: true });
                if (scrollContainer !== window) {
                    scrollContainer.addEventListener('scroll', checkScroll, { passive: true });
                }
            },
            scrollToTop() {
                const scrollContainer = this.$el.closest('main') || document.querySelector('main.overflow-y-auto') || window;
                scrollContainer.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }));
    });
</script>
