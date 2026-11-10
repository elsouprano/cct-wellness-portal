@section('title', auth()->user()->isCounselor() || auth()->user()->isAdmin() ? 'Dashboard' : 'Home')
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
                            <div class="flex flex-wrap items-center gap-1.5 mt-2 text-xs text-foreground/70 font-medium">
                                <span class="text-foreground/50 uppercase tracking-wider font-semibold">Assigned School{{ $assignedDepartments->count() > 1 ? 's' : '' }}:</span>
                                <span class="text-foreground font-semibold">{{ $assignedDepartments->pluck('name')->implode(', ') }}</span>
                            </div>
                        @else
                            <div class="mt-2 text-xs text-amber-800 font-medium italic">
                                Unassigned (No School Assigned)
                            </div>
                        @endif
                    @else
                        <div class="mt-2 text-xs font-medium text-foreground/70">
                            System Administrator &bull; All Schools
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-xs text-foreground/70 font-medium">
                        Academic Year: <span class="font-bold text-foreground">{{ $academicYear ?? 'Not Configured' }}</span>
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
                        <h4 class="font-semibold text-amber-800">No school assigned yet. Please contact the system administrator.</h4>
                        <p class="text-xs text-amber-700 mt-0.5">You will not see student data or be able to manage inventory schedules until a school is assigned.</p>
                    </div>
                </div>
            @endif

            <!-- Summary Cards (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Card 1: Registered Students -->
                <a href="{{ route('staff.students.index', ['status' => 'active']) }}" 
                   class="group bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md hover:border-primary/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary transition-all cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-foreground/60 group-hover:text-primary transition-colors flex items-center gap-1.5">
                            Registered Students
                            <svg class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-foreground group-hover:text-primary transition-colors">{{ number_format($registeredStudentsCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">Students under your assigned school{{ $assignedDepartments->count() > 1 ? 's' : '' }}</p>
                    </div>
                </a>

                <!-- Card 2: Submitted -->
                <a href="{{ route('staff.inventory.index', ['academic_year' => $academicYear]) }}" 
                   class="group bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md hover:border-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-emerald-500 transition-all cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                            Submitted
                            <svg class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-emerald-600">{{ number_format($submittedCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">{{ number_format($submittedCount) }} of {{ number_format($registeredStudentsCount) }} registered &middot; {{ $registeredStudentsCount > 0 ? ($completionPercentage ?? 0) . '%' : '—' }}</p>
                    </div>
                </a>

                <!-- Card 3: Pending -->
                <a href="{{ route('staff.students.index', ['status' => 'active', 'submission_status' => 'pending']) }}" 
                   class="group bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md hover:border-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-amber-500 transition-all cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700 flex items-center gap-1.5">
                            Pending
                            <svg class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-amber-600">{{ number_format($pendingCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">Students awaiting inventory submission</p>
                    </div>
                </a>

                <!-- Card 4: Flagged Responses -->
                <a href="{{ route('staff.flags.index', ['status' => 'unreviewed', 'academic_year' => $academicYear]) }}" 
                   class="group bg-white rounded-2xl p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-md hover:border-rose-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-rose-500 transition-all cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1.5">
                            Flagged Responses
                            <svg class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:bg-rose-600 group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-rose-600">{{ number_format($flaggedResponsesCount) }}</h3>
                        <p class="text-xs text-foreground/50 mt-1">{{ $unreviewedFlagsCount }} unreviewed flag{{ $unreviewedFlagsCount === 1 ? '' : 's' }}</p>
                    </div>
                </a>
            </div>

            <!-- Assessment Progress Section -->
            <div class="bg-white rounded-2xl p-6 border border-border shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Assessment Completion Progress</h3>
                        <p class="text-xs text-foreground/60">Student Individual Inventory submission progress for Academic Year {{ $academicYear ?? 'Current' }}</p>
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
                    <a href="{{ route('staff.students.index') }}" class="group rounded-xl p-2 hover:bg-muted/30 transition-colors cursor-pointer block">
                        <p class="text-xs text-foreground/50 uppercase font-semibold group-hover:text-primary transition-colors">Total Assigned</p>
                        <p class="font-bold text-foreground text-sm sm:text-base mt-0.5 group-hover:text-primary transition-colors">{{ number_format($assignedStudentsCount) }}</p>
                    </a>
                    <a href="{{ route('staff.inventory.index') }}" class="group rounded-xl p-2 hover:bg-emerald-50/50 transition-colors cursor-pointer block">
                        <p class="text-xs text-emerald-600 uppercase font-semibold">Submitted</p>
                        <p class="font-bold text-emerald-600 text-sm sm:text-base mt-0.5">{{ number_format($submittedCount) }}</p>
                    </a>
                    <a href="{{ route('staff.students.index', ['submission_status' => 'pending']) }}" class="group rounded-xl p-2 hover:bg-amber-50/50 transition-colors cursor-pointer block">
                        <p class="text-xs text-amber-600 uppercase font-semibold">Pending</p>
                        <p class="font-bold text-amber-600 text-sm sm:text-base mt-0.5">{{ number_format($pendingCount) }}</p>
                    </a>
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
                        <span class="text-xs font-medium text-foreground/60">
                            A.Y. {{ $academicYear ?? 'Current' }}
                        </span>
                    </div>
                    <div class="p-4 flex-1">
                        @if($activeSchedules && $activeSchedules->count() > 0)
                            <div class="space-y-2.5">
                                @foreach($activeSchedules as $sched)
                                    <a href="{{ route('schedules.index') }}" class="p-3.5 rounded-xl border border-border bg-muted/10 flex items-center justify-between hover:bg-muted/20 hover:border-primary/30 transition-colors cursor-pointer group block">
                                        <div>
                                            <div class="font-semibold text-sm text-foreground group-hover:text-primary transition-colors">
                                                {{ $sched->year_level }} Year Student Individual Inventory
                                            </div>
                                            <p class="text-xs text-foreground/60 mt-0.5">
                                                Target: {{ $sched->program ?? 'All Programs' }}
                                                @if($sched->close_date)
                                                    &bull; Closes: {{ \Carbon\Carbon::parse($sched->close_date)->format('M d, Y') }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 shrink-0">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Open
                                        </span>
                                    </a>
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
                        <p class="text-xs text-foreground/60 mt-0.5">Latest student inventories submitted under your school</p>
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
                                    <tr class="hover:bg-muted/50 transition-colors cursor-pointer" onclick="window.location='{{ route('staff.inventory.show', $submission->id) }}'">
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border bg-white shadow-xs">
                                                    <img src="{{ $submission->user->avatar_url }}" alt="Avatar" width="32" height="32" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-foreground text-sm">
                                                        {{ $submission->user->first_name }} {{ $submission->user->last_name }}
                                                    </div>
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
        <x-slot name="header">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Home') }}
            </h2>
        </x-slot>

        @php
            $studentProgramCode = auth()->user()->structuredProgram ? auth()->user()->structuredProgram->code : (auth()->user()->program ?? 'Student');
            $studentSection = auth()->user()->section ? ' ' . auth()->user()->section : '';
            $inventoryDetailsUrl = (isset($hasSubmitted) && $hasSubmitted && isset($submission) && $submission)
                ? route('inventory.history.show', $submission->id)
                : route('inventory.index');
        @endphp

        <div class="space-y-6">
            {{-- STUDENT HOME --}}
            
            <!-- Welcome Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h1 class="font-heading text-lg sm:text-xl font-bold text-foreground">
                    Welcome back, {{ auth()->user()->first_name }} &middot; <span class="text-primary">{{ $studentProgramCode }}{{ $studentSection }}</span>
                    <span class="sr-only">{{ auth()->user()->year_level }}</span>
                </h1>
                <span class="text-sm font-medium text-foreground/60">
                    {{ now()->format('l, F j, Y') }}
                </span>
            </div>

            <!-- Primary Card: Student Individual Inventory Status -->
            <div class="w-full">
                @if(isset($hasSubmitted) && $hasSubmitted)
                    <!-- State 1: Submitted -->
                    <div class="bg-white border border-border rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-xl bg-secondary/10 text-secondary border border-secondary/20 flex items-center justify-center shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground">Student Individual Inventory Complete</h2>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold bg-secondary/10 text-secondary border border-secondary/20">
                                        Submitted
                                    </span>
                                </div>
                                <p class="text-foreground/70 text-sm leading-relaxed">
                                    You've successfully completed your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ isset($submission) && $submission ? route('inventory.history.show', $submission->id) : route('inventory.history') }}" class="btn-primary text-sm px-5 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 w-full md:w-auto shadow-xs">
                                <span>View Details</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @elseif(isset($activeSchedule) && $activeSchedule)
                    <!-- State 2: Active / Open -->
                    <div class="bg-white border border-border rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4 max-w-2xl">
                            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                                </svg>
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground leading-snug">
                                        Your Student Individual Inventory for A.Y. {{ $academicYearLabel }} is now open
                                    </h2>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-sm font-semibold bg-primary/10 text-primary border border-primary/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                        Pending
                                    </span>
                                </div>
                                <p class="text-foreground/70 text-sm leading-relaxed">
                                    You have not yet completed your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year. Please complete and submit the form before the closing date.
                                </p>
                                @if($activeSchedule->close_date)
                                    @php
                                        $daysLeft = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSchedule->close_date)->endOfDay(), false);
                                    @endphp
                                    <div class="text-sm font-semibold {{ $daysLeft <= 3 ? 'text-destructive' : 'text-amber-700' }} flex items-center gap-1.5 pt-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 shrink-0">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <span>Closes in {{ floor($daysLeft) }} {{ Str::plural('day', floor($daysLeft)) }} ({{ \Carbon\Carbon::parse($activeSchedule->close_date)->format('M d, Y') }})</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ route('inventory.index') }}" class="btn-primary text-sm px-5 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 w-full md:w-auto shadow-xs">
                                <span>Start Assessment</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @elseif(isset($upcomingSchedule) && $upcomingSchedule)
                    <!-- State 3: Upcoming Assessment -->
                    <div class="bg-white border border-border rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground">Student Individual Inventory Scheduled</h2>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        Scheduled
                                    </span>
                                </div>
                                <p class="text-foreground/70 text-sm leading-relaxed">
                                    Your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year is scheduled to open on <span class="font-bold text-foreground">{{ \Carbon\Carbon::parse($upcomingSchedule->open_date)->format('F j, Y') }}</span>.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ route('inventory.index') }}" class="btn-secondary text-sm px-5 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 w-full md:w-auto">
                                <span>View Inventory</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- State 4: Not Yet Completed / No Active Schedule -->
                    <div class="bg-white border border-border rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-xl bg-muted text-foreground/60 border border-border flex items-center justify-center shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground">Student Individual Inventory Pending</h2>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold bg-muted text-foreground/70 border border-border">
                                        Not Yet Open
                                    </span>
                                </div>
                                <p class="text-foreground/70 text-sm leading-relaxed">
                                    You have not yet completed your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year. No assessment window is currently open for your year level.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ route('inventory.index') }}" class="btn-secondary text-sm px-5 py-2.5 rounded-xl inline-flex items-center justify-center gap-2 w-full md:w-auto">
                                <span>View Inventory</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Campus Announcements Section -->
            <div class="space-y-3.5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Campus Announcements</h3>
                        <p class="text-foreground/60 text-sm mt-0.5">Updates and notices from the Guidance and Counseling Office</p>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="text-sm font-semibold text-primary hover:underline shrink-0">
                        See all &rarr;
                    </a>
                </div>

                @if(isset($recentAnnouncements) && $recentAnnouncements->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($recentAnnouncements as $announcement)
                            <a href="{{ route('announcements.show', $announcement) }}" class="group bg-white rounded-2xl p-5 border border-border shadow-xs hover:shadow-md hover:border-primary/30 transition-all flex flex-col gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm text-foreground/60 font-medium">
                                        {{ $announcement->published_at ? $announcement->published_at->format('M d, Y') : $announcement->created_at->format('M d, Y') }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold bg-secondary/10 text-secondary border border-secondary/20">
                                        Published
                                    </span>
                                </div>
                                <h4 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors line-clamp-1 leading-snug mt-0.5">
                                    {{ $announcement->title }}
                                </h4>
                                <p class="text-foreground/70 text-sm line-clamp-2 leading-relaxed">
                                    {{ Str::limit(strip_tags($announcement->content ?? $announcement->body), 120) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white border border-border rounded-2xl p-6 text-center shadow-xs">
                        <h3 class="font-heading text-base font-bold text-foreground mb-1">You're all caught up!</h3>
                        <p class="text-foreground/60 text-sm max-w-md mx-auto">There are no active announcements from the Guidance and Counseling Office right now.</p>
                    </div>
                @endif
            </div>

            <!-- Quick Actions Section -->
            <div class="space-y-3.5">
                <div>
                    <h3 class="font-heading text-lg font-bold text-foreground">Quick Actions</h3>
                    <p class="text-foreground/60 text-sm mt-0.5">Direct access to your student inventory records and account details</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 1. View Assessment History -->
                    <a href="{{ route('inventory.history') }}" class="group bg-white rounded-2xl p-5 border border-border shadow-xs hover:shadow-md hover:border-primary/30 transition-all flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">View Assessment History</h4>
                            <p class="text-sm text-foreground/60 mt-0.5 leading-snug">Review past inventory submissions</p>
                        </div>
                    </a>

                    <!-- 2. View Announcements -->
                    <a href="{{ route('announcements.index') }}" class="group bg-white rounded-2xl p-5 border border-border shadow-xs hover:shadow-md hover:border-primary/30 transition-all flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0 group-hover:bg-secondary group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">View Announcements</h4>
                            <p class="text-sm text-foreground/60 mt-0.5 leading-snug">Read official notices and updates</p>
                        </div>
                    </a>

                    <!-- 3. View Inventory -->
                    <a href="{{ $inventoryDetailsUrl }}" class="group bg-white rounded-2xl p-5 border border-border shadow-xs hover:shadow-md hover:border-primary/30 transition-all flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">View Inventory</h4>
                            <p class="text-sm text-foreground/60 mt-0.5 leading-snug">Open current inventory or details</p>
                        </div>
                    </a>

                    <!-- 4. My Profile -->
                    <a href="{{ route('profile.edit') }}" class="group bg-white rounded-2xl p-5 border border-border shadow-xs hover:shadow-md hover:border-primary/30 transition-all flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center shrink-0 group-hover:bg-secondary group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">My Profile</h4>
                            <p class="text-sm text-foreground/60 mt-0.5 leading-snug">View student record and settings</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
