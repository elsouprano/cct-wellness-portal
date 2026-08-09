<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Year Level Management') }}
            </h2>
            <div class="space-x-2">
                <a href="{{ route('year-levels.audit') }}" class="btn-secondary text-sm px-4 py-2">View Audit Logs</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-primary/10 border-l-4 border-primary p-4 rounded-r-lg">
                    <p class="text-sm text-primary font-medium">{{ session('success') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-destructive/10 border-l-4 border-destructive p-4 rounded-r-lg">
                    <p class="text-sm text-destructive font-medium">{{ $errors->first() }}</p>
                </div>
            @endif

            <!-- Bulk Promote Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                <h3 class="text-lg font-heading font-semibold text-foreground mb-2">Bulk Promote Students</h3>
                <p class="text-sm text-foreground/70 mb-6">Advance all active students to their next year level (e.g., 1st to 2nd). Students currently in their 4th year will be ignored.</p>
                
                <form action="{{ route('year-levels.bulk-promote') }}" method="POST" onsubmit="return confirm('WARNING: This will promote all 1st, 2nd, and 3rd year students to the next level. This action will be logged. Are you absolutely sure?');">
                    @csrf
                    <button type="submit" class="btn-primary">Execute Bulk Promotion</button>
                </form>
            </div>

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
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Current Level</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-foreground/70 uppercase">Override</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border">
                            @forelse($students as $student)
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-foreground">
                                        {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_initial }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/80">
                                        {{ $student->program }} - {{ $student->section }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                        {{ $student->year_level ?? 'None' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($student->year_level_confirmed)
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">Confirmed</span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-secondary/10 text-secondary border border-secondary/20">Unconfirmed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <form method="POST" action="{{ route('year-levels.override', $student) }}" class="flex items-center justify-end gap-2">
                                            @csrf
                                            <select name="year_level" class="input-field py-1.5 text-xs" required>
                                                <option value="1st" {{ $student->year_level === '1st' ? 'selected' : '' }}>1st</option>
                                                <option value="2nd" {{ $student->year_level === '2nd' ? 'selected' : '' }}>2nd</option>
                                                <option value="3rd" {{ $student->year_level === '3rd' ? 'selected' : '' }}>3rd</option>
                                                <option value="4th" {{ $student->year_level === '4th' ? 'selected' : '' }}>4th</option>
                                            </select>
                                            <button type="submit" class="btn-secondary text-xs px-3 py-1.5">Set</button>
                                        </form>
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
</x-app-layout>
