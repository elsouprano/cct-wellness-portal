<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-foreground">Analytics Dashboard</h2>
                <p class="text-foreground/60 text-sm mt-1">Population-level insights across all submitted inventories.</p>
            </div>
            
            <a href="{{ route('analytics.export', request()->query()) }}" class="btn-primary flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV Report
            </a>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-border p-6 shadow-sm mb-8">
        <form method="GET" action="{{ route('analytics.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
            <!-- Academic Year -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wider mb-1.5">Academic Year</label>
                <select name="academic_year" class="input-field py-2 text-sm w-full">
                    <option value="">All Years</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->label }}" {{ $filters['academic_year'] === $ay->label ? 'selected' : '' }}>{{ $ay->label }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Year Level -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wider mb-1.5">Year Level</label>
                <select name="year_level" class="input-field py-2 text-sm w-full">
                    <option value="">All Levels</option>
                    <option value="1st" {{ $filters['year_level'] == '1st' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd" {{ $filters['year_level'] == '2nd' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd" {{ $filters['year_level'] == '3rd' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th" {{ $filters['year_level'] == '4th' ? 'selected' : '' }}>4th Year</option>
                </select>
            </div>

            <!-- Department -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wider mb-1.5">Department</label>
                <select name="department_id" class="input-field py-2 text-sm w-full">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $filters['department_id'] == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Program -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wider mb-1.5">Program</label>
                <select name="program_id" class="input-field py-2 text-sm w-full">
                    <option value="">All Programs</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" {{ $filters['program_id'] == $prog->id ? 'selected' : '' }}>{{ $prog->code ?? $prog->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wider mb-1.5">Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-field py-2 text-sm w-full">
            </div>

            <!-- Date To / Submit -->
            <div class="col-span-1 md:col-span-1 lg:col-span-2 flex flex-col">
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wider mb-1.5">Date To</label>
                <div class="flex gap-2">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-field py-2 text-sm w-full">
                    <button type="submit" class="btn-primary py-2 px-6 shadow-none whitespace-nowrap">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Chart 1: DASS-21 Distribution -->
        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-bold text-foreground mb-4">DASS-21 Severity Distribution</h3>
            <div class="relative h-72">
                <canvas id="dass21Chart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Completion Trend -->
        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-bold text-foreground mb-4">Submission Trends</h3>
            <div class="relative h-72">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Chart 3: Learning Styles -->
        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-bold text-foreground mb-4">Learning Style Distribution</h3>
            <div class="relative h-72">
                <canvas id="learningStyleChart"></canvas>
            </div>
        </div>

        <!-- Chart 4: Flags -->
        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm">
            <h3 class="text-lg font-bold text-foreground mb-4">Flag Rate Breakdown</h3>
            <div class="relative h-72">
                <canvas id="flagChart"></canvas>
            </div>
        </div>
        
        <!-- Chart 5: Comparison by Program/Section -->
        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-bold text-foreground mb-4">Average DASS-21 Scores by Group</h3>
            <div class="relative h-80">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js and Initialization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', function() {
            // Shared colors matching MASTER.md sage/earthy palette
            const palette = {
                primary: '#4c6b5d',
                secondary: '#8e9f85',
                accent: '#d4a373',
                danger: '#c87979',
                warning: '#e2b36b',
                muted: '#e9ece7',
                dark: '#2c3e35'
            };
            
            const severityColors = {
                'Normal': palette.secondary,
                'Mild': palette.warning,
                'Moderate': palette.accent,
                'Severe': '#b55a5a', // darker danger
                'Extremely Severe': palette.danger
            };

            // 1. DASS-21 Severity Distribution (Grouped Bar Chart)
            const dass21Raw = @json($dass21Data);
            const subscales = [...new Set(dass21Raw.map(d => d.subscale_name))];
            const severities = ['Normal', 'Mild', 'Moderate', 'Severe', 'Extremely Severe'];
            
            const dass21Datasets = severities.map(severity => {
                return {
                    label: severity,
                    backgroundColor: severityColors[severity],
                    data: subscales.map(subscale => {
                        const match = dass21Raw.find(d => d.subscale_name === subscale && d.severity_label === severity);
                        return match ? match.count : 0;
                    })
                };
            });

            new Chart(document.getElementById('dass21Chart'), {
                type: 'bar',
                data: {
                    labels: subscales,
                    datasets: dass21Datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Inter' } } } }
                }
            });

            // 2. Trend Chart (Line Chart)
            const trendRaw = @json($trendData);
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: trendRaw.map(d => d.date),
                    datasets: [{
                        label: 'Submissions',
                        data: trendRaw.map(d => d.count),
                        borderColor: palette.primary,
                        backgroundColor: palette.primary + '33', // 20% opacity
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });

            // 3. Learning Style (Doughnut Chart)
            const lsRaw = @json($learningStyleData);
            new Chart(document.getElementById('learningStyleChart'), {
                type: 'doughnut',
                data: {
                    labels: lsRaw.map(d => d.subscale_name),
                    datasets: [{
                        data: lsRaw.map(d => d.total_score),
                        backgroundColor: [palette.primary, palette.secondary, palette.accent, palette.warning]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { font: { family: 'Inter' } } } }
                }
            });

            // 4. Flags (Stacked Bar Chart)
            const flagRaw = @json($flagData);
            const flagTypes = [...new Set(flagRaw.map(d => d.flag_type))];
            
            const reviewedData = flagTypes.map(type => {
                const match = flagRaw.find(d => d.flag_type === type && d.is_reviewed == 1);
                return match ? match.count : 0;
            });
            const unreviewedData = flagTypes.map(type => {
                const match = flagRaw.find(d => d.flag_type === type && d.is_reviewed == 0);
                return match ? match.count : 0;
            });

            new Chart(document.getElementById('flagChart'), {
                type: 'bar',
                data: {
                    labels: flagTypes.map(t => t.replace('_', ' ').toUpperCase()),
                    datasets: [
                        { label: 'Unreviewed', backgroundColor: palette.danger, data: unreviewedData },
                        { label: 'Reviewed', backgroundColor: palette.secondary, data: reviewedData }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                    },
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // 5. Comparison by Program/Section
            const compRaw = @json($comparisonData);
            const groups = [...new Set(compRaw.map(d => d.group_name))];
            // subscales is already defined above from DASS-21
            
            const compDatasets = subscales.map((subscale, index) => {
                const colors = [palette.primary, palette.accent, palette.secondary];
                return {
                    label: subscale,
                    backgroundColor: colors[index % colors.length],
                    data: groups.map(g => {
                        const match = compRaw.find(d => d.group_name === g && d.subscale_name === subscale);
                        return match ? parseFloat(match.avg_score).toFixed(1) : 0;
                    })
                };
            });

            new Chart(document.getElementById('comparisonChart'), {
                type: 'bar',
                data: {
                    labels: groups.length > 0 ? groups : ['No Data'],
                    datasets: compRaw.length > 0 ? compDatasets : []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
</x-staff-layout>
