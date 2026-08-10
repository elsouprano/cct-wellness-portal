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
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                {{-- STUDENT DASHBOARD (unchanged) --}}
                <div class="card max-w-md mx-auto text-center border border-border">
                    <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-primary">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                        </svg>
                    </div>
                    
                    <h1 class="font-heading text-3xl font-bold text-primary mb-2">Welcome, {{ auth()->user()->first_name }}</h1>
                    <p class="text-foreground/70 mb-8">You have successfully logged into the Wellness Portal.</p>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-primary w-full mt-4">Logout</button>
                    </form>
                </div>

                <!-- Inventory Widget (Student Only) -->
                @php
                    $academicYear = '2025-2026';
                    $submission = \App\Models\InventorySubmission::where('user_id', auth()->user()->id)
                        ->where('academic_year', $academicYear)
                        ->first();
                    
                    $isSubmitted = $submission && $submission->submitted_at;
                @endphp
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-heading font-semibold text-foreground">3rd Year Individual Inventory</h3>
                    </div>
                    <p class="text-foreground/70 mb-6">Complete your required psychological assessment for the current academic year.</p>
                    
                    @if($isSubmitted)
                        <a href="{{ route('inventory.index') }}" class="btn-secondary inline-block">View my submission status</a>
                    @else
                        <a href="{{ route('inventory.index') }}" class="btn-primary inline-block bg-accent border-accent hover:bg-accent/90">Take the 3rd Year Individual Inventory</a>
                    @endif
                </div>

                <!-- Announcements Widget -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-heading font-semibold text-foreground">Campus Announcements</h3>
                        <a href="{{ route('announcements.index') }}" class="text-primary font-medium hover:underline text-sm">View all &rarr;</a>
                    </div>
                    <p class="text-foreground/70 mb-6">
                        Stay up to date with the latest news and updates from the guidance office.
                    </p>
                    <a href="{{ route('announcements.index') }}" class="btn-primary inline-block">
                        Go to Announcements Feed
                    </a>
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
