@section('title', auth()->user()->isCounselor() || auth()->user()->isAdmin() ? 'Dashboard' : 'Home')
@if(auth()->user()->isCounselor() || auth()->user()->isAdmin())
    <x-staff-layout>
        {{-- STAFF DASHBOARD --}}
        
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Welcome Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface p-6 rounded-token-lg border border-border shadow-xs">
                <div>
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold text-primary">
                        Welcome back, {{ auth()->user()->first_name }}
                    </h1>
                    @if(auth()->user()->isCounselor())
                        @if($assignedDepartments->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-1.5 mt-2 text-xs text-muted-foreground font-medium">
                                <span class="uppercase tracking-wider font-semibold">Assigned School{{ $assignedDepartments->count() > 1 ? 's' : '' }}:</span>
                                <span class="text-foreground font-semibold">{{ $assignedDepartments->pluck('name')->implode(', ') }}</span>
                            </div>
                        @else
                            <div class="mt-2 text-xs text-warning-foreground font-medium italic">
                                Unassigned (No School Assigned)
                            </div>
                        @endif
                    @else
                        <div class="mt-2 text-xs font-medium text-muted-foreground">
                            System Administrator &bull; All Schools
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-xs text-muted-foreground font-medium">
                        Academic Year: <span class="font-bold text-foreground">{{ $academicYear ?? 'Not Configured' }}</span>
                    </div>
                </div>
            </div>

            <!-- Unassigned Counselor Warning Banner -->
            @if(auth()->user()->isCounselor() && $assignedDepartments->isEmpty())
                <div role="alert" class="bg-warning-subtle border border-warning-border border-l-4 border-l-warning p-4 rounded-token-md shadow-xs flex items-center gap-3">
                    <x-icon name="exclamation-triangle" class="h-6 w-6 text-warning shrink-0" />
                    <div>
                        <h4 class="font-semibold text-warning-foreground">No school assigned yet. Please contact the system administrator.</h4>
                        <p class="text-xs text-warning-foreground/90 mt-0.5">You will not see student data or be able to manage inventory schedules until a school is assigned.</p>
                    </div>
                </div>
            @endif

            <!-- Summary Cards (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Card 1: Registered Students -->
                <a href="{{ route('staff.students.index', ['status' => 'active']) }}" 
                   class="group bg-surface rounded-token-lg p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-sm hover:border-primary/40 transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground group-hover:text-primary transition-colors flex items-center gap-1.5">
                            Registered Students
                            <svg aria-hidden="true" class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-token-md bg-primary-subtle text-primary border border-primary/15 flex items-center justify-center group-hover:bg-primary group-hover:text-on-primary transition-colors">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-foreground group-hover:text-primary transition-colors">{{ number_format($registeredStudentsCount) }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">Students under your assigned school{{ $assignedDepartments->count() > 1 ? 's' : '' }}</p>
                    </div>
                </a>

                <!-- Card 2: Submitted -->
                <a href="{{ route('staff.inventory.index', ['academic_year' => $academicYear]) }}" 
                   class="group bg-surface rounded-token-lg p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-sm hover:border-success-border transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-success-foreground flex items-center gap-1.5">
                            Submitted
                            <svg aria-hidden="true" class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-token-md bg-success-subtle text-success border border-success-border flex items-center justify-center group-hover:bg-success group-hover:text-on-primary transition-colors">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-success">{{ number_format($submittedCount) }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">{{ number_format($submittedCount) }} of {{ number_format($registeredStudentsCount) }} registered &middot; {{ $registeredStudentsCount > 0 ? ($completionPercentage ?? 0) . '%' : '—' }}</p>
                    </div>
                </a>

                <!-- Card 3: Pending -->
                <a href="{{ route('staff.students.index', ['status' => 'active', 'submission_status' => 'pending']) }}" 
                   class="group bg-surface rounded-token-lg p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-sm hover:border-warning-border transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-warning-foreground flex items-center gap-1.5">
                            Pending
                            <svg aria-hidden="true" class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-token-md bg-warning-subtle text-warning border border-warning-border flex items-center justify-center group-hover:bg-warning group-hover:text-on-primary transition-colors">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-warning">{{ number_format($pendingCount) }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">Students awaiting inventory submission</p>
                    </div>
                </a>

                <!-- Card 4: Flagged Responses -->
                <a href="{{ route('staff.flags.index', ['status' => 'unreviewed', 'academic_year' => $academicYear]) }}" 
                   class="group bg-surface rounded-token-lg p-6 border border-border shadow-xs flex flex-col justify-between hover:shadow-sm hover:border-destructive-border transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-destructive-foreground flex items-center gap-1.5">
                            Flagged Responses
                            <svg aria-hidden="true" class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <div class="w-10 h-10 rounded-token-md bg-destructive-subtle text-destructive border border-destructive-border flex items-center justify-center group-hover:bg-destructive group-hover:text-on-primary transition-colors">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-heading text-3xl font-bold text-destructive">{{ number_format($flaggedResponsesCount) }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">{{ $unreviewedFlagsCount }} unreviewed flag{{ $unreviewedFlagsCount === 1 ? '' : 's' }}</p>
                    </div>
                </a>
            </div>

            <!-- Assessment Progress Section -->
            <div class="bg-surface rounded-token-lg p-6 border border-border shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Assessment Completion Progress</h3>
                        <p class="text-xs text-muted-foreground">Student Individual Inventory submission progress for Academic Year {{ $academicYear ?? 'Current' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-heading text-2xl font-bold text-primary">{{ $completionPercentage !== null ? $completionPercentage . '%' : '—' }}</span>
                        <span class="text-xs text-muted-foreground block">Completed</span>
                    </div>
                </div>
                <div class="w-full bg-muted rounded-full h-3 overflow-hidden" role="progressbar" aria-valuenow="{{ min(100, $completionPercentage ?? 0) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Assessment completion progress">
                    <div class="bg-primary h-3 rounded-full transition-all duration-300" style="width: {{ min(100, $completionPercentage ?? 0) }}%"></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-4 mt-4 pt-4 border-t border-border text-center">
                    <a href="{{ route('staff.students.index') }}" class="group min-h-[44px] rounded-token-md p-2 hover:bg-muted/50 transition-colors cursor-pointer block">
                        <p class="text-xs text-muted-foreground uppercase font-semibold group-hover:text-primary transition-colors">Total Assigned</p>
                        <p class="font-bold text-foreground text-sm sm:text-base mt-0.5 group-hover:text-primary transition-colors">{{ number_format($assignedStudentsCount) }}</p>
                    </a>
                    <a href="{{ route('staff.inventory.index') }}" class="group min-h-[44px] rounded-token-md p-2 hover:bg-success-subtle transition-colors cursor-pointer block">
                        <p class="text-xs text-success-foreground uppercase font-semibold">Submitted</p>
                        <p class="font-bold text-success text-sm sm:text-base mt-0.5">{{ number_format($submittedCount) }}</p>
                    </a>
                    <a href="{{ route('staff.students.index', ['submission_status' => 'pending']) }}" class="group min-h-[44px] rounded-token-md p-2 hover:bg-warning-subtle transition-colors cursor-pointer block">
                        <p class="text-xs text-warning-foreground uppercase font-semibold">Pending</p>
                        <p class="font-bold text-warning text-sm sm:text-base mt-0.5">{{ number_format($pendingCount) }}</p>
                    </a>
                </div>
            </div>

            <!-- Needs Attention & Active Schedules Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Needs Attention Card -->
                <div class="bg-surface rounded-token-lg border border-border shadow-xs overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-border bg-destructive-subtle/60 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <x-icon name="exclamation-triangle" class="w-4 h-4 text-destructive" />
                            <h3 class="font-heading text-base font-bold text-foreground">Needs Attention</h3>
                        </div>
                        <x-badge variant="danger">
                            {{ $unreviewedFlagsCount }} Flag{{ $unreviewedFlagsCount === 1 ? '' : 's' }} For Review
                        </x-badge>
                    </div>
                    <div class="p-4 space-y-3 flex-1 flex flex-col justify-center">
                        <!-- Unreviewed Flags -->
                        <a href="{{ route('staff.flags.index', ['status' => 'unreviewed']) }}" class="flex items-center justify-between p-4 rounded-token-md border border-border hover:border-destructive-border hover:bg-destructive-subtle/40 transition-colors cursor-pointer group">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-token-sm bg-destructive-subtle text-destructive border border-destructive-border flex items-center justify-center shrink-0 mt-0.5">
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-foreground text-sm group-hover:text-primary transition-colors">
                                        Recent Unreviewed Flags
                                    </div>
                                    <p class="text-xs text-muted-foreground mt-0.5">Submissions flagged by response quality checks requiring verification.</p>
                                </div>
                            </div>
                            <div class="text-right pl-3 shrink-0">
                                <x-badge :variant="$unreviewedFlagsCount > 0 ? 'danger' : 'neutral'">
                                    {{ $unreviewedFlagsCount }}
                                </x-badge>
                            </div>
                        </a>

                        @if(isset($recentFlags) && $recentFlags->isNotEmpty())
                            <ul class="divide-y divide-border/70 rounded-token-md border border-border bg-surface-subtle/40">
                                @foreach($recentFlags as $flaggedSub)
                                    <li>
                                        <a href="{{ route('staff.inventory.show', $flaggedSub->id) }}" class="min-h-[44px] px-3.5 py-2.5 flex items-center justify-between gap-2 text-xs hover:bg-muted/60 transition-colors">
                                            <span class="font-semibold text-foreground truncate">
                                                {{ $flaggedSub->user ? trim("{$flaggedSub->user->first_name} {$flaggedSub->user->last_name}") : 'Unknown Student' }}
                                            </span>
                                            <span class="text-destructive font-semibold shrink-0">
                                                {{ $flaggedSub->flags->count() }} {{ Str::plural('flag', $flaggedSub->flags->count()) }} &rarr;
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <!-- Active Assessment Schedules -->
                <div class="bg-surface rounded-token-lg border border-border shadow-xs overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-border bg-primary-subtle/70 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-icon name="calendar" class="w-4 h-4 text-primary" />
                            <h3 class="font-heading text-base font-bold text-foreground">Active Schedules</h3>
                        </div>
                        <span class="text-xs font-medium text-muted-foreground">
                            A.Y. {{ $academicYear ?? 'Current' }}
                        </span>
                    </div>
                    <div class="p-4 flex-1">
                        @if($activeSchedules && $activeSchedules->count() > 0)
                            <div class="space-y-2.5">
                                @foreach($activeSchedules as $sched)
                                    <a href="{{ route('schedules.index') }}" class="p-3.5 rounded-token-md border border-border bg-surface-subtle/50 flex items-center justify-between hover:bg-muted/50 hover:border-primary/30 transition-colors cursor-pointer group block">
                                        <div>
                                            <div class="font-semibold text-sm text-foreground group-hover:text-primary transition-colors">
                                                {{ $sched->year_level }} Year Student Individual Inventory
                                            </div>
                                            <p class="text-xs text-muted-foreground mt-0.5">
                                                Target: {{ $sched->structuredProgram?->code ?? $sched->structuredProgram?->name ?? $sched->program ?? 'All Programs' }}
                                                @if($sched->close_date)
                                                    &bull; Closes: {{ \Carbon\Carbon::parse($sched->close_date)->format('M d, Y') }}
                                                @endif
                                            </p>
                                        </div>
                                        <x-badge variant="success" icon="check" class="shrink-0">Open</x-badge>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <x-icon name="clock" class="h-8 w-8 text-muted-foreground/50 mx-auto mb-2" />
                                <p class="text-sm font-medium text-foreground">No active schedules right now</p>
                                <p class="text-xs text-muted-foreground mt-0.5">Assessment schedules will appear here when open.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Submissions Table -->
            <div class="bg-surface rounded-token-lg border border-border shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="font-heading text-lg font-bold text-foreground">Recent Submissions</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Latest student inventories submitted under your school</p>
                    </div>
                    <a href="{{ route('staff.inventory.index') }}" class="inline-flex items-center gap-1 min-h-[44px] sm:px-2 text-xs font-semibold text-primary hover:text-primary-hover hover:underline cursor-pointer self-start sm:self-auto">
                        View All Submissions &rarr;
                    </a>
                </div>

                @if($recentSubmissions->count() > 0)
                    <div tabindex="0" role="region" aria-label="Recent student submissions" class="data-table-wrapper" data-stack-mobile="true">
                        <table class="min-w-full divide-y divide-border text-left">
                            <caption class="sr-only">Latest student inventory submissions</caption>
                            <thead>
                                <tr class="text-muted-foreground text-xs uppercase tracking-wider bg-surface-subtle">
                                    <th scope="col" class="px-5 py-3 font-semibold">Student</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Student ID</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Program</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Year Level</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Submitted Date</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Quality Status</th>
                                    <th scope="col" class="px-5 py-3 font-semibold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border" x-data>
                                @foreach($recentSubmissions as $submission)
                                    @php $submissionUrl = route('staff.inventory.show', $submission->id); @endphp
                                    <tr class="hover:bg-muted/50 transition-colors cursor-pointer" @click="if (!$event.target.closest('a, button')) { window.location = @js($submissionUrl); }">
                                        <td data-label="Student" class="px-5 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border bg-white shadow-xs">
                                                    @if($submission->user)
                                                        <img src="{{ $submission->user->avatar_url }}" alt="Avatar" width="32" height="32" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-foreground text-sm">
                                                        {{ $submission->user ? trim("{$submission->user->first_name} {$submission->user->last_name}") : 'Unknown Student' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Student ID" class="px-5 py-4 whitespace-nowrap text-sm text-foreground font-medium">
                                            {{ $submission->user?->student_id ?? 'N/A' }}
                                        </td>
                                        <td data-label="Program" class="px-5 py-4 whitespace-nowrap text-sm text-foreground">
                                            {{ $submission->user?->structuredProgram ? $submission->user->structuredProgram->code : ($submission->user?->program ?? 'N/A') }}
                                        </td>
                                        <td data-label="Year Level" class="px-5 py-4 whitespace-nowrap text-sm text-foreground/70">
                                            {{ $submission->user?->year_level ?? 'N/A' }} Year
                                        </td>
                                        <td data-label="Submitted" class="px-5 py-4 whitespace-nowrap">
                                            <div class="text-xs text-foreground font-medium">{{ $submission->submitted_at?->format('M d, Y h:i A') }}</div>
                                            <div class="text-[11px] text-foreground/50">{{ $submission->submitted_at?->diffForHumans() }}</div>
                                        </td>
                                        <td data-label="Quality" class="px-5 py-4 whitespace-nowrap">
                                            @if($submission->quality_classification === 'FOR REVIEW' || $submission->flags->count() > 0)
                                                <x-badge variant="danger" icon="exclamation-triangle">
                                                    For Review ({{ $submission->flags->count() }} flag{{ $submission->flags->count() === 1 ? '' : 's' }})
                                                </x-badge>
                                            @else
                                                <x-badge variant="success" icon="check">
                                                    Acceptable
                                                </x-badge>
                                            @endif
                                        </td>
                                        <td data-label="Action" class="px-5 py-4 whitespace-nowrap text-right">
                                            <a href="{{ route('staff.inventory.show', $submission->id) }}" class="inline-flex items-center gap-1 min-h-[44px] sm:min-h-0 text-primary hover:text-primary-hover bg-primary-subtle hover:bg-primary/15 px-3 py-1.5 rounded-token-sm text-xs font-semibold transition-colors cursor-pointer">
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
            <span class="font-heading font-semibold text-base text-muted-foreground leading-tight">
                {{ __('Home') }}
            </span>
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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 min-w-0">
                <h1 class="font-heading text-xl sm:text-3xl font-bold text-foreground tracking-tight break-words min-w-0">
                    Welcome back, {{ auth()->user()->first_name }} &middot; <span class="text-primary">{{ $studentProgramCode }}{{ $studentSection }}</span>
                    <span class="sr-only">{{ auth()->user()->year_level }}</span>
                </h1>
                <span class="text-xs sm:text-sm font-medium text-muted-foreground shrink-0">
                    {{ now()->format('l, F j, Y') }}
                </span>
            </div>

            <!-- Primary Card: Student Individual Inventory Status -->
            <div class="w-full">
                @if(isset($hasSubmitted) && $hasSubmitted)
                    <!-- State 1: Submitted -->
                    <div class="bg-surface border border-border rounded-token-lg p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-token-md bg-success-subtle text-success border border-success-border flex items-center justify-center shrink-0 mt-0.5">
                                <x-icon name="check" class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground">Student Individual Inventory Complete</h2>
                                    <x-badge variant="success" icon="check">Submitted</x-badge>
                                </div>
                                <p class="text-muted-foreground text-sm leading-relaxed">
                                    You've successfully completed your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ isset($submission) && $submission ? route('inventory.history.show', $submission->id) : route('inventory.history') }}" class="btn-primary text-sm w-full md:w-auto">
                                <span>View Details</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @elseif(isset($activeSchedule) && $activeSchedule)
                    <!-- State 2: Active / Open -->
                    <div class="bg-surface border border-border rounded-token-lg p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4 max-w-2xl">
                            <div class="w-11 h-11 rounded-token-md bg-primary-subtle text-primary border border-primary/20 flex items-center justify-center shrink-0 mt-0.5">
                                <x-icon name="document-text" class="w-5 h-5" />
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground leading-snug">
                                        Your Student Individual Inventory for A.Y. {{ $academicYearLabel }} is now open
                                    </h2>
                                    <x-badge variant="primary" icon="clock">Pending</x-badge>
                                </div>
                                <p class="text-muted-foreground text-sm leading-relaxed">
                                    You have not yet completed your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year. Please complete and submit the form before the closing date.
                                </p>
                                @if($activeSchedule->close_date)
                                    @php
                                        $daysLeft = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($activeSchedule->close_date)->endOfDay(), false);
                                    @endphp
                                    <div class="text-sm font-semibold {{ $daysLeft <= 3 ? 'text-destructive' : 'text-warning-foreground' }} flex items-center gap-1.5 pt-0.5">
                                        <x-icon name="clock" class="w-4 h-4 shrink-0" />
                                        <span>Closes in {{ floor($daysLeft) }} {{ Str::plural('day', floor($daysLeft)) }} ({{ \Carbon\Carbon::parse($activeSchedule->close_date)->format('M d, Y') }})</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ route('inventory.index') }}" class="btn-primary text-sm w-full md:w-auto">
                                <span>Start Assessment</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @elseif(isset($upcomingSchedule) && $upcomingSchedule)
                    <!-- State 3: Upcoming Assessment -->
                    <div class="bg-surface border border-border rounded-token-lg p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-token-md bg-warning-subtle text-warning border border-warning-border flex items-center justify-center shrink-0 mt-0.5">
                                <x-icon name="calendar" class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground">Student Individual Inventory Scheduled</h2>
                                    <x-badge variant="warning" icon="calendar">Scheduled</x-badge>
                                </div>
                                <p class="text-muted-foreground text-sm leading-relaxed">
                                    Your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year is scheduled to open on <span class="font-bold text-foreground">{{ \Carbon\Carbon::parse($upcomingSchedule->open_date)->format('F j, Y') }}</span>.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ route('inventory.index') }}" class="btn-secondary text-sm w-full md:w-auto">
                                <span>View Inventory</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- State 4: Not Yet Completed / No Active Schedule -->
                    <div class="bg-surface border border-border rounded-token-lg p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-token-md bg-muted text-muted-foreground border border-border flex items-center justify-center shrink-0 mt-0.5">
                                <x-icon name="calendar" class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="font-heading text-lg sm:text-xl font-bold text-foreground">Student Individual Inventory Pending</h2>
                                    <x-badge variant="neutral">Not Yet Open</x-badge>
                                </div>
                                <p class="text-muted-foreground text-sm leading-relaxed">
                                    You have not yet completed your Student Individual Inventory for the {{ $academicYearLabel ?? 'current' }} academic year. No assessment window is currently open for your year level.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 w-full md:w-auto">
                            <a href="{{ route('inventory.index') }}" class="btn-secondary text-sm w-full md:w-auto">
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
                        <h2 class="font-heading text-lg font-bold text-foreground">Campus Announcements</h2>
                        <p class="text-muted-foreground text-sm mt-0.5">Updates and notices from the Guidance and Counseling Office</p>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="inline-flex items-center min-h-[44px] px-2 text-sm font-semibold text-primary hover:underline shrink-0 cursor-pointer">
                        See all &rarr;
                    </a>
                </div>

                @if(isset($recentAnnouncements) && $recentAnnouncements->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($recentAnnouncements as $announcement)
                            <a href="{{ route('announcements.show', $announcement) }}" class="group bg-surface rounded-token-lg p-5 border border-border shadow-xs hover:shadow-sm hover:border-primary/35 transition-colors duration-150 flex flex-col gap-2 cursor-pointer">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs text-muted-foreground font-medium">
                                        {{ $announcement->published_at ? $announcement->published_at->format('M d, Y') : $announcement->created_at->format('M d, Y') }}
                                    </span>
                                    <x-badge variant="success">Published</x-badge>
                                </div>
                                <h3 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors line-clamp-1 leading-snug mt-0.5">
                                    {{ $announcement->title }}
                                </h3>
                                <p class="text-muted-foreground text-sm line-clamp-2 leading-relaxed">
                                    {{ Str::limit(strip_tags($announcement->content ?? $announcement->body), 120) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="bg-surface border border-border rounded-token-lg p-6 text-center shadow-xs">
                        <h3 class="font-heading text-base font-bold text-foreground mb-1">You're all caught up!</h3>
                        <p class="text-muted-foreground text-sm max-w-md mx-auto">There are no active announcements from the Guidance and Counseling Office right now.</p>
                    </div>
                @endif
            </div>

            <!-- Quick Actions Section -->
            <div class="space-y-3.5">
                <div>
                    <h2 class="font-heading text-lg font-bold text-foreground">Quick Actions</h2>
                    <p class="text-muted-foreground text-sm mt-0.5">Direct access to your student inventory records and account details</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 1. View Assessment History -->
                    <a href="{{ route('inventory.history') }}" class="group bg-surface rounded-token-lg p-5 border border-border shadow-xs hover:shadow-sm hover:border-primary/35 transition-colors duration-150 flex items-start gap-3.5 cursor-pointer">
                        <div class="w-10 h-10 rounded-token-md bg-primary-subtle text-primary border border-primary/15 flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-on-primary transition-colors">
                            <x-icon name="check-circle" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">View Assessment History</h3>
                            <p class="text-sm text-muted-foreground mt-0.5 leading-snug">Review past inventory submissions</p>
                        </div>
                    </a>

                    <!-- 2. View Announcements -->
                    <a href="{{ route('announcements.index') }}" class="group bg-surface rounded-token-lg p-5 border border-border shadow-xs hover:shadow-sm hover:border-primary/35 transition-colors duration-150 flex items-start gap-3.5 cursor-pointer">
                        <div class="w-10 h-10 rounded-token-md bg-success-subtle text-success border border-success-border flex items-center justify-center shrink-0 group-hover:bg-success group-hover:text-on-primary transition-colors">
                            <x-icon name="megaphone" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">View Announcements</h3>
                            <p class="text-sm text-muted-foreground mt-0.5 leading-snug">Read official notices and updates</p>
                        </div>
                    </a>

                    <!-- 3. View Inventory -->
                    <a href="{{ $inventoryDetailsUrl }}" class="group bg-surface rounded-token-lg p-5 border border-border shadow-xs hover:shadow-sm hover:border-primary/35 transition-colors duration-150 flex items-start gap-3.5 cursor-pointer">
                        <div class="w-10 h-10 rounded-token-md bg-primary-subtle text-primary border border-primary/15 flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-on-primary transition-colors">
                            <x-icon name="document-text" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">View Inventory</h3>
                            <p class="text-sm text-muted-foreground mt-0.5 leading-snug">Open current inventory or details</p>
                        </div>
                    </a>

                    <!-- 4. My Profile -->
                    <a href="{{ route('profile.edit') }}" class="group bg-surface rounded-token-lg p-5 border border-border shadow-xs hover:shadow-sm hover:border-primary/35 transition-colors duration-150 flex items-start gap-3.5 cursor-pointer">
                        <div class="w-10 h-10 rounded-token-md bg-success-subtle text-success border border-success-border flex items-center justify-center shrink-0 group-hover:bg-success group-hover:text-on-primary transition-colors">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-heading text-base font-bold text-foreground group-hover:text-primary transition-colors">My Profile</h3>
                            <p class="text-sm text-muted-foreground mt-0.5 leading-snug">View student record and settings</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
