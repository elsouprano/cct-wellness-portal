@section('title', 'Dashboard')
@if(auth()->user()->isCounselor() || auth()->user()->isAdmin())
    <x-staff-layout>
        {{-- STAFF DASHBOARD --}}
        
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Greeting & Overview -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-heading text-3xl font-bold text-primary">
                        Welcome, {{ auth()->user()->first_name }}
                        @if(auth()->user()->isCounselor())
                            @if(auth()->user()->assignedDepartment)
                                <span class="text-lg font-medium text-foreground/70 ml-2">({{ auth()->user()->assignedDepartment->name }})</span>
                            @else
                                <span class="text-sm font-medium px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 ml-2">Unassigned</span>
                            @endif
                        @endif
                    </h1>
                    <p class="text-foreground/70 mt-1">Here is your overview for the {{ $academicYear }} academic year.</p>
                </div>
            </div>

            <!-- Unassigned Counselor Banner -->
            @if(auth()->user()->isCounselor() && !auth()->user()->department_id)
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl shadow-sm flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-amber-800">No department assigned yet. Please contact the system administrator.</h4>
                        <p class="text-xs text-amber-700 mt-0.5">You will not see student data or be able to create assessment schedules until a department is assigned.</p>
                    </div>
                </div>
            @endif

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

            <div class="w-full">
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-10">
            {{-- STUDENT DASHBOARD --}}
            
            <!-- Row 1: Greeting & Student Context Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-border/60">
                <div class="flex items-center gap-3.5 sm:gap-4">
                    <div class="w-14 h-14 rounded-full overflow-hidden border border-border shadow-xs shrink-0 bg-white">
                        <img src="{{ auth()->user()->avatar_url }}" alt="Profile Picture" width="56" height="56" loading="lazy" decoding="async" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h1 class="font-heading text-2xl sm:text-3xl font-bold text-foreground">Welcome back, {{ auth()->user()->first_name }}</h1>
                        <p class="text-base text-foreground/70 mt-0.5">
                            <span>{{ auth()->user()->structuredProgram ? auth()->user()->structuredProgram->code : (auth()->user()->program ?? 'Student') }}{{ auth()->user()->section ? ' ' . auth()->user()->section : '' }}</span>
                            <span class="sr-only">{{ auth()->user()->year_level }}</span>
                        </p>
                    </div>
                </div>
                <div class="text-sm sm:text-base font-semibold text-accent uppercase tracking-wider sm:text-right">
                    {{ now()->format('l, F j, Y') }}
                </div>
            </div>

            <!-- Assessment Status Card -->
            <div class="w-full">
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
                            <p class="text-foreground/80 text-lg leading-relaxed">
                                You've successfully completed your individual inventory for the {{ $academicYearLabel ?? 'current' }} academic year. Thank you for taking the time to share with us!
                            </p>
                        </div>
                    </div>
                @elseif(isset($activeSchedule) && $activeSchedule)
                    <!-- State 2: Active / Open -->
                    <div class="relative bg-white border-2 border-primary/20 rounded-[2rem] p-8 sm:p-12 shadow-sm flex flex-col md:flex-row items-center justify-between gap-8 overflow-hidden transition-all hover:shadow-md">
                        <div class="space-y-4 max-w-2xl text-center md:text-left">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-accent/10 text-accent uppercase tracking-wider">
                                <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                                Assessment Period Active
                            </div>
                            <h2 class="font-heading text-3xl font-bold text-foreground leading-tight">
                                Your {{ $activeSchedule->year_level }} Individual Inventory for A.Y. {{ $academicYearLabel }} is now open
                            </h2>
                            <p class="text-foreground/70 text-lg leading-relaxed">
                                Please take a few moments to complete your required psychological assessment. Your responses help us support your wellness and academic journey.
                            </p>
                            @if($activeSchedule->close_date)
                                @php
                                    $daysLeft = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSchedule->close_date)->endOfDay(), false);
                                @endphp
                                <div class="text-sm font-semibold {{ $daysLeft <= 3 ? 'text-destructive' : 'text-accent' }} flex items-center justify-center md:justify-start gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Closes in {{ floor($daysLeft) }} {{ Str::plural('day', floor($daysLeft)) }} ({{ \Carbon\Carbon::parse($activeSchedule->close_date)->format('M d, Y') }})
                                </div>
                            @endif
                        </div>
                        <div class="shrink-0 w-full md:w-auto text-center">
                            <a href="{{ route('inventory.index') }}" class="btn-primary text-lg font-bold px-8 py-4 inline-flex items-center justify-center gap-3 w-full md:w-auto shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                                <span>Start Assessment</span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @elseif(isset($upcomingSchedule) && $upcomingSchedule)
                    <!-- State 3: Upcoming Assessment -->
                    <div class="bg-white border border-border rounded-[2rem] p-8 sm:p-12 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6 sm:gap-8 text-center sm:text-left transition-all hover:shadow-md">
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-foreground/40">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-heading text-3xl font-bold text-foreground mb-3">Upcoming Assessment</h2>
                            <p class="text-foreground/70 text-lg leading-relaxed">
                                Your {{ $upcomingSchedule->year_level }} Individual Inventory will open on <span class="font-bold text-foreground">{{ \Carbon\Carbon::parse($upcomingSchedule->open_date)->format('F j, Y') }}</span>. Check back soon!
                            </p>
                        </div>
                    </div>
                @else
                    <!-- State 4: No Active Assessment -->
                    <div class="bg-white border border-border rounded-[2rem] p-8 sm:p-12 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6 sm:gap-8 text-center sm:text-left transition-all hover:shadow-md">
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-foreground/40">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-heading text-3xl font-bold text-foreground mb-3">No Assessment Scheduled</h2>
                            <p class="text-foreground/70 text-lg leading-relaxed">
                                There is currently no active assessment period for your year level. We'll post an announcement when the schedule is available.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Announcements Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-heading text-2xl font-bold text-foreground">Campus Announcements</h3>
                        <p class="text-foreground/60 text-sm mt-1">Updates and notices from the Guidance and Counseling Office</p>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="hidden sm:inline-flex items-center gap-1 text-primary font-bold hover:underline">
                        View All Announcements &rarr;
                    </a>
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
                                        {{ $announcement->published_at ? $announcement->published_at->format('M d, Y') : $announcement->created_at->format('M d, Y') }}
                                        @if($announcement->published_at && $announcement->updated_at > $announcement->published_at->copy()->addMinutes(1))
                                            <span class="text-foreground/40 italic lowercase tracking-normal">(Edited {{ $announcement->updated_at->format('M d') }})</span>
                                        @endif
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
    </x-app-layout>
@endif
