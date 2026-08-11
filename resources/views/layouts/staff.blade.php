<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Staff</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans text-foreground bg-[#f4f7f4] antialiased selection:bg-primary/20">
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
            
            <!-- Mobile sidebar backdrop -->
            <div x-show="sidebarOpen" style="display: none;" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <!-- Sidebar -->
            <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
                 class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-border shadow-[4px_0_24px_rgba(0,0,0,0.02)] flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:flex-shrink-0">
                
                <!-- Logo -->
                <div class="h-20 flex items-center px-6 border-b border-border/50 shrink-0 bg-white">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#fefbf6] rounded-full flex items-center justify-center shadow-sm border border-border">
                            <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Logo" class="h-6 w-auto mix-blend-multiply" />
                        </div>
                        <span class="font-heading font-bold text-lg text-primary leading-tight">CCT Wellness<br><span class="text-xs text-foreground/50 font-sans tracking-wide uppercase">Staff Portal</span></span>
                    </a>
                </div>

                <!-- Nav Links -->
                <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1.5">
                    @php
                        $navItems = [
                            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                            ['route' => 'announcements.index', 'label' => 'Announcements', 'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46'],
                        ];
                        
                        if(Auth::user()->role === 'system_admin') {
                            $navItems[] = ['route' => 'question-bank.index', 'label' => 'Question Bank', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'];
                            $navItems[] = ['route' => 'schedules.index', 'label' => 'Schedules', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'];
                            $navItems[] = ['route' => 'flag-settings.index', 'label' => 'Flag Settings', 'icon' => 'M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5'];
                            $navItems[] = ['route' => 'institution.index', 'label' => 'Institution Data', 'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z'];
                        }
                        
                        $navItems[] = ['route' => 'year-levels.index', 'label' => 'Year Levels', 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'];
                        $navItems[] = ['route' => 'staff.inventory.index', 'label' => 'Submissions', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'];
                        $navItems[] = ['route' => 'analytics.index', 'label' => 'Analytics', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'];
                    @endphp

                    <div class="px-3 pb-2 text-xs font-semibold text-foreground/40 uppercase tracking-wider">Main Navigation</div>

                    @foreach($navItems as $item)
                        @php
                            $routePrefix = explode('.', $item['route'])[0];
                            if($item['route'] == 'dashboard') $routePrefix = 'dashboard';
                            if($item['route'] == 'staff.inventory.index') $routePrefix = 'staff.inventory';
                            
                            $isActive = request()->routeIs($routePrefix . '.*') || request()->routeIs($item['route']);
                        @endphp
                        <a href="{{ route($item['route']) }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ $isActive ? 'bg-primary/10 text-primary border border-primary/20 shadow-sm font-semibold' : 'text-foreground/70 hover:bg-muted hover:text-foreground font-medium border border-transparent' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0;" class="{{ $isActive ? 'text-primary' : 'text-foreground/50' }}">
                              <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <!-- User Info & Logout -->
                <div class="border-t border-border/50 p-4 shrink-0 bg-muted/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 shadow-sm border border-border bg-white">
                            <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-foreground truncate leading-tight">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                            <p class="text-[0.7rem] text-foreground/60 truncate uppercase tracking-wider font-bold mt-0.5">{{ str_replace('_', ' ', Auth::user()->role) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 text-sm font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 py-2 rounded-lg transition-colors border border-red-100/50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px; flex-shrink: 0;">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
                <!-- Mobile Top Bar -->
                <header class="lg:hidden h-16 bg-white border-b border-border shadow-sm flex items-center justify-between px-4 shrink-0 relative z-30">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = true" class="p-2 -ml-2 rounded-lg text-foreground/70 hover:bg-muted focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 24px; height: 24px; flex-shrink: 0;">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                        <span class="font-heading font-bold text-primary">CCT Staff Portal</span>
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
                <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 relative z-10">
                    <div class="max-w-7xl mx-auto">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
