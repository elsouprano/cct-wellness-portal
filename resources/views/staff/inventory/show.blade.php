@section('title')Review - {{ \Illuminate\Support\Str::limit($submission->user->first_name . ' ' . $submission->user->last_name, 25) }}@endsection
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    Inventory Verification View
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    A.Y. {{ $submission->academic_year }} &bull; {{ $submission->user?->first_name }} {{ $submission->user?->last_name }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="#student-responses" class="btn-secondary text-xs px-3.5 py-2 rounded-xl inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    <span>View Responses</span>
                </a>
                <a href="{{ route('staff.students.show', ['student' => $submission->user, 'tab' => 'responses']) }}" class="btn-secondary text-xs px-3.5 py-2 rounded-xl inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>Student Profile</span>
                </a>
                <a href="{{ route('staff.inventory.export', $submission) }}" class="btn-primary text-xs px-3.5 py-2 rounded-xl inline-flex items-center gap-1.5" target="_blank">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Download PDF</span>
                </a>
                <a href="{{ route('staff.inventory.index') }}" class="btn-secondary text-xs px-3.5 py-2 rounded-xl inline-flex items-center gap-1">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl text-emerald-800 text-sm font-medium shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Student Profile Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border p-6 sm:p-8">
                <h3 class="text-xl font-heading font-semibold mb-4 text-primary border-b border-border pb-2">Student Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm items-center">
                    <div class="flex items-center gap-4 col-span-2 md:col-span-1">
                        <div class="w-12 h-12 rounded-full overflow-hidden shrink-0 border border-border shadow-sm">
                            <img src="{{ $submission->user->avatar_url }}" alt="Avatar" width="48" height="48" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                        </div>
                        <div>
                            <span class="block text-foreground/60 font-medium">Name</span>
                            <span class="block font-semibold text-foreground">{{ $submission->user->last_name }}, {{ $submission->user->first_name }} {{ $submission->user->middle_initial }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="block text-foreground/60 font-medium">Program & Section</span>
                        <span class="block font-semibold text-foreground">{{ $submission->user->program }} - {{ $submission->user->section }}</span>
                    </div>
                    <div>
                        <span class="block text-foreground/60 font-medium">Year Level</span>
                        <span class="block font-semibold text-foreground">{{ $submission->user->year_level }}</span>
                    </div>
                    <div>
                        <span class="block text-foreground/60 font-medium">Submitted At</span>
                        <span class="block font-semibold text-foreground">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : 'Incomplete' }}</span>
                    </div>
                </div>
            </div>

            <!-- AUTOMATIC RESPONSE QUALITY ASSESSMENT CARD -->
            @php
                $assessment = $submission->qualityAssessment;
                $classification = $submission->quality_classification ?? ($assessment?->classification ?? ($submission->flags->isNotEmpty() ? 'FOR REVIEW' : 'ACCEPTABLE'));
                $isForReview = $classification === 'FOR REVIEW';
                $timingDetails = $assessment?->metrics['speed'] ?? [];
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border {{ $isForReview ? 'border-amber-300 ring-1 ring-amber-200' : 'border-emerald-200 ring-1 ring-emerald-100' }}">
                <div class="px-6 py-4 border-b {{ $isForReview ? 'bg-amber-50/70 border-amber-200' : 'bg-emerald-50/60 border-emerald-100' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold text-foreground/60 uppercase tracking-wide">Respondent #{{ $submission->user?->student_id ?? $submission->id }}</span>
                            <span class="text-xs text-foreground/40">&bull;</span>
                            <span class="text-xs font-medium text-foreground/60">
                                @if($assessment)
                                    Assessed {{ $assessment->assessed_at?->format('M d, Y h:i A') }} (Version {{ $assessment->version }})
                                @else
                                    Automated Assessment
                                @endif
                            </span>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-foreground mt-0.5">
                            Response Quality Assessment
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($isForReview)
                            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-sm">
                                <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                FOR REVIEW
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-sm">
                                <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                ACCEPTABLE
                            </span>
                        @endif

                        <form method="POST" action="{{ route('staff.inventory.reassess', $submission) }}" class="inline"
                              data-confirm="save"
                              data-confirm-title="Re-evaluate Submission"
                              data-confirm-message="Re-evaluate response quality against current settings and correlated pairs?"
                              data-confirm-button="Re-evaluate">
                            @csrf
                            <button type="submit" class="btn-secondary text-xs px-3 py-1.5 hover:bg-muted font-medium cursor-pointer" title="Re-evaluate response quality against current settings and correlated pairs">
                                Re-evaluate
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    @if($isForReview)
                        <!-- Simplified Neutral Guidance Note -->
                        <div class="p-4 bg-amber-50/70 rounded-2xl border border-amber-200 text-amber-950 text-sm leading-relaxed flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-200 text-amber-900 font-bold text-xs shrink-0">!</span>
                            <div>
                                <span class="font-semibold text-amber-900">Guidance Note:</span> Some responses may need verification. Review the items below to determine whether further checking is necessary.
                            </div>
                        </div>

                        <!-- Detected Indicators List -->
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-border/60">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-foreground/70">
                                    Detected Indicators &amp; Evidence
                                </h4>
                                <a href="#student-responses" class="text-xs text-primary hover:underline font-semibold inline-flex items-center gap-1">
                                    Jump to Full Questionnaire &darr;
                                </a>
                            </div>

                            @php
                                $indicatorCount = 0;
                                $contradictionPairs = $assessment?->metrics['contradiction']['flagged_pairs'] ?? [];
                                $straightLineCategories = $assessment?->metrics['straight_line']['categories'] ?? [];
                                $speedMetrics = $assessment?->metrics['speed'] ?? [];
                            @endphp

                            <!-- 1. Possible Inconsistent Answers -->
                            @if(!empty($contradictionPairs))
                                @foreach($contradictionPairs as $pair)
                                    @php $indicatorCount++; @endphp
                                    <div class="p-5 rounded-2xl bg-white border border-border shadow-2xs space-y-3.5" x-data="{ showDetails: false }">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="flex items-start gap-3">
                                                <span class="w-7 h-7 rounded-full bg-amber-100 text-amber-900 border border-amber-200 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                                    {{ $indicatorCount }}
                                                </span>
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h5 class="text-sm font-bold text-foreground">Possible Inconsistent Answers</h5>
                                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Consistency</span>
                                                    </div>
                                                    <p class="text-xs text-foreground/65 mt-0.5">
                                                        {{ \App\Support\InstrumentLabel::for($pair['category'] ?? null) }} &bull; Questions {{ $pair['item_a']['item_number'] ?? 'A' }} &amp; {{ $pair['item_b']['item_number'] ?? 'B' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" @click="showDetails = !showDetails" class="text-xs text-foreground/80 hover:text-primary font-semibold bg-muted/50 hover:bg-muted border border-border px-3 py-1.5 rounded-xl transition-colors cursor-pointer">
                                                <span x-text="showDetails ? 'Hide Details' : 'View Context'">View Context</span>
                                            </button>
                                        </div>

                                        <!-- Evidence Preview -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div class="p-3.5 bg-muted/30 rounded-xl border border-border/70 text-xs space-y-1.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold text-foreground/75">Question {{ $pair['item_a']['item_number'] }}</span>
                                                    <span class="font-bold text-primary px-2 py-0.5 rounded-md bg-primary/10 border border-primary/20">{{ $pair['item_a']['response'] }}</span>
                                                </div>
                                                <p class="text-foreground/85 leading-snug">"{{ $pair['item_a']['prompt'] }}"</p>
                                            </div>

                                            <div class="p-3.5 bg-muted/30 rounded-xl border border-border/70 text-xs space-y-1.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold text-foreground/75">Question {{ $pair['item_b']['item_number'] }}</span>
                                                    <span class="font-bold text-primary px-2 py-0.5 rounded-md bg-primary/10 border border-primary/20">{{ $pair['item_b']['response'] }}</span>
                                                </div>
                                                <p class="text-foreground/85 leading-snug">"{{ $pair['item_b']['prompt'] }}"</p>
                                            </div>
                                        </div>

                                        <p class="text-xs text-foreground/75 leading-relaxed">
                                            <span class="font-semibold text-foreground">Explanation:</span> These answers differ from the expected response pattern for related questions.
                                        </p>

                                        <!-- Collapsible details for counselor insight -->
                                        <div x-show="showDetails" style="display: none;" class="pt-2.5 text-xs text-foreground/65 border-t border-border/60 space-y-1">
                                            <div>Expected Relationship: <span class="font-medium text-foreground capitalize">{{ $pair['relationship_type'] ?? 'Related' }}</span></div>
                                            @if(!empty($pair['notes']))
                                                <div>Item Context: {{ $pair['notes'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            <!-- 2. Repeated Answer Pattern (Straight-lining) -->
                            @if($assessment && $assessment->hasStraightLineFlag())
                                @foreach($straightLineCategories as $catName => $catMetric)
                                    @if(isset($catMetric['percentage']) && isset($catMetric['threshold']) && $catMetric['percentage'] >= $catMetric['threshold'])
                                        @php $indicatorCount++; @endphp
                                        <div class="p-5 rounded-2xl bg-white border border-border shadow-2xs space-y-3">
                                            <div class="flex items-start gap-3">
                                                <span class="w-7 h-7 rounded-full bg-amber-100 text-amber-900 border border-amber-200 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                                    {{ $indicatorCount }}
                                                </span>
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h5 class="text-sm font-bold text-foreground">Repeated Answer Pattern</h5>
                                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Response Pattern</span>
                                                    </div>
                                                    <p class="text-xs text-foreground/65 mt-0.5">
                                                        {{ \App\Support\InstrumentLabel::for($catName) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="p-3.5 bg-muted/30 rounded-xl border border-border/70 text-xs text-foreground/85">
                                                Selected <span class="font-bold text-foreground">"{{ $catMetric['repeated_value'] ?? 'N/A' }}"</span> for <span class="font-bold text-foreground">{{ $catMetric['max_frequency'] ?? '0' }} of {{ $catMetric['total_items'] ?? '0' }}</span> items ({{ number_format($catMetric['percentage'] ?? 0, 1) }}%).
                                            </div>

                                            <p class="text-xs text-foreground/75 leading-relaxed">
                                                <span class="font-semibold text-foreground">Explanation:</span> The identical answer choice was selected repeatedly across consecutive items.
                                            </p>
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            <!-- 3. Fast Completion (Speed) -->
                            @if($assessment && $assessment->hasSpeedFlag())
                                @php $indicatorCount++; @endphp
                                <div class="p-5 rounded-2xl bg-white border border-border shadow-2xs space-y-3">
                                    <div class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-full bg-amber-100 text-amber-900 border border-amber-200 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                            {{ $indicatorCount }}
                                        </span>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h5 class="text-sm font-bold text-foreground">Fast Completion</h5>
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Completion Time</span>
                                            </div>
                                            <p class="text-xs text-foreground/65 mt-0.5">Entire Inventory Submission</p>
                                        </div>
                                    </div>

                                    <div class="p-3.5 bg-muted/30 rounded-xl border border-border/70 text-xs grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-foreground/60">Total Duration:</span>
                                            <span class="font-bold text-foreground ml-1">{{ $speedMetrics['total_duration_formatted'] ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-foreground/60">Average per Item:</span>
                                            <span class="font-bold text-amber-800 ml-1">{{ $speedMetrics['average_seconds_per_item'] ?? 'N/A' }}s / item</span>
                                        </div>
                                    </div>

                                    <p class="text-xs text-foreground/75 leading-relaxed">
                                        <span class="font-semibold text-foreground">Explanation:</span> The questionnaire was completed noticeably faster than typical reading and comprehension time.
                                    </p>
                                </div>
                            @endif

                            <!-- Fallback for legacy flags if assessment metrics not populated -->
                            @if($indicatorCount === 0 && $submission->flags->isNotEmpty())
                                @foreach($submission->flags as $flag)
                                    @php $indicatorCount++; @endphp
                                    <div class="p-4 rounded-xl bg-white border border-amber-200 shadow-xs flex items-start gap-3">
                                        <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                            {{ $indicatorCount }}
                                        </span>
                                        <div class="text-xs text-foreground/90 space-y-1">
                                            <div class="font-bold text-sm text-foreground">{{ $flag->readable_description }}</div>
                                            @if(!empty($flag->description) && $flag->description !== $flag->readable_description && !str_contains($flag->description, '_'))
                                                <p>{{ $flag->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Counselor Flag Review & Notes Section -->
                        @if($submission->flags->isNotEmpty())
                            <div class="pt-4 border-t border-border space-y-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-foreground/60">
                                    Counselor Review Verification Actions
                                </h4>

                                <div class="space-y-3">
                                    @foreach($submission->flags as $flag)
                                        <div class="p-4 rounded-xl border {{ $flag->is_reviewed ? 'bg-muted/40 border-border opacity-85' : 'bg-white border-destructive/20 shadow-sm' }} flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    @if($flag->is_reviewed)
                                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                            ✓ Reviewed by {{ $flag->reviewer->first_name ?? 'Staff' }} on {{ $flag->reviewed_at?->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center text-xs font-bold text-destructive bg-destructive/10 px-2.5 py-0.5 rounded-full">
                                                            {{ $flag->readable_description }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($flag->is_reviewed && $flag->reviewer_notes)
                                                    <div class="text-xs text-foreground/70 bg-white p-2.5 rounded-lg border border-border mt-1">
                                                        <span class="font-semibold text-foreground/80">Reviewer Note:</span> {{ $flag->reviewer_notes }}
                                                    </div>
                                                @endif
                                            </div>

                                            @if(!$flag->is_reviewed)
                                                <form method="POST" action="{{ route('staff.inventory.flags.review', $flag) }}" class="flex flex-col sm:flex-row items-end sm:items-center gap-2 shrink-0"
                                                      data-confirm="save"
                                                      data-confirm-title="Mark Flag as Reviewed"
                                                      data-confirm-message="Are you sure you want to mark this flag as reviewed?">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="reviewer_notes" placeholder="Optional review note..." class="text-xs border-border rounded-lg shadow-sm focus:ring-primary focus:border-primary py-1.5 px-3 w-56">
                                                    <button type="submit" class="btn-primary text-xs py-1.5 px-3 shrink-0">
                                                        Mark Reviewed
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    @else
                        <!-- ACCEPTABLE DETAILS -->
                        <div class="p-5 bg-emerald-50/40 rounded-2xl border border-emerald-100 flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-heading font-bold text-emerald-950 text-base">
                                    No Response Quality Concerns Detected
                                </h4>
                                <p class="text-sm text-emerald-800/80 mt-1 leading-relaxed">
                                    All responses satisfied deterministic quality checks. Pacing, response diversity across question batteries, and paired question consistency were verified within acceptable normal thresholds.
                                </p>
                                <div class="mt-3 flex flex-wrap gap-4 text-xs font-medium text-emerald-900/70">
                                    <span>&bull; Pacing: Normal ({{ $timingDetails['avg_seconds_per_item'] ?? 'N/A' }}s/question)</span>
                                    <span>&bull; Response Variation: Satisfactory</span>
                                    <span>&bull; Contradictions: None detected</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ORIGINAL STUDENT RESPONSES INSPECTION PANEL -->
            <div id="student-responses" class="bg-white rounded-3xl shadow-sm border border-border overflow-hidden scroll-mt-6" x-data="{ expandedCategory: '{{ $responsesByCategory->keys()->first() }}' }">
                <div class="px-6 py-5 border-b border-border bg-muted/20 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-heading font-bold text-foreground">
                                Original Student Responses
                            </h3>
                            <p class="text-xs text-foreground/60 mt-0.5">
                                Direct, unmodified answers submitted by the student for manual verification and counseling inspection.
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-foreground/60">
                            {{ $responsesByCategory->count() }} {{ \Illuminate\Support\Str::plural('category', $responsesByCategory->count()) }}
                        </span>
                    </div>

                    <!-- Mobile Category Select (< sm) -->
                    <div class="sm:hidden">
                        <label for="mobile-verification-category" class="sr-only">Select Questionnaire Category</label>
                        <select id="mobile-verification-category" x-model="expandedCategory" class="w-full rounded-xl border-border text-sm font-medium focus:border-primary focus:ring-primary bg-white">
                            @foreach($responsesByCategory->keys() as $catName)
                                <option value="{{ $catName }}">{{ \App\Support\InstrumentLabel::for($catName) }} ({{ $responsesByCategory[$catName]->count() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Desktop/Tablet Wrapping Category Pills (sm+) -->
                    <div class="hidden sm:flex flex-wrap items-center gap-2">
                        @foreach($responsesByCategory->keys() as $catName)
                            <button type="button" 
                                    @click="expandedCategory = '{{ $catName }}'"
                                    :class="expandedCategory === '{{ $catName }}' ? 'bg-primary text-white font-semibold border-primary shadow-2xs' : 'bg-white text-foreground/80 border-border hover:bg-muted'"
                                    class="px-3 py-1.5 rounded-xl border text-xs transition-colors font-medium cursor-pointer">
                                {{ \App\Support\InstrumentLabel::for($catName) }} ({{ $responsesByCategory[$catName]->count() }})
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="p-6">
                    @foreach($responsesByCategory as $catName => $catResponses)
                        <div x-show="expandedCategory === '{{ $catName }}'" style="display: none;" class="space-y-3">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border text-sm">
                                    <thead class="bg-muted/40">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-foreground/60 uppercase w-16">Item #</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-foreground/60 uppercase">Question Prompt</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-foreground/60 uppercase w-56">Student's Answer</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-white">
                                        @foreach($catResponses as $r)
                                            <tr class="hover:bg-muted/20 transition-colors">
                                                <td class="px-4 py-3 font-semibold text-foreground/70">
                                                    Q{{ $r->item_number }}
                                                </td>
                                                <td class="px-4 py-3 text-foreground/90 leading-snug">
                                                    {{ $r->questionItem?->prompt ?? 'Question prompt not found' }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-muted text-foreground border border-border">
                                                        {{ $r->response_value }}
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

            <!-- COMPUTED SCORES -->
            @if($submission->scores->isEmpty())
                <div class="bg-secondary/10 border-l-4 border-secondary p-4 rounded-xl text-secondary">
                    No computed scores found for this submission. It may still be in progress.
                </div>
            @else
                <div x-data="{ activeTab: '{{ $scoresByCategory->keys()->first() }}' }">
                    <!-- Tab Navigation -->
                    <div class="border-b border-border mb-6">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                            @foreach($scoresByCategory as $category => $scores)
                                <button 
                                    @click="activeTab = '{{ $category }}'"
                                    :class="activeTab === '{{ $category }}' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border font-medium'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-colors duration-150"
                                    :aria-selected="activeTab === '{{ $category }}'"
                                    role="tab"
                                >
                                    {{ \App\Support\InstrumentLabel::for($category) }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Panels -->
                    <div>
                        @foreach($scoresByCategory as $category => $scores)
                            <div 
                                x-show="activeTab === '{{ $category }}'"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                style="display: none;"
                                role="tabpanel"
                            >
                                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                                    <div class="px-6 py-4 border-b border-border bg-muted/30 flex justify-between items-center">
                                        <h4 class="text-lg font-bold text-foreground">{{ \App\Support\InstrumentLabel::for($category) }}</h4>
                                        @if(strtolower($category) === 'dass21')
                                            <span class="text-xs text-accent bg-accent/10 px-3 py-1 rounded-full font-semibold border border-accent/20">Severity Labels Apply</span>
                                        @endif
                                    </div>
                                    
                                    <div class="p-6">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            @foreach($scores as $score)
                                                <div class="p-5 border border-border rounded-2xl bg-muted/20 shadow-sm">
                                                    <div class="text-sm text-foreground/60 uppercase tracking-wide font-medium mb-1">
                                                        {{ $score->subscale_name ?? 'Overall Total' }}
                                                    </div>
                                                    <div class="text-3xl font-light text-primary">
                                                        @if(strtolower($category) === 'learning_style')
                                                            {{ $score->raw_score }}
                                                        @elseif(strtolower($category) === 'dass21')
                                                            {{ $score->scaled_score }} <span class="text-sm text-foreground/40 ml-1">/ 42</span>
                                                        @else
                                                            {{ $score->raw_score }}
                                                        @endif
                                                    </div>
                                                    @if($score->severity_label)
                                                        <div class="mt-3">
                                                            @php
                                                                if ($score->severity_color) {
                                                                    $colorClass = match(strtolower($score->severity_color)) {
                                                                        'green' => 'bg-green-100 text-green-800 border-green-200',
                                                                        'yellow' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                                        'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
                                                                        'red' => 'bg-red-100 text-red-800 border-red-200',
                                                                        'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                                        'blue' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                                        default => 'bg-gray-100 text-gray-800 border-gray-200'
                                                                    };
                                                                } else {
                                                                    $colorClass = match(strtolower($score->severity_label)) {
                                                                        'normal' => 'bg-primary/10 text-primary border-primary/20',
                                                                        'mild' => 'bg-secondary/10 text-secondary border-secondary/20',
                                                                        'moderate' => 'bg-accent/10 text-accent border-accent/20',
                                                                        'severe', 'extremely severe' => 'bg-destructive/10 text-destructive border-destructive/20',
                                                                        default => 'bg-muted text-foreground/80 border-border'
                                                                    };
                                                                }
                                                            @endphp
                                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full border {{ $colorClass }}">
                                                                {{ $score->severity_label }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if(strtolower($category) === 'dass21')
                                                        <div class="mt-3 text-xs font-medium text-foreground/50 bg-white inline-block px-2 py-1 rounded shadow-sm border border-border">Raw Sum: {{ $score->raw_score }} (x2 applied)</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        @if(strtolower($category) === 'dass21')
                                            <div class="mt-6 text-sm italic text-foreground/60 bg-muted/50 p-4 rounded-xl border border-border">
                                                Note: These severity labels reflect symptom severity, not a clinical diagnosis.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-staff-layout>
