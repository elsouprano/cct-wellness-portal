<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Counselor Review Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Top Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Total Submissions -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-sm font-medium text-foreground/70 uppercase tracking-wide">Total Submissions</div>
                    <div class="mt-2 text-3xl font-bold text-foreground">{{ number_format($totalSubmissions) }}</div>
                </div>
                <!-- Flagged Submissions -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-sm font-medium text-foreground/70 uppercase tracking-wide">Flagged</div>
                    <div class="mt-2 text-3xl font-bold text-orange-600">{{ number_format($totalFlagged) }}</div>
                </div>
                <!-- Unreviewed Flags -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-sm font-medium text-foreground/70 uppercase tracking-wide">Unreviewed Flags</div>
                    <div class="mt-2 text-3xl font-bold {{ $totalUnreviewedFlags > 0 ? 'text-destructive' : 'text-primary' }}">
                        {{ number_format($totalUnreviewedFlags) }}
                    </div>
                </div>
                <!-- DASS21 Severe+ -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-border">
                    <div class="text-sm font-medium text-foreground/70 uppercase tracking-wide">DASS21 Severe+</div>
                    <div class="mt-2 text-3xl font-bold text-destructive">
                        {{ number_format(($dass21Stats['Severe'] ?? 0) + ($dass21Stats['Extremely Severe'] ?? 0)) }}
                    </div>
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

                        <!-- Min Severity -->
                        <div>
                            <label class="block text-xs font-medium text-foreground/80">Min DASS21 Severity</label>
                            <select name="min_severity" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                <option value="">Any</option>
                                <option value="Normal" {{ request('min_severity') == 'Normal' ? 'selected' : '' }}>Normal+</option>
                                <option value="Mild" {{ request('min_severity') == 'Mild' ? 'selected' : '' }}>Mild+</option>
                                <option value="Moderate" {{ request('min_severity') == 'Moderate' ? 'selected' : '' }}>Moderate+</option>
                                <option value="Severe" {{ request('min_severity') == 'Severe' ? 'selected' : '' }}>Severe+</option>
                                <option value="Extremely Severe" {{ request('min_severity') == 'Extremely Severe' ? 'selected' : '' }}>Extremely Severe</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-end justify-between">
                        <div class="flex space-x-4">
                            <div>
                                <label class="block text-xs font-medium text-foreground/80">Sort By</label>
                                <select name="sort" class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                                    <option value="submitted_at" {{ request('sort') == 'submitted_at' ? 'selected' : '' }}>Date Submitted</option>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Flags</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-foreground/70 uppercase tracking-wider">Highest DASS21</th>
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
                                            if ($sc->category_name === 'DASS21' && $sc->severity_label) {
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
                                @endphp
                                <tr onclick="window.location='{{ route('staff.inventory.show', $submission) }}'" class="cursor-pointer hover:bg-muted/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border">
                                                <img src="{{ $submission->user->avatar_url }}" alt="Avatar" class="w-full h-full object-cover" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-foreground">{{ $submission->user->last_name }}, {{ $submission->user->first_name }}</div>
                                                <div class="text-xs text-foreground/60">{{ $submission->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        {{ $submission->user->program }} / {{ $submission->user->section }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        {{ $submission->user->year_level }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        {{ $submission->submitted_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($submission->flags_count > 0)
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->unreviewed_flags > 0 ? 'bg-destructive/10 text-destructive' : 'bg-muted text-foreground/80' }}">
                                                    {{ $submission->flags_count }} Total
                                                </span>
                                                @if($submission->unreviewed_flags > 0)
                                                    <span class="text-xs font-bold text-destructive">{{ $submission->unreviewed_flags }} Unreviewed</span>
                                                @endif
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-foreground/50">
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
