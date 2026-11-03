@section('title', 'Counselor Review Dashboard')
<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Counselor Review Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-5">
        
        <!-- Top Stats (5 Cards in 1 Row on xl and up) -->
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <!-- 1. Registered students -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-border shadow-xs flex flex-col justify-between">
                <div>
                    <div class="text-xs font-medium text-foreground/60 whitespace-nowrap">Registered students</div>
                    <div class="mt-1 text-2xl font-bold text-foreground whitespace-nowrap">{{ number_format($registeredStudentsCount) }}</div>
                </div>
                <div class="text-[11px] text-foreground/50 mt-2 truncate" title="Total active student accounts">Total active student accounts</div>
            </div>

            <!-- 2. Total submissions -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-border shadow-xs flex flex-col justify-between">
                <div>
                    <div class="text-xs font-medium text-foreground/60 whitespace-nowrap">Total submissions</div>
                    <div class="mt-1 text-2xl font-bold text-foreground whitespace-nowrap">{{ number_format($totalSubmissions) }}</div>
                </div>
                <div class="mt-2">
                    <div class="text-[11px] text-foreground/60 truncate" title="{{ number_format($submittedCount) }} of {{ number_format($registeredStudentsCount) }} registered &middot; {{ $registeredStudentsCount > 0 ? ($completionPercentage ?? 0) . '%' : '—' }}">
                        {{ number_format($submittedCount) }} of {{ number_format($registeredStudentsCount) }} registered &middot; {{ $registeredStudentsCount > 0 ? ($completionPercentage ?? 0) . '%' : '—' }}
                    </div>
                    <div class="mt-1.5 w-full bg-muted rounded-full h-1.5 overflow-hidden">
                        <div class="bg-primary h-1.5 rounded-full transition-all duration-300" style="width: {{ min(100, max(0, $completionPercentage ?? 0)) }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- 3. Flagged -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-border shadow-xs flex flex-col justify-between">
                <div>
                    <div class="text-xs font-medium text-foreground/60 whitespace-nowrap">Flagged</div>
                    <div class="mt-1 text-2xl font-bold text-amber-600 whitespace-nowrap">{{ number_format($totalFlagged) }}</div>
                </div>
                <div class="text-[11px] text-amber-600/70 mt-2 truncate" title="Quality review or item flags">Quality review or item flags</div>
            </div>

            <!-- 4. Unreviewed flags -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-border shadow-xs flex flex-col justify-between">
                <div>
                    <div class="text-xs font-medium text-foreground/60 whitespace-nowrap">Unreviewed flags</div>
                    <div class="mt-1 text-2xl font-bold {{ $totalUnreviewedFlags > 0 ? 'text-destructive' : 'text-primary' }} whitespace-nowrap">
                        {{ number_format($totalUnreviewedFlags) }}
                    </div>
                </div>
                <div class="text-[11px] text-foreground/50 mt-2 truncate" title="Pending counselor review">Pending counselor review</div>
            </div>

            <!-- 5. DASS21 Severe+ -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-border shadow-xs flex flex-col justify-between">
                <div>
                    <div class="text-xs font-medium text-foreground/60 whitespace-nowrap">DASS21 Severe+</div>
                    <div class="mt-1 text-2xl font-bold {{ $dass21SeverePlus > 0 ? 'text-destructive' : 'text-foreground' }} whitespace-nowrap">
                        {{ number_format($dass21SeverePlus) }}
                    </div>
                </div>
                <div class="text-[11px] text-destructive/70 mt-2 truncate" title="Severe & extremely severe">Severe &amp; extremely severe</div>
            </div>
        </div>

        <!-- DASS21 Breakdown & Quality Summary Bar -->
        <div class="bg-white rounded-2xl border border-border shadow-xs px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <span class="font-semibold text-foreground/80 mr-1">DASS21 Breakdown:</span>
                <span class="px-2 py-0.5 bg-muted text-foreground/80 rounded border border-border/60">Normal: {{ $dass21Stats['Normal'] ?? 0 }}</span>
                <span class="px-2 py-0.5 bg-yellow-50 text-yellow-800 rounded border border-yellow-200">Mild: {{ $dass21Stats['Mild'] ?? 0 }}</span>
                <span class="px-2 py-0.5 bg-orange-50 text-orange-800 rounded border border-orange-200">Moderate: {{ $dass21Stats['Moderate'] ?? 0 }}</span>
                <span class="px-2 py-0.5 bg-destructive/10 text-destructive rounded border border-destructive/20">Severe: {{ $dass21Stats['Severe'] ?? 0 }}</span>
                <span class="px-2 py-0.5 bg-destructive/20 text-destructive rounded font-bold border border-destructive/30">Extreme: {{ $dass21Stats['Extremely Severe'] ?? 0 }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-foreground/60">
                <span class="font-medium">Assessed Submissions: <strong class="text-foreground">{{ number_format($totalAssessed) }}</strong></span>
                <span class="text-border">&bull;</span>
                <span class="text-emerald-700 font-medium">Acceptable Quality: <strong>{{ number_format($totalAcceptable) }}</strong></span>
                <span class="text-border">&bull;</span>
                <span class="text-amber-700 font-medium">Flagged for Review: <strong>{{ number_format($totalFlaggedForReview) }}</strong></span>
            </div>
        </div>

        @php
            $hasMoreFilters = request()->filled('has_flags')
                || (request()->filled('sort') && request('sort') !== 'submitted_at')
                || (request()->filled('direction') && request('direction') !== 'desc');
        @endphp

        <!-- Filters & Search -->
        <div x-data="{ openMore: @js($hasMoreFilters) }" class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-border space-y-4">
            <form method="GET" action="{{ route('staff.inventory.index') }}" class="space-y-4">
                <!-- Single Toolbar Row on Desktop -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <!-- Search -->
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="block text-xs font-medium text-foreground/70 mb-1">Search Student</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-foreground/40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or Email..." class="block w-full pl-9 pr-3 py-2 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <!-- Academic Year -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-foreground/70 mb-1">Academic Year</label>
                        <select name="academic_year" class="block w-full py-2 px-3 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                            @foreach($academicYears as $year)
                                <option value="{{ $year }}" {{ request('academic_year', $academicYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year Level -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-foreground/70 mb-1">Year Level</label>
                        <select name="year_level" class="block w-full py-2 px-3 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                            <option value="">All Levels</option>
                            <option value="1st" {{ request('year_level') == '1st' ? 'selected' : '' }}>1st Year</option>
                            <option value="2nd" {{ request('year_level') == '2nd' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3rd" {{ request('year_level') == '3rd' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4th" {{ request('year_level') == '4th' ? 'selected' : '' }}>4th Year</option>
                        </select>
                    </div>

                    <!-- Quality Status -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-foreground/70 mb-1">Quality Status</label>
                        <select name="quality_classification" class="block w-full py-2 px-3 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                            <option value="">All Statuses</option>
                            <option value="ACCEPTABLE" {{ request('quality_classification') == 'ACCEPTABLE' ? 'selected' : '' }}>Acceptable Only</option>
                            <option value="FOR REVIEW" {{ request('quality_classification') == 'FOR REVIEW' ? 'selected' : '' }}>For Review Only</option>
                        </select>
                    </div>

                    <!-- Toolbar Actions -->
                    <div class="sm:col-span-2 lg:col-span-2 flex items-center justify-end gap-2">
                        <button type="button" @click="openMore = !openMore" 
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border transition-colors"
                                :class="openMore || @js($hasMoreFilters) ? 'bg-primary/10 text-primary border-primary/30' : 'bg-muted/60 hover:bg-muted text-foreground/70 border-border'">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="openMore ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <span>More</span>
                            @if($hasMoreFilters)
                                <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                            @endif
                        </button>
                        <a href="{{ route('staff.inventory.index') }}" class="btn-secondary text-xs px-3 py-2">Clear</a>
                        <button type="submit" class="btn-primary text-xs px-3.5 py-2 whitespace-nowrap">
                            Apply
                        </button>
                    </div>
                </div>

                <!-- Collapsible More Filters -->
                <div x-show="openMore" x-cloak x-collapse class="pt-3 border-t border-border/70">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Flag Status -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/70 mb-1">Flag Status</label>
                            <select name="has_flags" class="block w-full py-2 px-3 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                                <option value="">Any Flag Status</option>
                                <option value="any" {{ request('has_flags') == 'any' ? 'selected' : '' }}>Has Flags</option>
                                <option value="unreviewed" {{ request('has_flags') == 'unreviewed' ? 'selected' : '' }}>Unreviewed Only</option>
                                <option value="none" {{ request('has_flags') == 'none' ? 'selected' : '' }}>No Flags</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/70 mb-1">Sort By</label>
                            <select name="sort" class="block w-full py-2 px-3 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                                <option value="submitted_at" {{ request('sort') == 'submitted_at' ? 'selected' : '' }}>Date Submitted</option>
                                <option value="quality_classification" {{ request('sort') == 'quality_classification' ? 'selected' : '' }}>Quality Status</option>
                                <option value="flag_count" {{ request('sort') == 'flag_count' ? 'selected' : '' }}>Flag Count</option>
                                <option value="dass21_severity" {{ request('sort') == 'dass21_severity' ? 'selected' : '' }}>DASS21 Severity</option>
                                <option value="student_name" {{ request('sort') == 'student_name' ? 'selected' : '' }}>Student Name</option>
                            </select>
                        </div>

                        <!-- Direction -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/70 mb-1">Direction</label>
                            <select name="direction" class="block w-full py-2 px-3 rounded-xl border-border text-sm shadow-xs focus:border-primary focus:ring-primary">
                                <option value="desc" {{ request('direction', 'desc') == 'desc' ? 'selected' : '' }}>Descending / Highest</option>
                                <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Ascending / Lowest</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @php
            $calcRow = function($submission) {
                // Highest DASS21 calculation
                $highestDassSev = 'Normal';
                $sevVal = 1;
                if ($submission->relationLoaded('scores') ? $submission->scores : $submission->scores) {
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
                $hasScores = $submission->scores && $submission->scores->count() > 0;

                // Classification
                $classification = $submission->quality_classification 
                    ?? ($submission->flags_count > 0 ? 'FOR REVIEW' : ($submission->submitted_at ? 'ACCEPTABLE' : null));
                $qa = $submission->qualityAssessment;

                // Quality tags
                $tags = [];
                if ($qa && $qa->hasSpeedFlag()) {
                    $tags[] = ['label' => '⚡ Fast Pace', 'title' => 'Fast Pace: unusually short completion time'];
                }
                if ($qa && $qa->hasStraightLineFlag()) {
                    $tags[] = ['label' => '📏 Repetitive', 'title' => 'Repetitive: invariant response pattern'];
                }
                if ($qa && $qa->hasContradictionFlag()) {
                    $tags[] = ['label' => '🔀 Contradiction', 'title' => 'Contradiction: inconsistent answers detected'];
                }

                // Program and Section
                $prog = $submission->user?->structuredProgram?->code ?? $submission->user?->program;
                $sec = $submission->user?->section;
                $isLongProg = $prog && strlen($prog) > 12;

                return [
                    'highestDassSev' => $highestDassSev,
                    'sevVal' => $sevVal,
                    'dassColor' => $dassColor,
                    'hasScores' => $hasScores,
                    'classification' => $classification,
                    'qa' => $qa,
                    'tags' => $tags,
                    'prog' => $prog,
                    'sec' => $sec,
                    'isLongProg' => $isLongProg,
                ];
            };
        @endphp

        <!-- Submissions Table (Desktop: xl and up) -->
        <div class="hidden xl:block bg-white border border-border rounded-2xl shadow-sm overflow-clip">
            <div class="w-full">
                <table class="table-fixed w-full divide-y divide-border">
                    <colgroup>
                        <col>
                        <col class="w-[98px] 2xl:w-[145px]">
                        <col class="w-[98px] 2xl:w-[145px]">
                        <col class="w-[200px] 2xl:w-[265px]">
                        <col class="w-[138px] 2xl:w-[175px]">
                        <col class="w-[170px] 2xl:w-[218px]">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="sticky top-0 lg:-top-8 z-10 bg-muted px-4 py-3 text-left text-sm font-semibold text-foreground/70 rounded-tl-2xl border-b border-border">Student</th>
                            <th scope="col" class="sticky top-0 lg:-top-8 z-10 bg-muted px-4 py-3 text-left text-sm font-semibold text-foreground/70 border-b border-border">Program</th>
                            <th scope="col" class="sticky top-0 lg:-top-8 z-10 bg-muted px-4 py-3 text-left text-sm font-semibold text-foreground/70 border-b border-border">Submitted</th>
                            <th scope="col" class="sticky top-0 lg:-top-8 z-10 bg-muted px-4 py-3 text-left text-sm font-semibold text-foreground/70 border-b border-border">Response quality</th>
                            <th scope="col" class="sticky top-0 lg:-top-8 z-10 bg-muted px-4 py-3 text-left text-sm font-semibold text-foreground/70 whitespace-nowrap border-b border-border">Flags</th>
                            <th scope="col" class="sticky top-0 lg:-top-8 z-10 bg-muted px-4 py-3 text-left text-sm font-semibold text-foreground/70 whitespace-nowrap rounded-tr-2xl border-b border-border">Highest DASS-21</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-white">
                        @forelse($submissions as $submission)
                            @php
                                $row = $calcRow($submission);
                                $highestDassSev = $row['highestDassSev'];
                                $dassColor = $row['dassColor'];
                                $hasScores = $row['hasScores'];
                                $classification = $row['classification'];
                                $tags = $row['tags'];
                                $prog = $row['prog'];
                                $sec = $row['sec'];
                                $isLongProg = $row['isLongProg'];
                                $showUrl = route('staff.inventory.show', $submission);
                            @endphp
                            <tr x-data 
                                @click="if(!$event.target.closest('a, button, input, select')) window.location='{{ $showUrl }}'"
                                @keydown.enter="if(!$event.target.closest('a, button, input, select')) window.location='{{ $showUrl }}'"
                                tabindex="0"
                                class="group cursor-pointer hover:bg-muted/50 transition-colors focus:outline-none focus:bg-muted/60 focus:ring-2 focus:ring-primary/30">
                                
                                <!-- Student -->
                                <td class="px-4 py-3 align-middle text-sm min-w-[220px]">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 border border-border bg-muted/20">
                                            <img src="{{ $submission->user?->avatar_url }}" alt="Avatar" width="36" height="36" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ $showUrl }}" 
                                               title="{{ $submission->user?->last_name ?? 'Unknown' }}, {{ $submission->user?->first_name ?? 'Student' }}"
                                               class="text-sm font-semibold text-foreground group-hover:text-primary transition-colors block truncate focus:underline">
                                                {{ $submission->user?->last_name ?? 'Unknown' }}, {{ $submission->user?->first_name ?? 'Student' }}
                                            </a>
                                            <div title="{{ $submission->user?->email ?? 'N/A' }}" class="text-xs text-foreground/60 truncate">
                                                {{ $submission->user?->email ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Program -->
                                <td class="px-4 py-3 align-middle text-sm text-foreground/80 font-medium">
                                    @if($isLongProg)
                                        <div class="line-clamp-2 text-xs font-medium text-foreground/80 leading-snug" title="{{ $prog }}">
                                            {{ $prog }}
                                        </div>
                                        @if($sec)
                                            <div class="text-[11px] text-foreground/50 mt-0.5 font-normal">
                                                {{ $sec }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="truncate text-sm font-medium text-foreground/80" title="{{ $prog }}{{ $sec ? ' ' . $sec : '' }}">
                                            {{ $prog ?? 'N/A' }}{{ $sec ? ' ' . $sec : '' }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Submitted -->
                                <td class="px-4 py-3 align-middle text-sm">
                                    @if($submission->submitted_at)
                                        <div class="font-medium text-foreground/90 truncate">{{ $submission->submitted_at->format('M j, Y') }}</div>
                                        <div class="text-xs text-foreground/50 truncate">{{ $submission->submitted_at->format('g:i A') }}</div>
                                    @else
                                        <span class="text-xs font-medium text-foreground/50 italic">Incomplete</span>
                                    @endif
                                </td>

                                <!-- Response quality -->
                                <td class="px-4 py-3 align-middle text-sm">
                                    @if($classification === 'ACCEPTABLE')
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                                Acceptable
                                            </span>
                                        </div>
                                    @elseif($classification === 'FOR REVIEW')
                                        <div class="flex flex-col gap-1 items-start">
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 whitespace-nowrap">
                                                    For Review
                                                </span>
                                            </div>
                                            @if(!empty($tags))
                                                @php
                                                    $visibleTags = array_slice($tags, 0, 2);
                                                    $extraCount = count($tags) - 2;
                                                    $allTagsTitle = implode(', ', array_column($tags, 'label'));
                                                @endphp
                                                <div class="flex items-center gap-1 flex-nowrap" title="{{ $allTagsTitle }}">
                                                    @foreach($visibleTags as $t)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-muted text-foreground/80 border border-border/70 whitespace-nowrap shrink-0" title="{{ $t['title'] }}">
                                                            {{ $t['label'] }}
                                                        </span>
                                                    @endforeach
                                                    @if($extraCount > 0)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-muted text-foreground/70 border border-border/70 whitespace-nowrap shrink-0" title="{{ $allTagsTitle }}">
                                                            +{{ $extraCount }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-foreground/40 text-sm whitespace-nowrap">—</span>
                                    @endif
                                </td>

                                <!-- Flags -->
                                <td class="px-4 py-3 align-middle text-sm whitespace-nowrap">
                                    @if($submission->flags_count > 0)
                                        @if($submission->unreviewed_flags > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-destructive/10 text-destructive border border-destructive/20 whitespace-nowrap"
                                                  title="{{ $submission->flags_count }} Total ({{ $submission->unreviewed_flags }} Unreviewed)">
                                                {{ $submission->unreviewed_flags }} unreviewed
                                                <span class="sr-only">{{ $submission->flags_count }} Total {{ $submission->unreviewed_flags }} Unreviewed</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-foreground/70 border border-border/70 whitespace-nowrap"
                                                  title="{{ $submission->flags_count }} Total (All Reviewed)">
                                                {{ $submission->flags_count }} reviewed
                                                <span class="sr-only">{{ $submission->flags_count }} Total</span>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-foreground/40 text-sm whitespace-nowrap">—</span>
                                    @endif
                                </td>

                                <!-- Highest DASS-21 -->
                                <td class="px-4 py-3 align-middle text-sm whitespace-nowrap pr-4">
                                    <div class="flex items-center justify-between gap-2 w-full">
                                        @if($hasScores)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border whitespace-nowrap {{ $dassColor }}" title="Highest DASS-21: {{ $highestDassSev }}">
                                                {{ $highestDassSev }}
                                            </span>
                                        @else
                                            <span class="text-xs text-foreground/40 font-medium whitespace-nowrap">N/A</span>
                                        @endif
                                        <svg class="w-4 h-4 text-foreground/30 group-hover:text-primary transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-foreground/50">
                                    No submissions match your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination footer for desktop -->
            @if($submissions->total() > 0)
                <div class="p-4 border-t border-border rounded-b-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-xs sm:text-sm text-foreground/60">
                        Showing <span class="font-semibold text-foreground">{{ $submissions->firstItem() }}</span>–<span class="font-semibold text-foreground">{{ $submissions->lastItem() }}</span> of <span class="font-semibold text-foreground">{{ $submissions->total() }}</span> submissions
                    </div>
                    <div>
                        {{ $submissions->links() }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Submissions Cards (Mobile / Tablet: below xl) -->
        <div class="block xl:hidden space-y-3">
            @forelse($submissions as $submission)
                @php
                    $row = $calcRow($submission);
                    $highestDassSev = $row['highestDassSev'];
                    $dassColor = $row['dassColor'];
                    $hasScores = $row['hasScores'];
                    $classification = $row['classification'];
                    $tags = $row['tags'];
                    $prog = $row['prog'];
                    $sec = $row['sec'];
                    $isLongProg = $row['isLongProg'];
                    $showUrl = route('staff.inventory.show', $submission);
                @endphp
                <a href="{{ $showUrl }}" class="block bg-white p-4 rounded-2xl border border-border shadow-xs hover:border-primary/40 hover:shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary/40 space-y-3">
                    <!-- Top: Avatar + Student info + Chevron -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 border border-border bg-muted/20">
                                <img src="{{ $submission->user?->avatar_url }}" alt="Avatar" width="36" height="36" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-foreground truncate">
                                    {{ $submission->user?->last_name ?? 'Unknown' }}, {{ $submission->user?->first_name ?? 'Student' }}
                                </div>
                                <div class="text-xs text-foreground/60 truncate">
                                    {{ $submission->user?->email ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-foreground/40 shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>

                    <!-- Middle: Program/Section & Date -->
                    <div class="flex items-center justify-between text-xs text-foreground/70 pt-2 border-t border-border/50">
                        <div class="font-medium mr-2 {{ $isLongProg ? 'line-clamp-2' : 'truncate' }}" title="{{ $prog }}{{ $sec ? ' ' . $sec : '' }}">
                            {{ $prog ?? 'N/A' }}{{ $sec ? ' ' . $sec : '' }}
                        </div>
                        <div class="shrink-0 text-foreground/60 whitespace-nowrap">
                            @if($submission->submitted_at)
                                {{ $submission->submitted_at->format('M j, Y • g:i A') }}
                            @else
                                <span class="italic">Incomplete</span>
                            @endif
                        </div>
                    </div>

                    <!-- Bottom: Badges Row (Quality, Flags, DASS-21) -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border/50">
                        <!-- Quality Badge -->
                        @if($classification === 'ACCEPTABLE')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                Acceptable
                            </span>
                        @elseif($classification === 'FOR REVIEW')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 whitespace-nowrap">
                                For Review
                            </span>
                            @foreach(array_slice($tags, 0, 2) as $t)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-muted text-foreground/80 border border-border/70 whitespace-nowrap">
                                    {{ $t['label'] }}
                                </span>
                            @endforeach
                        @endif

                        <!-- Flags Pill -->
                        @if($submission->flags_count > 0)
                            @if($submission->unreviewed_flags > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-destructive/10 text-destructive border border-destructive/20 whitespace-nowrap">
                                    {{ $submission->unreviewed_flags }} unreviewed
                                    <span class="sr-only">{{ $submission->flags_count }} Total {{ $submission->unreviewed_flags }} Unreviewed</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-foreground/70 border border-border/70 whitespace-nowrap">
                                    {{ $submission->flags_count }} reviewed
                                    <span class="sr-only">{{ $submission->flags_count }} Total</span>
                                </span>
                            @endif
                        @endif

                        <!-- DASS21 Badge -->
                        @if($hasScores)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border whitespace-nowrap {{ $dassColor }} ml-auto">
                                {{ $highestDassSev }}
                            </span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="bg-white p-8 rounded-2xl border border-border text-center text-sm text-foreground/50">
                    No submissions match your filters.
                </div>
            @endforelse

            <!-- Pagination for mobile -->
            @if($submissions->total() > 0)
                <div class="bg-white p-4 rounded-2xl border border-border space-y-3">
                    <div class="text-xs text-foreground/60 text-center">
                        Showing <span class="font-semibold text-foreground whitespace-nowrap">{{ $submissions->firstItem() }}</span>–<span class="font-semibold text-foreground whitespace-nowrap">{{ $submissions->lastItem() }}</span> of <span class="font-semibold text-foreground whitespace-nowrap">{{ $submissions->total() }}</span> submissions
                    </div>
                    <div>
                        {{ $submissions->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>
</x-staff-layout>
