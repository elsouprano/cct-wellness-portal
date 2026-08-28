<div x-data="commandPalette()"
     @keydown.window.prevent.ctrl.k="toggle()"
     @keydown.window.prevent.cmd.k="toggle()"
     class="relative z-[100]"
     style="display: none;"
     x-show="isOpen">
     
    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
         @click="close()"></div>

    <!-- Modal Panel -->
    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="close()"
             class="mx-auto max-w-xl transform divide-y divide-border overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all">
             
            <div class="relative">
                <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-foreground/40" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" 
                       x-ref="searchInput"
                       x-model="search"
                       @keydown.down.prevent="selectNext()"
                       @keydown.up.prevent="selectPrevious()"
                       @keydown.enter.prevent="goToSelected()"
                       @keydown.escape.prevent="close()"
                       class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-foreground placeholder:text-foreground/40 focus:ring-0 sm:text-sm" 
                       placeholder="Search pages or press ESC to close..." 
                       role="combobox" aria-expanded="false" aria-controls="options">
            </div>

            <!-- Results -->
            <ul class="max-h-80 scroll-py-2 overflow-y-auto py-2 text-sm text-foreground/80" id="options" role="listbox">
                <template x-for="(item, index) in filteredItems" :key="item.id">
                    <li class="cursor-pointer select-none px-4 py-2 hover:bg-primary/5" 
                        :class="{'bg-primary/10 text-primary': selectedIndex === index}"
                        @click="goTo(item.url)"
                        @mouseenter="selectedIndex = index"
                        role="option">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-white" x-html="item.icon"></span>
                            <div class="flex-auto">
                                <p class="font-medium text-foreground" x-text="item.name" :class="{'text-primary': selectedIndex === index}"></p>
                                <p class="text-xs text-foreground/50" x-text="item.description"></p>
                            </div>
                        </div>
                    </li>
                </template>
                
                <li x-show="filteredItems.length === 0" class="p-4 text-center text-sm text-foreground/50">
                    No results found for "<span x-text="search"></span>"
                </li>
            </ul>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('alpine:init', () => {
        Alpine.data('commandPalette', () => ({
            isOpen: false,
            search: '',
            selectedIndex: 0,
            items: [
                { id: 1, name: 'Dashboard', description: 'Overview and quick stats', url: '{{ route("dashboard") }}', icon: '<svg class="w-4 h-4 text-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>' },
                { id: 2, name: 'Question Bank', description: 'Manage categories and question items', url: '{{ route("question-bank.index") }}', icon: '<svg class="w-4 h-4 text-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>' },
                { id: 3, name: 'Submissions', description: 'View student inventory results', url: '{{ route("staff.inventory.index") }}', icon: '<svg class="w-4 h-4 text-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>' },
                { id: 4, name: 'Analytics', description: 'Reports and data visualization', url: '{{ route("analytics.index") }}', icon: '<svg class="w-4 h-4 text-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>' },
                { id: 5, name: 'Log Out', description: 'Sign out of your account', url: 'javascript:window.dispatchEvent(new CustomEvent("open-modal", { detail: "confirm-logout" }));', icon: '<svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>' }
            ],
            
            get filteredItems() {
                if (this.search === '') {
                    return this.items;
                }
                return this.items.filter(item => 
                    item.name.toLowerCase().includes(this.search.toLowerCase()) || 
                    item.description.toLowerCase().includes(this.search.toLowerCase())
                );
            },
            
            toggle() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    this.search = '';
                    this.selectedIndex = 0;
                    setTimeout(() => this.$refs.searchInput.focus(), 50);
                }
            },
            
            close() {
                this.isOpen = false;
            },
            
            selectNext() {
                if (this.selectedIndex < this.filteredItems.length - 1) {
                    this.selectedIndex++;
                    this.scrollToSelected();
                }
            },
            
            selectPrevious() {
                if (this.selectedIndex > 0) {
                    this.selectedIndex--;
                    this.scrollToSelected();
                }
            },
            
            scrollToSelected() {
                const listbox = document.getElementById('options');
                if (!listbox) return;
                const activeItem = listbox.children[this.selectedIndex];
                if (activeItem) {
                    const listboxRect = listbox.getBoundingClientRect();
                    const activeItemRect = activeItem.getBoundingClientRect();

                    if (activeItemRect.bottom > listboxRect.bottom) {
                        listbox.scrollTop += activeItemRect.bottom - listboxRect.bottom;
                    } else if (activeItemRect.top < listboxRect.top) {
                        listbox.scrollTop -= listboxRect.top - activeItemRect.top;
                    }
                }
            },
            
            goToSelected() {
                if (this.filteredItems.length > 0) {
                    this.goTo(this.filteredItems[this.selectedIndex].url);
                }
            },
            
            goTo(url) {
                if (url.startsWith('javascript:')) {
                    // Safe execution of javascript urls defined internally
                    const script = url.replace('javascript:', '');
                    try {
                        eval(script);
                    } catch(e) {}
                } else {
                    window.location.href = url;
                }
            },

            init() {
                this.$watch('search', () => {
                    this.selectedIndex = 0;
                });
            }
        }));
    });
</script>

<form id="logout-form-palette" method="POST" action="{{ route('logout') }}" style="display: none;">
    @csrf
</form>
