@section('title', 'Counselor Review Dashboard')
<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Counselor Review Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Top Stats Bar: Response Quality Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Submissions Assessed -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-xs font-semibold text-foreground/60 uppercase tracking-wide">Assessed Submissions</div>
                    <div class="mt-2 text-3xl font-bold text-foreground">{{ number_format($totalAssessed) }}</div>
                    <div class="text-xs text-foreground/50 mt-1">Total completed & evaluated</div>
                </div>
                <!-- Acceptable Quality -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-xs font-semibold text-foreground/60 uppercase tracking-wide">Acceptable Quality</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600">{{ number_format($totalAcceptable) }}</div>
                    <div class="text-xs text-emerald-600/70 mt-1">No quality concerns detected</div>
                </div>
                <!-- Flagged for Review -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-xs font-semibold text-foreground/60 uppercase tracking-wide">Flagged for Review</div>
                    <div class="mt-2 text-3xl font-bold text-amber-600">{{ number_format($totalFlaggedForReview) }}</div>
                    <div class="text-xs text-amber-600/70 mt-1">Requires counselor verification</div>
                </div>
                <!-- Unreviewed Flags -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-xs font-semibold text-foreground/60 uppercase tracking-wide">Unreviewed Flags</div>
                    <div class="mt-2 text-3xl font-bold {{ $totalUnreviewedFlags > 0 ? 'text-destructive' : 'text-primary' }}">
                        {{ number_format($totalUnreviewedFlags) }}
                    </div>
                    <div class="text-xs text-foreground/50 mt-1">Pending counselor review</div>
                </div>
            </div>

            <!-- DASS21 Distribution Bar (Optional detailed breakdown) -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-border flex flex-wrap gap-4 text-sm">
                <span class="font-semibold text-foreground/80 mr-2">DASS21 Breakdown:</span>
                <span class="px-2 py-1 bg-muted text-foreground/80 rounded">Normal: {{ $dass21Stats['Normal'] ?? 0 }}</span>
                <span class="px-2 py-1 bg-yellow-50 text-yellow-700 rounded">Mild: {{ $dass21Stats['Mild'] ?? 0 }}</span>
                <span class="px-2 py-1 bg-orange-50 text-orange-700 rounded">Moderate: {{ $dass21Stats['Moderate'] ?? 0 }}</span>
                <span class="px-2 py-1 bg-destructive/10 text-destructive rounded">Severe: {{ $dass21Stats['Severe'] ?? 0 }}</span>
                <span class="px-2 py-1 bg-destructive/20 text-destructive rounded font-bold">Extreme: {{ $dass21Stats['Extremely Severe'] ?? 0 }}</span>
            </div>

            <!-- Filters & Search -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                <form method="GET" action="{{ route('staff.inventory.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <!-- Search -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-medium text-foreground/80">Search Student</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or Email..." class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                        </div>
                        
                        <!-- Academic Year -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/80">Academic Year</label>
                            <select name="academic_year" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year }}" {{ request('academic_year', $academicYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Level -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/80">Year Level</label>
                            <select name="year_level" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                <option value="">All Levels</option>
                                <option value="1st" {{ request('year_level') == '1st' ? 'selected' : '' }}>1st Year</option>
                                <option value="2nd" {{ request('year_level') == '2nd' ? 'selected' : '' }}>2nd Year</option>
                                <option value="3rd" {{ request('year_level') == '3rd' ? 'selected' : '' }}>3rd Year</option>
                                <option value="4th" {{ request('year_level') == '4th' ? 'selected' : '' }}>4th Year</option>
                            </select>
                        </div>
                        
                        <!-- Quality Classification -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/80">Quality Status</label>
                            <select name="quality_classification" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                <option value="">All Statuses</option>
                                <option value="ACCEPTABLE" {{ request('quality_classification') == 'ACCEPTABLE' ? 'selected' : '' }}>Acceptable Only</option>
                                <option value="FOR REVIEW" {{ request('quality_classification') == 'FOR REVIEW' ? 'selected' : '' }}>For Review Only</option>
                            </select>
                        </div>

                        <!-- Has Flags -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/80">Flag Status</label>
                            <select name="has_flags" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                <option value="">Any</option>
                                <option value="any" {{ request('has_flags') == 'any' ? 'selected' : '' }}>Has Flags</option>
                                <option value="unreviewed" {{ request('has_flags') == 'unreviewed' ? 'selected' : '' }}>Unreviewed Only</option>
                                <option value="none" {{ request('has_flags') == 'none' ? 'selected' : '' }}>No Flags</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-end justify-between">
                        <div class="flex space-x-4">
                            <div>
                                <label class="block text-xs font-medium text-foreground/80">Sort By</label>
                                <select name="sort" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                    <option value="submitted_at" {{ request('sort') == 'submitted_at' ? 'selected' : '' }}>Date Submitted</option>
                                    <option value="quality_classification" {{ request('sort') == 'quality_classification' ? 'selected' : '' }}>Quality Status</option>
                                    <option value="flag_count" {{ request('sort') == 'flag_count' ? 'selected' : '' }}>Flag Count</option>
                                    <option value="dass21_severity" {{ request('sort') == 'dass21_severity' ? 'selected' : '' }}>DASS21 Severity</option>
                                    <option value="student_name" {{ request('sort') == 'student_name' ? 'selected' : '' }}>Student Name</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-foreground/80">Direction</label>
                                <select name="direction" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                    <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Descending / Highest</option>
                                    <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Ascending / Lowest</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <a href="{{ route('staff.inventory.index') }}" class="btn-secondary">Clear</a>
                            <button type="submit" class="btn-primary">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Submissions Table -->
            <div class="bg-white shadow-sm sm:rounded-2xl border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Program / Sec</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider min-w-[210px]">Response Quality</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Highest DASS21</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-foreground/70 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border">
                            @forelse($submissions as $submission)
                                @php
                                    // Calculate highest DASS21 for display
                                    $highestDassSev = 'Normal';
                                    $sevVal = 1;
                                    if ($submission->scores) {
                                        foreach($submission->scores as $sc) {
                                            if (in_array(strtolower(str_replace('-', '', $sc->category_name)), ['dass21']) && $sc->severity_label) {
                                                $val = match(strtolower($sc->severity_label)) {
                                                    'extremely severe' => 5,
                                                    'severe' => 4,
                                                    'moderate' => 3,
                                                    'mild' => 2,
                                                    'normal' => 1,
                                                    default => 0
                                                };
                                                if ($val > $sevVal) {
                                                    $sevVal = $val;
                                                    $highestDassSev = $sc->severity_label;
                                                }
                                            }
                                        }
                                    }
                                    
                                    $dassColor = match($sevVal) {
                                        5, 4 => 'bg-destructive/10 text-destructive border-destructive/20',
                                        3 => 'bg-orange-100 text-orange-800 border-orange-200',
                                        2 => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        1 => 'bg-primary/10 text-primary border-primary/20',
                                        default => 'bg-muted text-foreground border-border'
                                    };

                                    $classification = $submission->quality_classification ?? ($submission->flags_count > 0 ? 'FOR REVIEW' : ($submission->submitted_at ? 'ACCEPTABLE' : null));
                                    $qa = $submission->qualityAssessment;
                                @endphp
                                <tr onclick="window.location='{{ route('staff.inventory.show', $submission) }}'" class="cursor-pointer hover:bg-muted/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border">
                                                <img src="{{ $submission->user?->avatar_url }}" alt="Avatar" width="32" height="32" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-foreground">{{ $submission->user?->last_name ?? 'Unknown' }}, {{ $submission->user?->first_name ?? 'Student' }}</div>
                                                <div class="text-xs text-foreground/60">{{ $submission->user?->email ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        {{ $submission->user?->program ?? 'N/A' }} / {{ $submission->user?->section ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        {{ $submission->user?->year_level ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : 'Incomplete' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($classification === 'ACCEPTABLE')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                                <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; display: inline-block;" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Acceptable
                                            </span>
                                        @elseif($classification === 'FOR REVIEW')
                                            <div class="flex flex-col gap-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 shrink-0 whitespace-nowrap">
                                                        <svg width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; display: inline-block;" class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        For Review
                                                    </span>
                                                    @if($submission->flags_count > 0)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-muted text-foreground/80 border border-border/60 shrink-0 whitespace-nowrap">
                                                            <span class="font-bold text-destructive">{{ $submission->unreviewed_flags }} Unreviewed</span>
                                                            <span class="text-foreground/40">/</span>
                                                            <span>{{ $submission->flags_count }} Total</span>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    @if($qa && $qa->hasSpeedFlag())
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 whitespace-nowrap">
                                                            ⚡ Fast Pace
                                                        </span>
                                                    @endif
                                                    @if($qa && $qa->hasStraightLineFlag())
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-orange-50 text-orange-700 border border-orange-200 whitespace-nowrap">
                                                            📏 Repetitive
                                                        </span>
                                                    @endif
                                                    @if($qa && $qa->hasContradictionFlag())
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 whitespace-nowrap">
                                                            🔀 Contradiction
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-foreground/40">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($submission->scores && $submission->scores->count() > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $dassColor }}">
                                                {{ $highestDassSev }}
                                            </span>
                                        @else
                                            <span class="text-foreground/40 text-sm">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('staff.inventory.show', $submission) }}" class="inline-flex items-center gap-1 text-primary hover:text-primary-dark transition-colors bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg text-xs font-semibold">
                                            View Responses &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-foreground/50">
                                        No submissions match your filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($submissions->hasPages())
                    <div class="p-4 border-t border-border">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-staff-layout>
