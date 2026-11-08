@props([
    'portalLabel' => 'Staff Portal',
    'mobileTitle' => 'CCT Staff Portal',
    'roleLabel' => '',
    'profileHref' => null,
])

<div x-data="{ sidebarOpen: false, logoutModalOpen: false }" class="flex h-screen overflow-hidden">
    
    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen" style="display: none;" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <!-- Sidebar -->
    <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
         class="fixed inset-y-0 left-0 z-50 lg:z-30 w-72 bg-white border-r border-border shadow-[4px_0_24px_rgba(0,0,0,0.02)] flex flex-col transition-transform duration-300 ease-in-out -translate-x-full lg:translate-x-0 lg:static lg:flex-shrink-0">
        
        <!-- Logo -->
        <div class="h-20 flex items-center px-6 border-b border-border/50 shrink-0 bg-white">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#fefbf6] rounded-full flex items-center justify-center shadow-sm border border-border">
                    <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Logo" width="24" height="24" loading="eager" decoding="async" class="h-6 w-auto mix-blend-multiply" />
                </div>
                <span class="font-heading font-bold text-lg text-primary leading-tight">CCT Wellness<br><span class="text-xs text-foreground/50 font-sans tracking-wide uppercase">{{ $portalLabel }}</span></span>
            </a>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1.5">
            <div class="px-3 pb-2 text-xs font-semibold text-foreground/40 uppercase tracking-wider">Main Navigation</div>
            {{ $navigation }}
        </nav>

        <!-- User Info & Logout -->
        <div class="border-t border-border/50 p-4 shrink-0 bg-muted/10">
            @if($profileHref)
                <a href="{{ $profileHref }}" class="flex items-center gap-3 rounded-xl p-1.5 -m-1.5 transition-colors {{ request()->routeIs('profile.*') ? 'bg-primary/10 text-primary' : 'hover:bg-muted/60' }}">
                    <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 shadow-sm border {{ request()->routeIs('profile.*') ? 'border-primary/30' : 'border-border' }} bg-white">
                        <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" width="40" height="40" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-foreground truncate leading-tight">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                        <p class="text-[0.7rem] text-foreground/60 truncate uppercase tracking-wider font-bold mt-0.5">
                            {{ $roleLabel }}
                        </p>
                    </div>
                </a>
            @else
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 shadow-sm border border-border bg-white">
                        <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" width="40" height="40" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-foreground truncate leading-tight">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                        <p class="text-[0.7rem] text-foreground/60 truncate uppercase tracking-wider font-bold mt-0.5">
                            {{ $roleLabel }}
                        </p>
                    </div>
                </div>
            @endif
            <!-- Logout Trigger -->
            <div class="w-full m-0">
                <button x-on:click.prevent="logoutModalOpen = true" title="Log Out"
                        class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 py-2 rounded-lg transition-colors border border-red-100/50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px; flex-shrink: 0;">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    Log Out
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <!-- Mobile Top Bar -->
        <header class="lg:hidden h-16 bg-white border-b border-border shadow-sm flex items-center justify-between px-4 shrink-0 relative z-30">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" aria-label="Open navigation menu" class="p-2 -ml-2 rounded-lg text-foreground/70 hover:bg-muted focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 24px; height: 24px; flex-shrink: 0;">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <span class="font-heading font-bold text-primary">{{ $mobileTitle }}</span>
            </div>
        </header>

        <!-- Page Header -->
        @isset($header)
            <header class="bg-white border-b border-border shrink-0 shadow-sm relative z-20">
                <div class="px-6 py-4 lg:py-6 lg:px-8 max-w-7xl mx-auto">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Main scrollable area -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8" x-data="{ pageLoaded: false }" x-init="setTimeout(() => pageLoaded = true, 10)">
            <div class="max-w-7xl mx-auto transition-all duration-500 ease-out"
                 :class="pageLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                {{ $slot }}
            </div>
        </main>
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
    </div>
</div>

