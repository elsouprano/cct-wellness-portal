@section('title')Review - {{ \Illuminate\Support\Str::limit($submission->user->first_name . ' ' . $submission->user->last_name, 25) }}@endsection
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                Inventory Verification View
            </h2>
            <div class="flex items-center gap-3">
                <a href="#student-responses" class="btn-secondary text-sm px-4 py-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    View Responses
                </a>
                <a href="{{ route('staff.students.show', $submission->user) }}" class="btn-secondary text-sm px-4 py-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Student Profile
                </a>
                <a href="{{ route('staff.inventory.export', $submission) }}" class="btn-primary text-sm px-4 py-2 flex items-center gap-2" target="_blank">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download PDF
                </a>
                <a href="{{ route('staff.inventory.index') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back</a>
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

                        <form method="POST" action="{{ route('staff.inventory.reassess', $submission) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-secondary text-xs px-3 py-1.5 hover:bg-muted font-medium" title="Re-evaluate response quality against current settings and correlated pairs">
                                Re-evaluate
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    @if($isForReview)
                        <!-- Contextual explanation banner -->
                        <div class="p-4 bg-amber-50/50 rounded-2xl border border-amber-200 text-amber-900 text-sm leading-relaxed">
                            <strong class="font-semibold">Guidance Note:</strong> One or more response quality indicators were triggered by deterministic rules. This is not proof of carelessness or dishonesty; it provides specific context for guidance personnel during verification.
                        </div>

                        <!-- Detected Indicators List -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-foreground/60">
                                Detected Indicators & Specific Evidence
                            </h4>

                            @if($assessment && !empty($assessment->flag_reasons))
                                <div class="space-y-3">
                                    @foreach($assessment->flag_reasons as $reason)
                                        <div class="p-4 rounded-xl bg-muted/40 border border-border flex items-start gap-3">
                                            <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">
                                                !
                                            </div>
                                            <div class="text-sm text-foreground/90 leading-relaxed font-medium">
                                                {{ $reason }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($submission->flags->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach($submission->flags as $flag)
                                        <div class="p-4 rounded-xl bg-muted/40 border border-border flex items-start gap-3">
                                            <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">
                                                !
                                            </div>
                                            <div class="text-sm text-foreground/90 leading-relaxed">
                                                @if($flag->flag_type === 'speed')
                                                    <strong>Unusually fast completion:</strong> Average time of {{ $flag->details['avg_seconds_per_item'] ?? 'N/A' }}s per item (Threshold: {{ $flag->details['threshold'] ?? '1.5' }}s).
                                                @elseif($flag->flag_type === 'straight_line')
                                                    <strong>Excessive repetitive answers:</strong> Selected identical response for {{ $flag->details['percentage'] ?? 'N/A' }}% of questions in {{ $flag->category }}.
                                                @elseif($flag->flag_type === 'contradiction')
                                                    <strong>Potentially contradictory answers:</strong> Inconsistent responses in {{ $flag->category }} between correlated items.
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="pt-1">
                                <a href="#student-responses" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-primary text-white hover:bg-primary/90 transition-colors shadow-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    Inspect Full Student Responses Below
                                </a>
                            </div>
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
                                                        <span class="inline-flex items-center text-xs font-bold text-destructive bg-destructive/10 px-2.5 py-0.5 rounded-full uppercase tracking-wide">
                                                            {{ str_replace('_', ' ', $flag->flag_type) }} Flag
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
                                                <form method="POST" action="{{ route('staff.inventory.flags.review', $flag) }}" class="flex flex-col sm:flex-row items-end sm:items-center gap-2 shrink-0">
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
                                    :class="activeTab === '{{ $category }}' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-colors duration-150 uppercase"
                                    :aria-selected="activeTab === '{{ $category }}'"
                                    role="tab"
                                >
                                    {{ $category }}
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
                                        <h4 class="text-lg font-bold text-foreground uppercase">{{ $category }}</h4>
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

            <!-- ORIGINAL STUDENT RESPONSES INSPECTION PANEL -->
            <div id="student-responses" class="bg-white rounded-3xl shadow-sm border border-border overflow-hidden scroll-mt-6" x-data="{ expandedCategory: '{{ $responsesByCategory->keys()->first() }}' }">
                <div class="px-6 py-5 border-b border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-heading font-bold text-foreground">
                            Original Student Responses
                        </h3>
                        <p class="text-xs text-foreground/60 mt-0.5">
                            Direct, unmodified answers submitted by the student for manual verification and counseling inspection.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 overflow-x-auto">
                        @foreach($responsesByCategory as $catName => $catResponses)
                            <button type="button" 
                                    @click="expandedCategory = '{{ $catName }}'"
                                    :class="expandedCategory === '{{ $catName }}' ? 'bg-primary text-white font-semibold' : 'bg-white text-foreground/70 border border-border hover:bg-muted'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition-colors shrink-0 uppercase tracking-wide">
                                {{ str_replace('_', ' ', $catName) }} ({{ $catResponses->count() }})
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

        </div>
    </div>
</x-staff-layout>
