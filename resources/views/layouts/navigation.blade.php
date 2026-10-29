<nav x-data="{ 
    open: false, 
    logoutModalOpen: false,
    init() {
        this.$watch('open', value => {
            document.body.classList.toggle('overflow-hidden', value);
        });
    }
}" 
@keydown.escape.window="open = false"
@resize.window="if (window.innerWidth >= 640) open = false"
class="bg-white border-b border-border shadow-sm relative z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-[var(--nav-h)]">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" width="48" height="48" loading="eager" decoding="async" class="block h-10 xl:h-11 2xl:h-12 w-auto mix-blend-multiply transition-all duration-150" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 xl:space-x-10 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">
                        {{ __('Announcements') }}
                    </x-nav-link>
                    @if(Auth::user()->role === 'student')
                    <x-nav-link :href="route('inventory.history')" :active="request()->routeIs('inventory.history*')">
                        {{ __('Assessment History') }}
                    </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48 xl:w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm xl:text-base 2xl:text-[17px] leading-4 font-medium rounded-md text-foreground/70 bg-white hover:text-foreground focus:outline-none transition ease-in-out duration-200">
                            <div class="w-8 h-8 xl:w-9 xl:h-9 2xl:w-10 2xl:h-10 rounded-full overflow-hidden mr-2 xl:mr-2.5 border border-border shrink-0 transition-all duration-150">
                                <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" width="40" height="40" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                            </div>
                            <div>{{ Auth::user()->first_name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 xl:h-5 xl:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button type="button" x-on:click.prevent="logoutModalOpen = true" class="block w-full px-4 py-2 text-start text-sm xl:text-base leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            {{ __('Log Out') }}
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button type="button" 
                        @click="open = ! open" 
                        aria-label="Toggle navigation menu"
                        :aria-expanded="open.toString()"
                        aria-controls="mobile-menu"
                        class="w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-xl text-foreground/70 hover:text-foreground transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 cursor-pointer">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="6" x2="20" y2="6" class="origin-center transition-transform duration-200" :class="open ? 'rotate-45 translate-y-[6px]' : ''" />
                        <line x1="4" y1="12" x2="20" y2="12" class="transition-opacity duration-150" :class="open ? 'opacity-0' : 'opacity-100'" />
                        <line x1="4" y1="18" x2="20" y2="18" class="origin-center transition-transform duration-200" :class="open ? '-rotate-45 -translate-y-[6px]' : ''" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Dimmed Backdrop (closes on outside tap) -->
    <div x-show="open" 
         x-cloak
         @click="open = false"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 top-[var(--nav-h)] z-30 bg-foreground/40 sm:hidden">
    </div>

    <!-- Responsive Navigation Overlay Menu Panel -->
    <div id="mobile-menu"
         x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-[var(--nav-h)] inset-x-0 z-40 bg-white border-b border-border shadow-lg max-h-[calc(100dvh-var(--nav-h))] overflow-y-auto sm:hidden">

        <!-- 1. User Card (Links to Profile) -->
        <a href="{{ route('profile.edit') }}" @click="open = false" class="flex items-center gap-3 p-4 transition-colors border-b border-border {{ request()->routeIs('profile.*') ? 'bg-primary/10 border-l-4 border-l-primary' : 'bg-muted/40 hover:bg-muted/70' }}">
            <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 border border-border shadow-xs bg-white">
                <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" width="40" height="40" loading="lazy" decoding="async" class="w-full h-full object-cover" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="font-semibold text-base {{ request()->routeIs('profile.*') ? 'text-primary' : 'text-foreground' }} truncate leading-tight">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                <div class="text-xs text-foreground/60 truncate mt-0.5">{{ Auth::user()->email }}</div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 {{ request()->routeIs('profile.*') ? 'text-primary' : 'text-foreground/40' }} ms-auto shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>

        <!-- 2. Main Nav Links -->
        <div class="p-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="open = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-foreground/50' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span>{{ __('Dashboard') }}</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')" @click="open = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 {{ request()->routeIs('announcements.*') ? 'text-primary' : 'text-foreground/50' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                </svg>
                <span>{{ __('Announcements') }}</span>
            </x-responsive-nav-link>

            @if(Auth::user()->role === 'student')
            <x-responsive-nav-link :href="route('inventory.history')" :active="request()->routeIs('inventory.history*')" @click="open = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 {{ request()->routeIs('inventory.history*') ? 'text-primary' : 'text-foreground/50' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ __('Assessment History') }}</span>
            </x-responsive-nav-link>
            @endif
        </div>

        <!-- 3. Footer Options (Destructive Log Out) -->
        <div class="p-3 border-t border-border">
            <!-- Destructive Log Out -->
            <button type="button" 
                    x-on:click.prevent="open = false; logoutModalOpen = true" 
                    class="h-12 min-h-[48px] w-full px-3.5 rounded-xl flex items-center gap-3 text-destructive font-medium transition-colors [@media(hover:hover)]:hover:bg-destructive/5 active:bg-destructive/10 text-start cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-destructive/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-destructive shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                </svg>
                <span>{{ __('Log Out') }}</span>
            </button>
        </div>
    </div>

    <!-- Logout Modal Built-in -->
    <div x-show="logoutModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0">
        <div x-show="logoutModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 transform transition-all" 
             @click="logoutModalOpen = false">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div x-show="logoutModalOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-sm mx-auto relative z-50">
            <form method="POST" action="{{ route('logout') }}" class="p-6" x-on:submit="logoutModalOpen = false">
                @csrf
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Confirm Logout') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Are you sure you want to log out of your account?') }}
                </p>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button @click="logoutModalOpen = false" type="button">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-danger-button class="ms-3" type="submit">
                        {{ __('Log Out') }}
                    </x-danger-button>
                </div>
            </form>
        </div>
    </div>
</nav>
