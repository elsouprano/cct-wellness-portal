@if(auth()->user()->isCounselor() || auth()->user()->isAdmin())
    <x-staff-layout>
        {{-- STAFF DASHBOARD --}}
        
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Greeting & Overview -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-heading text-3xl font-bold text-primary">Welcome, {{ auth()->user()->first_name }}</h1>
                    <p class="text-foreground/70 mt-1">Here is your overview for the {{ $academicYear }} academic year.</p>
                </div>
            </div>

            <!-- Active Schedules Banner -->
            @if($activeSchedules && $activeSchedules->count() > 0)
                <div class="bg-accent/10 border border-accent/20 rounded-xl p-4 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-accent mt-0.5 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-accent">Active Assessment Schedules</h4>
                        <p class="text-sm text-foreground/80 mt-1">
                            @foreach($activeSchedules as $sched)
                                {{ $sched->year_level }} Year ({{ $sched->program ?? 'All Programs' }}) is currently open. 
                            @endforeach
                        </p>
                    </div>
                </div>
            @endif

            <!-- Key Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Submissions -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-sm flex flex-col justify-between">
                    <div>
                        <p class="text-sm font-medium text-foreground/60 uppercase tracking-wider mb-1">Total Submissions</p>
                        <h3 class="font-heading text-4xl font-bold text-primary">{{ $stats['totalSubmissions'] ?? 0 }}</h3>
                    </div>
                </div>

                <!-- Total Flagged -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-sm flex flex-col justify-between">
                    <div>
                        <p class="text-sm font-medium text-foreground/60 uppercase tracking-wider mb-1">Total Flagged</p>
                        <h3 class="font-heading text-4xl font-bold text-orange-600 flex items-baseline gap-2">
                            {{ $stats['totalUnreviewedFlags'] ?? 0 }} 
                            <span class="text-lg text-foreground/50 font-normal">unreviewed</span>
                        </h3>
                    </div>
                </div>

                <!-- DASS21 Severe/Extremely Severe -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-sm flex flex-col justify-between">
                    <div>
                        <p class="text-sm font-medium text-foreground/60 uppercase tracking-wider mb-1">Severe / Extreme (DASS21)</p>
                        @php
                            $severeCount = ($stats['dass21Stats']['Severe'] ?? 0) + ($stats['dass21Stats']['Extremely Severe'] ?? 0);
                        @endphp
                        <h3 class="font-heading text-4xl font-bold text-red-600">{{ $severeCount }}</h3>
                    </div>
                </div>

                <!-- DASS21 Moderate -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-sm flex flex-col justify-between">
                    <div>
                        <p class="text-sm font-medium text-foreground/60 uppercase tracking-wider mb-1">Moderate (DASS21)</p>
                        <h3 class="font-heading text-4xl font-bold text-amber-500">{{ $stats['dass21Stats']['Moderate'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Quick Actions -->
                    <div class="lg:col-span-2 space-y-4">
                        <h3 class="font-heading text-xl font-bold text-foreground">Quick Actions</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <a href="{{ route('staff.inventory.index') }}" class="group bg-white p-5 rounded-2xl border border-border shadow-sm hover:shadow-md transition-all hover:border-primary">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors">Submissions Review</h4>
                                <p class="text-sm text-foreground/60 mt-1">Review student inventory results and flags.</p>
                            </a>

                            <a href="{{ route('schedules.index') }}" class="group bg-white p-5 rounded-2xl border border-border shadow-sm hover:shadow-md transition-all hover:border-primary">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors">Assessment Schedules</h4>
                                <p class="text-sm text-foreground/60 mt-1">Manage when students can take inventories.</p>
                            </a>

                            <a href="{{ route('announcements.index') }}" class="group bg-white p-5 rounded-2xl border border-border shadow-sm hover:shadow-md transition-all hover:border-primary">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors">Announcements</h4>
                                <p class="text-sm text-foreground/60 mt-1">Post updates to the student portal.</p>
                            </a>

                            <a href="{{ route('question-bank.index') }}" class="group bg-white p-5 rounded-2xl border border-border shadow-sm hover:shadow-md transition-all hover:border-primary">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors">Question Bank</h4>
                                <p class="text-sm text-foreground/60 mt-1">Configure inventory questions and scoring.</p>
                            </a>
                            
                            <a href="{{ route('year-levels.index') }}" class="group bg-white p-5 rounded-2xl border border-border shadow-sm hover:shadow-md transition-all hover:border-primary">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors">Year Levels</h4>
                                <p class="text-sm text-foreground/60 mt-1">Manage and promote student batches.</p>
                            </a>
                            
                            <a href="{{ route('flag-settings.index') }}" class="group bg-white p-5 rounded-2xl border border-border shadow-sm hover:shadow-md transition-all hover:border-primary">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-foreground group-hover:text-primary transition-colors">Flag Settings</h4>
                                <p class="text-sm text-foreground/60 mt-1">Configure automated flagging rules.</p>
                            </a>

                        </div>
                    </div>

                    <!-- Needs Attention Sidebar -->
                    <div class="space-y-4">
                        <h3 class="font-heading text-xl font-bold text-foreground">Needs Attention</h3>
                        
                        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
                            <div class="p-4 border-b border-border bg-muted/30">
                                <h4 class="font-semibold text-sm text-foreground">Recent Unreviewed Flags</h4>
                            </div>
                            
                            @if(isset($recentFlags) && $recentFlags->count() > 0)
                                <div class="divide-y divide-border">
                                    @foreach($recentFlags as $submission)
                                        <a href="{{ route('staff.inventory.show', $submission->id) }}" class="block p-4 hover:bg-muted/30 transition-colors group">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-foreground group-hover:text-primary transition-colors">{{ $submission->user->first_name }} {{ $submission->user->last_name }}</p>
                                                    <p class="text-xs text-foreground/60 mt-1">{{ $submission->user->program }} ({{ $submission->user->year_level }} Year)</p>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Flagged
                                                </span>
                                            </div>
                                            <p class="text-xs text-foreground/50 mt-2 flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px; flex-shrink: 0;">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                {{ $submission->submitted_at->diffForHumans() }}
                                            </p>
                                        </a>
                                    @endforeach
                                </div>
                                <div class="p-3 border-t border-border text-center">
                                    <a href="{{ route('staff.inventory.index') }}" class="text-sm font-medium text-primary hover:underline">View all submissions &rarr;</a>
                                </div>
                            @else
                                <div class="p-8 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-foreground/30 mx-auto mb-3">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <p class="text-sm text-foreground/60">No unreviewed flags. Great job!</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

    </x-staff-layout>
@else
    <x-app-layout>
        <div class="relative min-h-screen py-12 overflow-hidden">
            <!-- Decorative organic background shapes -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none -z-10">
                <svg class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] text-primary opacity-5" fill="currentColor" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                  <path d="M44.7,-76.4C58.8,-69.2,71.8,-59.1,81.6,-46.3C91.4,-33.5,98,-18,97.4,-2.9C96.8,12.2,89,26.8,78.7,39.6C68.4,52.4,55.6,63.4,41.4,71.4C27.2,79.4,11.6,84.4,-3.6,89.5C-18.8,94.6,-33.6,99.8,-45.5,94.3C-57.4,88.8,-66.4,72.6,-73.4,57.2C-80.4,41.8,-85.4,27.2,-87.3,12C-89.2,-3.2,-88,-19,-81.1,-32C-74.2,-45,-61.6,-55.2,-48,-62.7C-34.4,-70.2,-19.8,-75,-4.4,-67.4C11,-59.8,22,-39.8,30.6,-47.1C39.2,-54.4,45.4,-74.6,44.7,-76.4Z" transform="translate(100 100)" />
                </svg>
                <svg class="absolute top-[40%] -right-[15%] w-[60%] h-[60%] text-accent opacity-5" fill="currentColor" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                  <path d="M38.1,-63.9C49.9,-54.4,60.5,-44,69.5,-31.6C78.5,-19.2,85.9,-4.8,85,9C84.1,22.8,74.9,36,63.6,46.5C52.3,57,38.9,64.8,24.8,69.7C10.7,74.6,-4.1,76.6,-18.2,74.1C-32.3,71.6,-45.7,64.6,-57.1,54.7C-68.5,44.8,-77.9,32,-82.1,17.7C-86.3,3.4,-85.3,-12.4,-79.1,-25.9C-72.9,-39.4,-61.5,-50.6,-48.6,-59.6C-35.7,-68.6,-21.3,-75.4,-7.1,-65.7C7.1,-56,26.3,-73.4,38.1,-63.9Z" transform="translate(100 100)" />
                </svg>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-12">
                {{-- STUDENT DASHBOARD (Redesigned) --}}
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-border/50">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-4 border-white shadow-lg shrink-0">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Profile Picture" class="w-full h-full object-cover">
                        </div>
                        <div class="space-y-1">
                            <div class="text-sm font-semibold text-accent uppercase tracking-wider">{{ now()->format('l, F j, Y') }}</div>
                            <h1 class="font-heading text-3xl sm:text-4xl font-bold text-foreground">Welcome back, {{ auth()->user()->first_name }}</h1>
                            <p class="text-foreground/70 text-lg">We hope you're having a great day on campus.</p>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn-secondary whitespace-nowrap self-start md:self-auto text-sm px-5 py-2.5 bg-white">
                        Profile & Settings &rarr;
                    </a>
                </div>

                <!-- Assessment Status Card -->
                <div class="max-w-4xl">
                    @if(isset($hasSubmitted) && $hasSubmitted)
                        <!-- State 1: Submitted -->
                        <div class="bg-primary/5 border border-primary/20 rounded-[2rem] p-8 sm:p-12 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6 sm:gap-8 text-center sm:text-left transition-all hover:shadow-md">
                            <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-primary">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-heading text-3xl font-bold text-primary mb-3">Inventory Complete</h2>
                                <p class="text-foreground/80 text-lg leading-relaxed">You've successfully completed your individual inventory for the {{ $academicYearLabel ?? 'current' }} academic year. Thank you for taking the time to share with us!</p>
                            </div>
                        </div>
                    @elseif(isset($activeSchedule) && $activeSchedule)
                        <!-- State 2: Open & Unsubmitted -->
                        <div class="bg-white border-2 border-accent rounded-[2rem] p-8 sm:p-12 shadow-md relative overflow-hidden transition-all hover:shadow-lg group">
                            <!-- Subtle decorative inner shape -->
                            <div class="absolute -top-24 -right-24 w-64 h-64 bg-accent/5 rounded-full blur-3xl pointer-events-none group-hover:bg-accent/10 transition-colors duration-500"></div>
                            
                            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                                <div class="flex-1">
                                    <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-accent/10 text-accent font-bold text-sm rounded-full uppercase tracking-wider mb-5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Action Required
                                    </div>
                                    <h2 class="font-heading text-3xl sm:text-4xl font-bold text-foreground mb-4 leading-tight">Your {{ $activeSchedule->year_level }} Individual Inventory is open</h2>
                                    <p class="text-foreground/70 text-lg leading-relaxed">
                                        Please take a few moments to complete your required psychological assessment. 
                                        @if($activeSchedule->close_date)
                                            @php
                                                $daysLeft = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSchedule->close_date)->endOfDay(), false);
                                            @endphp
                                            @if($daysLeft >= 0)
                                                <span class="font-bold text-accent block mt-2 sm:inline sm:mt-0">Only {{ floor($daysLeft) }} {{ Str::plural('day', floor($daysLeft)) }} left.</span>
                                            @endif
                                        @endif
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <a href="{{ route('inventory.index') }}" class="inline-flex items-center justify-center gap-3 bg-accent text-white hover:bg-accent/90 border-accent px-10 py-4 rounded-xl text-lg font-bold shadow-lg hover:shadow-xl transition-all hover:-translate-y-1">
                                        Start Assessment
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- State 3: No Active Assessment -->
                        <div class="bg-white border border-border rounded-[2rem] p-8 sm:p-10 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left text-muted-foreground transition-all hover:shadow-md">
                            <div class="w-14 h-14 bg-muted rounded-full flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-foreground/40">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-heading text-2xl font-bold text-foreground mb-2">No Assessment Scheduled</h2>
                                <p class="text-foreground/60 text-lg">There is currently no open inventory assessment for your year level or program. Enjoy your day!</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Recent Announcements -->
                <div class="pt-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="font-heading text-3xl font-bold text-foreground">Campus Announcements</h3>
                            <p class="text-foreground/60 mt-1">Stay updated with the latest news and guidance</p>
                        </div>
                        <a href="{{ route('announcements.index') }}" class="btn-secondary text-sm px-5 py-2 bg-white hover:bg-muted/50 hidden sm:inline-flex">View All &rarr;</a>
                    </div>

                    @if(isset($recentAnnouncements) && $recentAnnouncements->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            @foreach($recentAnnouncements as $announcement)
                                <a href="{{ route('announcements.show', $announcement) }}" class="group bg-white rounded-3xl border border-border shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full hover:-translate-y-1">
                                    <div class="p-8 flex flex-col h-full">
                                        <div class="inline-flex items-center gap-2 text-xs font-bold text-primary/80 mb-4 uppercase tracking-wider">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            {{ $announcement->created_at->format('M d, Y') }}
                                        </div>
                                        <h4 class="font-heading text-xl font-bold text-foreground mb-3 group-hover:text-primary transition-colors line-clamp-2 leading-snug">
                                            {{ $announcement->title }}
                                        </h4>
                                        <div class="text-foreground/70 text-base line-clamp-3 mb-6 leading-relaxed">
                                            {{ Str::limit(strip_tags($announcement->content ?? $announcement->body), 120) }}
                                        </div>
                                        <div class="mt-auto inline-flex items-center gap-1 text-primary text-sm font-bold group-hover:underline">
                                            Read more 
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 transition-transform group-hover:translate-x-1">
                                              <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-6 sm:hidden text-center">
                            <a href="{{ route('announcements.index') }}" class="btn-secondary text-sm px-5 py-2 bg-white w-full">View All Announcements &rarr;</a>
                        </div>
                    @else
                        <div class="bg-gradient-to-br from-white to-muted/30 border border-border rounded-3xl p-12 text-center shadow-sm">
                            <div class="w-20 h-20 bg-primary/5 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-primary/40">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                                </svg>
                            </div>
                            <h3 class="font-heading text-2xl font-bold text-foreground mb-2">You're all caught up!</h3>
                            <p class="text-foreground/60 text-lg max-w-md mx-auto">There are no new announcements from the Guidance Office right now. Enjoy your day.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
