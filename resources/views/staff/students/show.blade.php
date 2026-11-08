@section('title')Profile - {{ \Illuminate\Support\Str::limit($student->first_name . ' ' . $student->last_name, 25) }}@endsection
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-heading font-bold text-xl lg:text-2xl text-foreground leading-tight">
                    {{ __('Student Profile') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    Individual inventory details, risk evaluation, and assessment history.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Back to Student Profiles Link -->
        <div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-foreground/60 hover:text-primary transition-colors py-1 px-2 -ml-2 rounded-lg hover:bg-muted/60">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Back to Student Profiles</span>
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-destructive/10 border border-destructive/20 text-destructive text-sm font-medium flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-destructive shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Student Identity Header Card (Compact 4-Column Grid) -->
        <div class="bg-white rounded-2xl shadow-xs border border-border p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-border">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full overflow-hidden shrink-0 border border-border bg-white shadow-xs">
                        <img src="{{ $student->avatar_url }}" alt="Avatar" width="64" height="64" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h3 class="text-xl lg:text-2xl font-bold text-foreground">{{ $student->first_name }} {{ $student->middle_initial }} {{ $student->last_name }}</h3>
                            
                            @if($student->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200 shadow-2xs">
                                    Archived
                                </span>
                            @endif

                            @if($student->year_level_confirmed)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20 shadow-2xs">
                                    Confirmed
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                                    Pending
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-foreground/50 mt-1 font-mono">ID: {{ $student->student_id ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if($latestSubmission)
                        <a href="{{ route('staff.inventory.export', $latestSubmission) }}" target="_blank" class="px-3.5 py-2 bg-primary text-white text-xs font-semibold rounded-xl shadow-xs hover:bg-primary/90 transition-colors inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export as PDF
                        </a>
                    @endif
                    @if(auth()->user()->isAdmin())
                        @if($student->is_active)
                            <form action="{{ route('staff.students.archive', $student) }}" method="POST" onsubmit="return confirm('Are you sure you want to archive this student? They will be removed from the active student directory.');" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-3.5 py-2 bg-destructive text-white text-xs font-semibold rounded-xl shadow-xs hover:bg-destructive/90 transition-colors">
                                    Archive Student
                                </button>
                            </form>
                        @else
                            <form action="{{ route('staff.students.unarchive', $student) }}" method="POST" onsubmit="return confirm('Are you sure you want to unarchive this student and restore them to the active student directory?');" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-3.5 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl shadow-xs hover:bg-emerald-700 transition-colors">
                                    Unarchive Student
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- 4-Column Identity Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-4 gap-x-6 pt-5">
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Student ID</p>
                    <p class="text-sm font-bold text-foreground font-mono">{{ $student->student_id ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">School</p>
                    <p class="text-sm font-bold text-foreground">{{ $student->structuredProgram->department->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Program</p>
                    <p class="text-sm font-bold text-foreground" title="{{ $student->structuredProgram->name ?? '' }}">
                        {{ $student->structuredProgram->code ?? $student->program ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Year Level</p>
                    <p class="text-sm font-bold text-foreground">{{ $student->year_level ? (str_contains(strtolower($student->year_level), 'year') ? $student->year_level : $student->year_level . ' Year') : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Section</p>
                    <p class="text-sm font-bold text-foreground">{{ $student->section ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Contact Number</p>
                    <p class="text-sm font-bold text-foreground">{{ $student->contact_number ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Email</p>
                    <p class="text-sm font-bold text-foreground truncate" title="{{ $student->email }}">{{ $student->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Address</p>
                    <p class="text-sm font-bold text-foreground truncate" title="{{ $student->address_line1 }}">{{ $student->address_line1 ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Is Paying Student</p>
                    <p class="text-sm font-bold text-foreground">{{ $student->is_paying_student ? 'Yes' : 'No' }}</p>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-0.5">Submission Status</p>
                    @if($latestSubmission)
                        @if($latestSubmission->submitted_at)
                            <p class="text-sm font-bold text-emerald-700">Submitted on {{ $latestSubmission->submitted_at->format('M d, Y h:i A') }} (A.Y. {{ $latestSubmission->academic_year }})</p>
                        @else
                            <p class="text-sm font-bold text-amber-700">Incomplete Submission (A.Y. {{ $latestSubmission->academic_year }})</p>
                        @endif
                    @else
                        <p class="text-sm font-bold text-foreground/50">No submission recorded</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div x-data="{ activeTab: '{{ request('tab', 'overview') }}' }">
            <div class="border-b border-border">
                <nav class="-mb-px flex space-x-6">
                    <button @click="activeTab = 'overview'" 
                            :class="activeTab === 'overview' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground font-medium hover:border-border'" 
                            class="whitespace-nowrap py-3.5 px-1 border-b-2 text-sm transition-colors focus:outline-none">
                        Overview
                    </button>
                    <button @click="activeTab = 'responses'" 
                            :class="activeTab === 'responses' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground font-medium hover:border-border'" 
                            class="whitespace-nowrap py-3.5 px-1 border-b-2 text-sm transition-colors focus:outline-none">
                        Questionnaire Responses
                    </button>
                    <button @click="activeTab = 'history'" 
                            :class="activeTab === 'history' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground font-medium hover:border-border'" 
                            class="whitespace-nowrap py-3.5 px-1 border-b-2 text-sm transition-colors focus:outline-none">
                        Assessment History
                    </button>
                </nav>
            </div>

            <!-- Tab 1: Overview (Two Columns on xl) -->
            <div x-show="activeTab === 'overview'" x-transition.opacity class="mt-6">
                @if($latestSubmission)
                    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                        <!-- Left Column: Risk Summary Card -->
                        <div class="xl:col-span-5 bg-white rounded-2xl shadow-xs border border-border p-6 space-y-5">
                            <div class="flex items-center justify-between pb-3 border-b border-border">
                                <h4 class="font-heading font-bold text-base text-foreground">Risk Summary</h4>
                                <span class="text-xs text-foreground/50 font-medium">A.Y. {{ $latestSubmission->academic_year }}</span>
                            </div>

                            @php
                                $flagCount = $latestSubmission->flags->count();
                                $hasFlags = $flagCount > 0;
                                $overallRisk = $hasFlags ? 'Urgent' : 'Normal';
                                $flagsGrouped = $latestSubmission->flags->groupBy('flag_type');
                            @endphp

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-foreground/60 uppercase tracking-wider">Overall Risk Level</span>
                                @if($hasFlags)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                        {{ $overallRisk }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        {{ $overallRisk }}
                                    </span>
                                @endif
                            </div>

                            @if($hasFlags)
                                <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3.5 flex items-center justify-between gap-3 shadow-2xs">
                                    <div class="flex items-center gap-2 text-amber-900 text-xs font-bold">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-amber-600 shrink-0">
                                            <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Requires Review</span>
                                    </div>
                                    <a href="{{ route('staff.inventory.show', $latestSubmission->id) }}" class="btn-primary text-xs py-1.5 px-3 rounded-lg shrink-0 inline-flex items-center gap-1 shadow-2xs">
                                        <span>Review Submission</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </a>
                                </div>

                                <!-- Grouped Flags List (Collapsible if > 5 flags) -->
                                <div x-data="{ expanded: false }" class="pt-2">
                                    <div class="text-xs font-bold text-foreground/70 uppercase tracking-wider mb-2.5">
                                        Risk Flags Detected ({{ $flagCount }})
                                    </div>
                                    <div class="space-y-3">
                                        @php $itemIndex = 0; @endphp
                                        @foreach($flagsGrouped as $flagType => $flags)
                                            <div class="bg-red-50/50 rounded-xl p-3 border border-red-200/70">
                                                <div class="text-xs font-bold text-red-900 mb-1.5">
                                                    {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $flagType)) }} Flags ({{ $flags->count() }})
                                                </div>
                                                <ul class="space-y-1.5 text-xs text-red-800">
                                                    @foreach($flags as $flag)
                                                        @php $itemIndex++; @endphp
                                                        <li class="flex items-start gap-1.5 leading-snug" x-show="expanded || {{ $itemIndex }} <= 5">
                                                            <span class="text-red-500 font-bold shrink-0">&bull;</span>
                                                            <span>{{ $flag->readable_description }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($flagCount > 5)
                                        <div class="mt-2.5 text-center">
                                            <button type="button" @click="expanded = !expanded" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                                                <span x-text="expanded ? 'Show less flags' : 'Show all flags ({{ $flagCount }})'"></span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3.5 flex items-center gap-2.5 text-xs text-emerald-900 shadow-2xs">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>No specific risk flags detected in the latest assessment.</span>
                                </div>
                            @endif
                        </div>

                        <!-- Right Column: Latest Scoring Results Card -->
                        <div class="xl:col-span-7 bg-white rounded-2xl shadow-xs border border-border p-6 flex flex-col">
                            <div class="flex items-center justify-between pb-3 border-b border-border mb-4">
                                <div>
                                    <h4 class="font-heading font-bold text-base text-foreground">Latest Scoring Results</h4>
                                    <p class="text-xs text-foreground/50 mt-0.5">Academic Year: {{ $latestSubmission->academic_year }}</p>
                                </div>
                                <a href="{{ route('staff.inventory.show', $latestSubmission->id) }}" class="text-primary hover:text-primary-dark text-xs font-semibold inline-flex items-center gap-1">
                                    <span>View Full Results</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>

                            <!-- Compact table with sticky header and max-h-[60vh] scroll -->
                            <div class="max-h-[60vh] overflow-y-auto rounded-xl border border-border">
                                <table class="min-w-full divide-y divide-border text-left text-sm">
                                    <thead class="sticky top-0 bg-muted/90 backdrop-blur-xs z-10">
                                        <tr class="text-foreground/70 text-xs font-semibold uppercase tracking-wider">
                                            <th scope="col" class="px-4 py-2.5">Category</th>
                                            <th scope="col" class="px-3 py-2.5 text-center">Raw Score</th>
                                            <th scope="col" class="px-4 py-2.5">Interpretation</th>
                                            <th scope="col" class="px-3 py-2.5 text-center">Needs Counseling</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-white">
                                        @forelse($sortedScores as $score)
                                            @php
                                                $interp = $score->severity_label ?? $score->interpretation_label ?? 'Normal';
                                                $lowerInterp = strtolower($interp);
                                                $isSevere = str_contains($lowerInterp, 'severe') || str_contains($lowerInterp, 'high');
                                                $isModerate = str_contains($lowerInterp, 'moderate');
                                            @endphp
                                            <tr class="hover:bg-muted/30 transition-colors {{ $isSevere ? 'bg-red-50/20' : '' }}">
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-foreground text-sm">
                                                        {{ \App\Support\InstrumentLabel::for($score->category_name) }}
                                                    </div>
                                                    @if($score->subscale_name)
                                                        <div class="text-xs text-foreground/50 font-normal mt-0.5">
                                                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $score->subscale_name)) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3 text-center text-foreground font-mono text-sm">
                                                    {{ $score->raw_score ?? $score->score }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($isSevere)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                                            {{ $interp }}
                                                        </span>
                                                    @elseif($isModerate)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                            {{ $interp }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                            {{ $interp }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    @if($isSevere)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                                            Yes
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                            No
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-8 text-center text-xs text-foreground/50">
                                                    No category scores available for this submission.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-xs border border-border p-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-muted/60 text-foreground/40 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="font-semibold text-foreground text-sm">No assessments submitted</p>
                        <p class="text-xs text-foreground/50 mt-1">This student has not submitted any assessments yet.</p>
                    </div>
                @endif
            </div>

            <!-- Tab 2: Questionnaire Responses -->
            <div x-show="activeTab === 'responses'" style="display: none;" x-transition.opacity class="mt-6">
                @if($latestSubmission && isset($responsesByCategory) && $responsesByCategory->isNotEmpty())
                    <div class="space-y-6">
                        <!-- Quality Classification Banner -->
                        @php
                            $qa = $latestSubmission->qualityAssessment;
                            $classification = $latestSubmission->quality_classification ?? ($qa?->classification ?? ($latestSubmission->flags->isNotEmpty() ? 'FOR REVIEW' : 'ACCEPTABLE'));
                            $isForReview = $classification === 'FOR REVIEW';
                        @endphp
                        <div class="p-4 sm:p-5 rounded-2xl border {{ $isForReview ? 'bg-amber-50/70 border-amber-200 text-amber-950' : 'bg-emerald-50/70 border-emerald-200 text-emerald-950' }} shadow-2xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-start sm:items-center gap-3">
                                    @if($isForReview)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                            <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            RESPONSE QUALITY: FOR REVIEW
                                        </span>
                                        <div class="text-xs font-medium text-amber-900">
                                            @if($qa && !empty($qa->flag_reasons))
                                                {{ implode(' • ', $qa->flag_reasons) }}
                                            @else
                                                Quality review indicators were detected for this submission.
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-2xs">
                                            <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            RESPONSE QUALITY: ACCEPTABLE
                                        </span>
                                        <div class="text-xs font-medium text-emerald-900">
                                            All responses verified with normal pacing, variety, and question consistency.
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('staff.inventory.show', $latestSubmission->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $isForReview ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-primary text-white hover:bg-primary/90' }} transition-colors shrink-0 shadow-2xs">
                                    Open Verification View &rarr;
                                </a>
                            </div>
                        </div>

                        <!-- Category Tabs and Tables -->
                        <div class="bg-white rounded-2xl shadow-xs border border-border overflow-hidden" x-data="{ activeCategory: '{{ $responsesByCategory->keys()->first() }}' }">
                            <div class="px-6 py-4 border-b border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-bold text-foreground">Student Questionnaire Responses</h4>
                                    <p class="text-xs text-foreground/60 mt-0.5">Academic Year: {{ $latestSubmission->academic_year }} &bull; Submitted: {{ $latestSubmission->submitted_at ? $latestSubmission->submitted_at->format('M d, Y h:i A') : 'Incomplete' }}</p>
                                </div>
                                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                                    @foreach($responsesByCategory->keys() as $cat)
                                        <button 
                                            type="button"
                                            @click="activeCategory = '{{ $cat }}'"
                                            :class="activeCategory === '{{ $cat }}' ? 'bg-primary text-white font-semibold shadow-2xs' : 'bg-white text-foreground/80 border border-border hover:bg-muted'"
                                            class="px-3 py-1.5 rounded-lg text-xs transition-colors shrink-0 font-medium">
                                            {{ \App\Support\InstrumentLabel::for($cat) }} ({{ $responsesByCategory[$cat]->count() }})
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="p-6">
                                @foreach($responsesByCategory as $category => $responses)
                                    @php
                                        $catConfig = isset($inventoryConfig) ? $inventoryConfig->firstWhere('name', $category) : null;
                                    @endphp
                                    <div x-show="activeCategory === '{{ $category }}'" style="display: none;" class="space-y-4">
                                        <div class="overflow-x-auto rounded-xl border border-border">
                                            <table class="min-w-full divide-y divide-border text-sm">
                                                <thead class="bg-muted/40">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/60 uppercase w-16">Item #</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/60 uppercase">Question Prompt</th>
                                                        <th class="px-4 py-3 text-right text-xs font-semibold text-foreground/60 uppercase w-48">Student's Answer</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-border bg-white">
                                                    @foreach($responses->sortBy('item_number') as $resp)
                                                        @php
                                                            $prompt = $resp->questionItem?->prompt ?? ($catConfig?->questionItems->firstWhere('item_number', $resp->item_number)?->prompt ?? 'Question prompt unavailable');
                                                        @endphp
                                                        <tr class="hover:bg-muted/20 transition-colors">
                                                            <td class="px-4 py-3 font-semibold text-foreground/70 font-mono text-xs">
                                                                Q{{ $resp->item_number }}
                                                            </td>
                                                            <td class="px-4 py-3 text-foreground/90 leading-snug">
                                                                {{ $prompt }}
                                                            </td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                                                <span class="inline-flex items-center justify-center min-w-[2rem] px-3 py-1 rounded-full bg-primary/10 text-primary font-bold border border-primary/20 text-xs font-mono">
                                                                    {{ $resp->response_value }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white shadow-xs rounded-2xl border border-border p-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-muted/60 text-foreground/40 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <p class="font-semibold text-foreground text-sm">No questionnaire responses recorded</p>
                        <p class="text-xs text-foreground/50 mt-1">Responses will appear here after the student completes their inventory.</p>
                    </div>
                @endif
            </div>

            <!-- Tab 3: Assessment History -->
            <div x-show="activeTab === 'history'" style="display: none;" x-transition.opacity class="mt-6">
                <div class="bg-white shadow-xs rounded-2xl border border-border overflow-hidden">
                    <div class="px-6 py-4 border-b border-border bg-muted/20">
                        <h4 class="text-base font-bold text-foreground">Assessment History</h4>
                    </div>
                    @if($student->inventorySubmissions->isEmpty())
                        <div class="p-12 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-muted/60 text-foreground/40 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="font-semibold text-foreground text-sm">No past assessments found</p>
                            <p class="text-xs text-foreground/50 mt-1">Previous academic year submissions will be listed here.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-border">
                            @foreach($student->inventorySubmissions as $submission)
                                <li class="hover:bg-muted/30 transition-colors">
                                    <a href="{{ route('staff.inventory.show', $submission->id) }}" class="flex items-center justify-between p-6 group">
                                        <div>
                                            <h5 class="text-base font-bold text-foreground group-hover:text-primary transition-colors">Academic Year: {{ $submission->academic_year }}</h5>
                                            <p class="text-sm text-foreground/60 mt-1">Submitted on {{ $submission->submitted_at ? $submission->submitted_at->format('F j, Y \a\t g:i A') : 'Incomplete' }}</p>
                                            
                                            @if($submission->flags->count() > 0)
                                                <div class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full border border-red-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $submission->flags->count() }} Flags
                                                </div>
                                            @else
                                                <div class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    Normal
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-primary group-hover:translate-x-1 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
