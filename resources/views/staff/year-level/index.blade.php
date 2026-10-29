@section('title', 'Year Level Management')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Year Level Management') }}
            </h2>
            <div class="space-x-2">
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.promotions.index') }}" class="btn-primary text-sm px-4 py-2 inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        Promote via CSV
                    </a>
                @endif
                <a href="{{ route('year-levels.audit') }}" class="btn-secondary text-sm px-4 py-2">View Audit Logs</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="bg-destructive/10 border-l-4 border-destructive p-4 rounded-r-lg">
                    <p class="text-sm text-destructive font-medium">{{ $errors->first() }}</p>
                </div>
            @endif

            <!-- Individual Override Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg border-b border-border bg-muted/30 flex justify-between items-center flex-wrap gap-4">
                    <h3 class="text-lg font-heading font-semibold text-foreground">Student Directory</h3>
                    
                    <form method="GET" action="{{ route('year-levels.index') }}" class="flex gap-2">
                        <select name="year_level" class="input-field py-1" onchange="this.form.submit()">
                            <option value="">All Year Levels</option>
                            <option value="1st" {{ request('year_level') === '1st' ? 'selected' : '' }}>1st Year</option>
                            <option value="2nd" {{ request('year_level') === '2nd' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3rd" {{ request('year_level') === '3rd' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4th" {{ request('year_level') === '4th' ? 'selected' : '' }}>4th Year</option>
                            <option value="unconfirmed" {{ request('year_level') === 'unconfirmed' ? 'selected' : '' }}>Unconfirmed Only</option>
                        </select>
                        <input type="text" name="search" placeholder="Search name or section..." value="{{ request('search') }}" class="input-field py-1">
                        <button type="submit" class="btn-secondary py-1 px-3">Filter</button>
                    </form>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Student Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Program/Section</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Year Level</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-foreground/70 uppercase">Override</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border">
                            @forelse($students as $student)
                                <tr class="hover:bg-muted/30 transition-colors"
                                    x-data="{
                                        currentLevel: '{{ $student->year_level ?? 'None' }}',
                                        selectedLevel: '{{ $student->year_level ?? '1st' }}',
                                        section: '{{ $student->section }}',
                                        sectionMatches: {{ $student->sectionMatchesYearLevel() ? 'true' : 'false' }},
                                        saving: false,
                                        errorMessage: '',
                                        async submitOverride(e) {
                                            this.saving = true;
                                            this.errorMessage = '';
                                            try {
                                                const form = e.target;
                                                const metaToken = document.querySelector('meta[name=\'csrf-token\']');
                                                const token = metaToken ? metaToken.getAttribute('content') : '{{ csrf_token() }}';
                                                const res = await fetch(form.action, {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'Accept': 'application/json',
                                                        'X-CSRF-TOKEN': token
                                                    },
                                                    body: JSON.stringify({ year_level: this.selectedLevel })
                                                });
                                                const data = await res.json();
                                                if (!res.ok) {
                                                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to update year level.'));
                                                }
                                                this.currentLevel = data.year_level;
                                                this.selectedLevel = data.year_level;
                                                this.section = data.section;
                                                this.sectionMatches = data.section_matches;
                                                if (window.dispatchEvent) {
                                                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: data.warning ? 'warning' : 'success', message: data.message } }));
                                                }
                                            } catch (err) {
                                                this.errorMessage = err.message || 'Failed to update year level.';
                                                if (window.dispatchEvent) {
                                                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: this.errorMessage } }));
                                                }
                                            } finally {
                                                this.saving = false;
                                            }
                                        }
                                    }">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-foreground">
                                        {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_initial }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/80">
                                        <div>{{ $student->program }} - <span x-text="section">{{ $student->section }}</span></div>
                                        <div x-show="!sectionMatches" style="{{ $student->sectionMatchesYearLevel() ? 'display: none;' : '' }}" class="mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                                Section doesn't match year level
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground font-medium">
                                        <span x-text="currentLevel">{{ $student->year_level ?? 'None' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($student->year_level_confirmed)
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">Confirmed</span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-secondary/10 text-secondary border border-secondary/20">Unconfirmed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <form method="POST" action="{{ route('year-levels.override', $student) }}" 
                                              @submit.prevent="submitOverride($event)" 
                                              class="flex items-center justify-end gap-2">
                                            @csrf
                                            <select name="year_level" x-model="selectedLevel" class="input-field py-1.5 text-xs" required :disabled="saving">
                                                <option value="1st">1st</option>
                                                <option value="2nd">2nd</option>
                                                <option value="3rd">3rd</option>
                                                <option value="4th">4th</option>
                                            </select>
                                            <button type="submit" :disabled="saving" class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                                <svg x-show="saving" style="display: none;" class="animate-spin h-3 w-3 text-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span x-text="saving ? 'Saving...' : 'Set'">Set</span>
                                            </button>
                                        </form>
                                        <div x-show="errorMessage" x-text="errorMessage" class="text-destructive text-[11px] mt-1 text-right" style="display: none;"></div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-foreground/50">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-border">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
