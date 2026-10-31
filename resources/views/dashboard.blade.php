@section('title', 'Dashboard')
@if(auth()->user()->isCounselor() || auth()->user()->isAdmin())
    <x-staff-layout>
        {{-- STAFF DASHBOARD --}}
        
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Welcome Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold text-primary">
                        Welcome back, {{ auth()->user()->first_name }}
                    </h1>
                    @if(auth()->user()->isCounselor())
                        @if($assignedDepartments->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-xs font-semibold text-foreground/50 uppercase tracking-wider">Assigned Department{{ $assignedDepartments->count() > 1 ? 's' : '' }}:</span>
                                @foreach($assignedDepartments as $dept)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                        {{ $dept->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Unassigned (No Department Assigned)
                                </span>
                            </div>
                        @endif
                    @else
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                System Administrator &bull; All Departments
                            </span>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="px-3.5 py-2 rounded-xl bg-muted/30 border border-border text-xs text-foreground/70 font-medium">
                        Academic Year: <span class="font-bold text-primary">{{ $academicYear ?? 'Not Configured' }}</span>
                    </div>
                </div>
            </div>

            <!-- Unassigned Counselor Warning Banner -->
            @if(auth()->user()->isCounselor() && $assignedDepartments->isEmpty())
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-xl shadow-xs flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="font-semibold text-amber-800">No department assigned yet. Please contact the system administrator.</h4>
                        <p class="text-xs text-amber-700 mt-0.5">You will not see student data or be able to manage inventory schedules until a department is assigned.</p>
                    </div>
                </div>
            @endif

            <!-- Summary Cards (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Card 1: Assigned Students -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-foreground/60">Assigned Students</span>
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-foreground">{{ number_format($assignedStudentsCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">Students under your assigned department{{ $assignedDepartments->count() > 1 ? 's' : '' }}</p>
                    </div>
                </div>

                <!-- Card 2: Submitted -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Submitted</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-emerald-600">{{ number_format($submittedCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">Inventories submitted ({{ $completionPercentage }}%)</p>
                    </div>
                </div>

                <!-- Card 3: Pending -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Pending</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-amber-600">{{ number_format($pendingCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">Students awaiting inventory submission</p>
                    </div>
                </div>

                <!-- Card 4: Flagged Responses -->
                <div class="bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Flagged Responses</span>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-rose-600">{{ number_format($flaggedResponsesCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">{{ $unreviewedFlagsCount }} unreviewed flag{{ $unreviewedFlagsCount === 1 ? '' : 's' }}</p>
                    </div>
                </div>
            </div>

            <!-- Assessment Progress Section -->
            <div class="bg-white rounded-2xl p-6 border border-border shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Assessment Completion Progress</h3>
                        <p class="text-xs text-foreground/60">Inventory submission progress for Academic Year {{ $academicYear ?? 'Current' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-heading text-2xl font-bold text-primary">{{ $completionPercentage }}%</span>
                        <span class="text-xs text-foreground/50 block">Completed</span>
                    </div>
                </div>
                <div class="w-full bg-muted rounded-full h-3 overflow-hidden">
                    <div class="bg-primary h-3 rounded-full transition-all duration-500" style="width: {{ min(100, $completionPercentage) }}%"></div>
                </div>
                <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-border/60 text-center">
                    <div>
                        <p class="text-xs text-foreground/50 uppercase font-semibold">Total Assigned</p>
                        <p class="font-bold text-foreground text-sm sm:text-base mt-0.5">{{ number_format($assignedStudentsCount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-600 uppercase font-semibold">Submitted</p>
                        <p class="font-bold text-emerald-600 text-sm sm:text-base mt-0.5">{{ number_format($submittedCount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-amber-600 uppercase font-semibold">Pending</p>
                        <p class="font-bold text-amber-600 text-sm sm:text-base mt-0.5">{{ number_format($pendingCount) }}</p>
                    </div>
                </div>
            </div>

            <!-- Needs Attention & Active Schedules Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Needs Attention Card -->
                <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-border bg-rose-50/30 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-rose-500 {{ $unreviewedFlagsCount > 0 ? 'animate-pulse' : '' }}"></div>
                            <h3 class="font-heading text-base font-bold text-foreground">Needs Attention</h3>
                        </div>
                        <span class="text-xs font-semibold text-rose-700 bg-rose-100/70 px-2.5 py-0.5 rounded-full">
                            {{ $unreviewedFlagsCount }} Flag{{ $unreviewedFlagsCount === 1 ? '' : 's' }} For Review
                        </span>
                    </div>
                    <div class="p-4 space-y-3 flex-1 flex flex-col justify-center">
                        <!-- Unreviewed Flags -->
                        <a href="{{ route('staff.flags.index', ['status' => 'unreviewed']) }}" class="flex items-center justify-between p-4 rounded-xl border border-border hover:border-rose-300 hover:bg-rose-50/20 transition-all group">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors">
                                        Recent Unreviewed Flags
                                    </div>
                                    <p class="text-xs text-foreground/60 mt-0.5">Submissions flagged by response quality checks requiring verification.</p>
                                </div>
                            </div>
                            <div class="text-right pl-3 shrink-0">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $unreviewedFlagsCount > 0 ? 'bg-rose-100 text-rose-800' : 'bg-muted text-foreground/50' }}">
                                    {{ $unreviewedFlagsCount }}
                                </span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Active Assessment Schedules -->
                <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-border bg-primary/5 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="font-heading text-base font-bold text-foreground">Active Schedules</h3>
                        </div>
                        <span class="text-xs font-semibold text-primary bg-primary/10 px-2.5 py-0.5 rounded-full">
                            A.Y. {{ $academicYear ?? 'Current' }}
                        </span>
                    </div>
                    <div class="p-4 flex-1">
                        @if($activeSchedules && $activeSchedules->count() > 0)
                            <div class="space-y-2.5">
                                @foreach($activeSchedules as $sched)
                                    <div class="p-3.5 rounded-xl border border-border bg-muted/10 flex items-center justify-between">
                                        <div>
                                            <div class="font-semibold text-sm text-foreground">
                                                {{ $sched->year_level }} Year Inventory
                                            </div>
                                            <p class="text-xs text-foreground/60 mt-0.5">
                                                Target: {{ $sched->program ?? 'All Programs' }}
                                                @if($sched->close_date)
                                                    &bull; Closes: {{ \Carbon\Carbon::parse($sched->close_date)->format('M d, Y') }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 shrink-0">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Open
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-foreground/30 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium text-foreground/70">No active schedules right now</p>
                                <p class="text-xs text-foreground/50 mt-0.5">Assessment schedules will appear here when open.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Submissions Table -->
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-5 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Recent Submissions</h3>
                        <p class="text-xs text-foreground/60 mt-0.5">Latest student inventories submitted under your department</p>
                    </div>
                    <a href="{{ route('staff.inventory.index') }}" class="text-xs font-semibold text-primary hover:text-primary-dark hover:underline flex items-center gap-1">
                        View All Submissions &rarr;
                    </a>
                </div>

                @if($recentSubmissions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-left">
                            <thead>
                                <tr class="text-foreground/50 text-xs uppercase tracking-wider bg-muted/20">
                                    <th class="px-5 py-3 font-semibold">Student</th>
                                    <th class="px-5 py-3 font-semibold">Student ID</th>
                                    <th class="px-5 py-3 font-semibold">Program</th>
                                    <th class="px-5 py-3 font-semibold">Year Level</th>
                                    <th class="px-5 py-3 font-semibold">Submitted Date</th>
                                    <th class="px-5 py-3 font-semibold">Quality Status</th>
                                    <th class="px-5 py-3 font-semibold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($recentSubmissions as $submission)
                                    <tr class="hover:bg-muted/20 transition-colors">
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border bg-white shadow-xs">
                                                    <img src="{{ $submission->user->avatar_url }}" alt="Avatar" width="32" height="32" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-foreground text-sm">
                                                        {{ $submission->user->first_name }} {{ $submission->user->last_name }}
                                                    </div>
                                                    <div class="text-xs text-foreground/50">{{ $submission->user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-foreground font-medium">
                                            {{ $submission->user->student_id ?? 'N/A' }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-foreground">
                                            {{ $submission->user->structuredProgram ? $submission->user->structuredProgram->code : ($submission->user->program ?? 'N/A') }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-foreground/70">
                                            {{ $submission->user->year_level ?? 'N/A' }} Year
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <div class="text-xs text-foreground font-medium">{{ $submission->submitted_at?->format('M d, Y h:i A') }}</div>
                                            <div class="text-[11px] text-foreground/50">{{ $submission->submitted_at?->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            @if($submission->quality_classification === 'FOR REVIEW' || $submission->flags->count() > 0)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                    For Review ({{ $submission->flags->count() }} flag{{ $submission->flags->count() === 1 ? '' : 's' }})
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Acceptable
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right">
                                            <a href="{{ route('staff.inventory.show', $submission->id) }}" class="inline-flex items-center gap-1 text-primary hover:text-primary-dark bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                                View &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-foreground/30 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm font-medium text-foreground/70">No submissions found</p>
                        <p class="text-xs text-foreground/50 mt-0.5">Student submissions for A.Y. {{ $academicYear ?? 'Current' }} will appear here.</p>
                    </div>
                @endif
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
