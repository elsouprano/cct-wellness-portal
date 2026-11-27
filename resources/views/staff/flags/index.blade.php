@section('title', 'Flagged Responses')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-bold text-2xl text-primary leading-tight">
                    Flagged Responses
                </h2>
                <p class="text-sm text-foreground/60 mt-1">
                    Review and verify student inventory submissions with rule-based response quality flags.
                </p>
            </div>
            <div class="text-xs text-foreground/50 bg-white px-3 py-1.5 rounded-xl border border-border shadow-xs">
                Academic Year: <span class="font-bold text-foreground">{{ $academicYear ?? 'Current' }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="bg-success-subtle border-l-4 border-success p-4 rounded-r-xl text-success-foreground text-sm font-medium shadow-sm flex items-center justify-between">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-border shadow-xs">
                <div class="text-xs font-semibold text-foreground/60 uppercase tracking-wide">Total Flags</div>
                <div class="text-2xl font-heading font-bold text-primary mt-1">{{ $totalFlagsCount }}</div>
                <div class="text-xs text-foreground/50 mt-1">Under assigned schools</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-border shadow-xs">
                <div class="text-xs font-semibold text-destructive uppercase tracking-wide">Unreviewed Flags</div>
                <div class="text-2xl font-heading font-bold text-destructive mt-1">{{ $unreviewedCount }}</div>
                <div class="text-xs text-foreground/50 mt-1">Awaiting counselor review</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-border shadow-xs">
                <div class="text-xs font-semibold text-success-foreground uppercase tracking-wide">Reviewed Flags</div>
                <div class="text-2xl font-heading font-bold text-success-foreground mt-1">{{ $reviewedCount }}</div>
                <div class="text-xs text-foreground/50 mt-1">Verified by guidance staff</div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white p-5 rounded-2xl border border-border shadow-xs">
            <form method="GET" action="{{ route('staff.flags.index') }}" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-border/60 pb-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('staff.flags.index', array_merge(request()->except('page'), ['status' => 'unreviewed'])) }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ $status === 'unreviewed' ? 'bg-destructive text-white shadow-xs' : 'bg-muted text-foreground/70 hover:bg-muted/80' }}">
                            Unreviewed ({{ $unreviewedCount }})
                        </a>
                        <a href="{{ route('staff.flags.index', array_merge(request()->except('page'), ['status' => 'reviewed'])) }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ $status === 'reviewed' ? 'bg-success text-white shadow-xs' : 'bg-muted text-foreground/70 hover:bg-muted/80' }}">
                            Reviewed ({{ $reviewedCount }})
                        </a>
                        <a href="{{ route('staff.flags.index', array_merge(request()->except('page'), ['status' => 'all'])) }}" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ $status === 'all' ? 'bg-primary text-white shadow-xs' : 'bg-muted text-foreground/70 hover:bg-muted/80' }}">
                            All ({{ $totalFlagsCount }})
                        </a>
                    </div>

                    <input type="hidden" name="status" value="{{ $status }}">

                    <!-- Academic Year Filter -->
                    @if($academicYears->count() > 1)
                        <div class="flex items-center gap-2 text-xs" x-data>
                            <span class="text-foreground/60 font-medium">AY:</span>
                            <select name="academic_year" @change="$el.form.submit()" class="rounded-xl border-border text-xs py-1.5 pl-3 pr-8 focus:ring-primary focus:border-primary">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay }}" {{ $academicYear === $ay ? 'selected' : '' }}>{{ $ay }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-1">
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-foreground/60 mb-1">Search Student</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search name, ID, or email..." class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-foreground/60 mb-1">Flag Type</label>
                        <select name="flag_type" class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                            <option value="all" {{ $flagType === 'all' || !$flagType ? 'selected' : '' }}>All Flag Types</option>
                            <option value="speed" {{ $flagType === 'speed' ? 'selected' : '' }}>Fast Completion (Speed)</option>
                            <option value="straight_line" {{ $flagType === 'straight_line' ? 'selected' : '' }}>Repetitive Answers (Straightlining)</option>
                            <option value="contradiction" {{ $flagType === 'contradiction' ? 'selected' : '' }}>Contradictory Answers</option>
                            <option value="dass21_severe" {{ $flagType === 'dass21_severe' ? 'selected' : '' }}>DASS-21 Severe / Extreme</option>
                        </select>
                    </div>

                    <div class="sm:col-span-4 flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary/90 transition-colors shadow-xs">
                            Filter
                        </button>
                        @if($search || ($flagType && $flagType !== 'all') || $status !== 'unreviewed')
                            <a href="{{ route('staff.flags.index') }}" class="px-3 py-2 bg-muted text-foreground/70 text-xs font-medium rounded-xl hover:bg-muted/80 transition-colors">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Flags Table -->
        <x-data-table
            caption="Flagged student inventory responses"
            :stack-mobile="true"
        >
            <thead class="bg-surface-subtle text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Student Information</th>
                    <th scope="col" class="px-6 py-3.5">Submission Date</th>
                    <th scope="col" class="px-6 py-3.5">Flag Reason</th>
                    <th scope="col" class="px-6 py-3.5">Quality Assessment</th>
                    <th scope="col" class="px-6 py-3.5">Review Status</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border text-sm bg-surface">
                @forelse($flags as $flag)
                    @php
                        $sub = $flag->submission;
                        $student = $sub?->user;
                        $qa = $sub?->qualityAssessment;
                        $classification = $sub?->quality_classification ?? ($qa?->classification ?? 'FOR REVIEW');
                    @endphp
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td data-label="Student" class="px-6 py-4 lg:whitespace-nowrap">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border bg-surface">
                                    <img src="{{ $student?->avatar_url }}" alt="Avatar" width="32" height="32" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ $student ? route('staff.students.show', $student) : '#' }}" class="font-bold text-foreground hover:text-primary transition-colors break-words">
                                        {{ $student?->last_name ?? 'Unknown' }}, {{ $student?->first_name ?? 'Student' }}
                                    </a>
                                    <div class="text-xs text-muted-foreground tabular-nums">
                                        {{ $student?->student_id ? 'ID: ' . $student->student_id . ' • ' : '' }}
                                        {{ $student?->structuredProgram?->code ?? $student?->program ?? 'N/A' }} 
                                        ({{ $student?->year_level ?? 'N/A' }})
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td data-label="Submitted" class="px-6 py-4 lg:whitespace-nowrap text-xs text-muted-foreground tabular-nums">
                            {{ $sub?->submitted_at ? $sub->submitted_at->format('M d, Y h:i A') : 'Incomplete' }}
                        </td>

                        <td data-label="Flag Reason" class="px-6 py-4">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-token-sm text-xs font-semibold
                                            {{ match($flag->flag_type) {
                                                'speed' => 'bg-destructive-subtle text-destructive-foreground border border-destructive-border',
                                                'straight_line' => 'bg-warning-subtle text-warning-foreground border border-warning-border',
                                                'contradiction' => 'bg-muted text-foreground border border-border',
                                                default => 'bg-warning-subtle text-warning-foreground border border-warning-border'
                                            } }}">
                                            @if($flag->flag_type === 'speed')
                                                <x-icon name="bolt" class="w-3.5 h-3.5" />
                                                Fast Completion ({{ $flag->details['avg_seconds_per_item'] ?? 'N/A' }}s/item)
                                            @elseif($flag->flag_type === 'straight_line')
                                                <x-icon name="bars-3-bottom-left" class="w-3.5 h-3.5" />
                                                Repetitive ({{ $flag->category ? \App\Support\InstrumentLabel::for($flag->category) : 'Battery' }})
                                            @elseif($flag->flag_type === 'contradiction')
                                                <x-icon name="arrows-right-left" class="w-3.5 h-3.5" />
                                                Contradiction ({{ $flag->category ? \App\Support\InstrumentLabel::for($flag->category) : 'Pairs' }})
                                            @else
                                                <x-icon name="exclamation-triangle" class="w-3.5 h-3.5" />
                                                {{ $flag->readable_description }}
                                            @endif
                                        </span>
                                        <div class="text-xs text-foreground/70 font-medium">
                                            {{ $flag->readable_description }}
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Quality Assessment" class="px-6 py-4 lg:whitespace-nowrap">
                                    @if($classification === 'FOR REVIEW')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-warning-subtle text-warning-foreground border border-warning-border">
                                            For Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-success-subtle text-success-foreground border border-success-border">
                                            Acceptable
                                        </span>
                                    @endif
                                </td>

                                <td data-label="Review Status" class="px-6 py-4 lg:whitespace-nowrap">
                                    @if($flag->is_reviewed)
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-token-sm text-xs font-semibold bg-success-subtle text-success-foreground border border-success-border">
                                                <x-icon name="check" class="w-3.5 h-3.5" />
                                                Reviewed
                                            </span>
                                            <div class="text-[11px] text-foreground/50 mt-0.5">
                                                By {{ $flag->reviewer?->first_name ?? 'Staff' }} on {{ $flag->reviewed_at?->format('M d') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-destructive/10 text-destructive border border-destructive/20">
                                            ● Unreviewed
                                        </span>
                                    @endif
                                </td>

                                <td data-label="Actions" class="px-6 py-4 lg:whitespace-nowrap text-right text-xs">
                                    <div class="flex flex-wrap items-center justify-end gap-2" x-data="{ showReviewModal: false }">
                                        @if($sub)
                                            <a href="{{ route('staff.inventory.show', $sub) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 font-semibold transition-colors">
                                                Inspect &rarr;
                                            </a>
                                        @endif

                                        <button type="button" @click="showReviewModal = true" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-border bg-white hover:bg-muted font-medium transition-colors text-foreground/80">
                                            {{ $flag->is_reviewed ? 'Unmark' : 'Review' }}
                                        </button>

                                        <!-- Review Notes Modal -->
                                        <div x-show="showReviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-foreground/50" @click.self="showReviewModal = false">
                                            <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-xl border border-border text-left">
                                                <h3 class="font-heading font-bold text-lg text-primary mb-2">
                                                    {{ $flag->is_reviewed ? 'Unmark Flag as Reviewed?' : 'Mark Flag as Reviewed' }}
                                                </h3>
                                                <p class="text-xs text-foreground/70 mb-4">
                                                    Student: <strong>{{ $student?->first_name }} {{ $student?->last_name }}</strong> &bull; Flag: {{ $flag->readable_description }}
                                                </p>

                                                <form method="POST" action="{{ route('staff.flags.review', $flag) }}"
                                                      data-confirm="save"
                                                      data-confirm-title="{{ $flag->is_reviewed ? 'Unmark Flag' : 'Verify Flag' }}"
                                                      data-confirm-message="Are you sure you want to {{ $flag->is_reviewed ? 'unmark this flag as reviewed' : 'confirm this flag as reviewed' }}?">
                                                    @csrf
                                                    @method('PATCH')

                                                    @if(!$flag->is_reviewed)
                                                        <div class="mb-4">
                                                            <label class="block text-xs font-semibold text-foreground/70 mb-1">Counselor Verification Notes (Optional)</label>
                                                            <textarea name="reviewer_notes" rows="3" placeholder="Add counseling observations, confirmation, or context..." class="w-full text-xs rounded-xl border-border p-3 focus:ring-primary focus:border-primary">{{ $flag->reviewer_notes }}</textarea>
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-warning-foreground bg-warning-subtle p-3 rounded-xl mb-4 border border-warning-border">
                                                            This will reset the flag to unreviewed status.
                                                        </p>
                                                    @endif

                                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                                                        <button type="button" @click="showReviewModal = false" class="px-4 py-2 bg-muted text-foreground/70 text-xs font-semibold rounded-xl hover:bg-muted/80">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="px-4 py-2 {{ $flag->is_reviewed ? 'bg-warning hover:bg-warning' : 'bg-primary hover:bg-primary/90' }} text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                                                            {{ $flag->is_reviewed ? 'Confirm Unmark' : 'Confirm Reviewed' }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-foreground/50">
                                    No flagged submissions match your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            <x-slot name="footer">
                <x-pagination :paginator="$flags" label="Flagged responses pagination" />
            </x-slot>
        </x-data-table>

    </div>
</x-staff-layout>

