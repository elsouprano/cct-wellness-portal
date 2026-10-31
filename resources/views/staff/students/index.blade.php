@section('title', 'Student Profiles')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-heading text-2xl font-bold text-foreground">
                    {{ __('Student Profiles') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    View and monitor student individual inventory records.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Search and Filter Bar -->
            <div class="bg-white p-5 shadow-sm sm:rounded-2xl border border-border">
                <form method="GET" action="{{ route('staff.students.index') }}" class="space-y-4">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-foreground/40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, student number, or email..." class="w-full pl-10 pr-4 py-2 border-border focus:border-primary focus:ring-primary rounded-xl shadow-xs text-sm">
                    </div>

                    <!-- Filter Dropdowns Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                        <!-- Program Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-foreground/60 mb-1">Program</label>
                            <select name="program" class="w-full border-border focus:border-primary focus:ring-primary rounded-xl shadow-xs text-sm">
                                <option value="">All Programs</option>
                                @foreach($programs as $prog)
                                    <option value="{{ $prog->id }}" {{ (string)request('program') === (string)$prog->id || request('program') === $prog->name || request('program') === $prog->code ? 'selected' : '' }}>
                                        {{ $prog->code ?? $prog->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Level Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-foreground/60 mb-1">Year Level</label>
                            <select name="year_level" class="w-full border-border focus:border-primary focus:ring-primary rounded-xl shadow-xs text-sm">
                                <option value="">All Year Levels</option>
                                <option value="1st" {{ request('year_level') === '1st' ? 'selected' : '' }}>1st Year</option>
                                <option value="2nd" {{ request('year_level') === '2nd' ? 'selected' : '' }}>2nd Year</option>
                                <option value="3rd" {{ request('year_level') === '3rd' ? 'selected' : '' }}>3rd Year</option>
                                <option value="4th" {{ request('year_level') === '4th' ? 'selected' : '' }}>4th Year</option>
                            </select>
                        </div>

                        <!-- Section Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-foreground/60 mb-1">Section</label>
                            <select name="section" class="w-full border-border focus:border-primary focus:ring-primary rounded-xl shadow-xs text-sm">
                                <option value="">All Sections</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec }}" {{ request('section') === $sec ? 'selected' : '' }}>
                                        Section {{ $sec }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Submission Status Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-foreground/60 mb-1">Submission (A.Y. {{ $academicYear ?? 'Current' }})</label>
                            <select name="submission_status" class="w-full border-border focus:border-primary focus:ring-primary rounded-xl shadow-xs text-sm">
                                <option value="">All Submissions</option>
                                <option value="submitted" {{ request('submission_status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                <option value="pending" {{ request('submission_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>

                        <!-- Account Status Filter -->
                        <div>
                            <label class="block text-xs font-semibold text-foreground/60 mb-1">Account Status</label>
                            <select name="status" class="w-full border-border focus:border-primary focus:ring-primary rounded-xl shadow-xs text-sm">
                                <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active Students</option>
                                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived Students</option>
                                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Accounts</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center justify-between pt-1">
                        <div class="text-xs text-foreground/60">
                            Showing {{ $students->total() }} total student{{ $students->total() === 1 ? '' : 's' }}
                        </div>
                        <div class="flex gap-2">
                            @if(request('search') || request('program') || request('year_level') || request('section') || request('submission_status') || (request('status') && request('status') !== 'active'))
                                <a href="{{ route('staff.students.index') }}" class="px-4 py-2 bg-muted text-foreground/70 font-semibold rounded-xl hover:bg-muted/80 transition-colors text-xs inline-flex items-center">
                                    Reset Filters
                                </a>
                            @endif
                            <button type="submit" class="px-5 py-2 bg-primary text-white font-semibold rounded-xl shadow-xs hover:bg-primary/90 transition-colors text-xs flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="bg-white shadow-sm sm:rounded-2xl border border-border overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-heading text-lg font-bold text-foreground">Student Directory</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border text-left">
                            <thead>
                                <tr class="text-foreground/50 text-xs uppercase tracking-wider bg-muted/20">
                                    <th class="px-4 py-3 font-semibold">Student</th>
                                    <th class="px-4 py-3 font-semibold">Student ID</th>
                                    <th class="px-4 py-3 font-semibold">Program & Section</th>
                                    <th class="px-4 py-3 font-semibold">Submission Status</th>
                                    <th class="px-4 py-3 font-semibold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($students as $student)
                                    @php
                                        $hasSubmitted = $student->inventorySubmissions->isNotEmpty();
                                    @endphp
                                    <tr class="hover:bg-muted/30 transition-colors">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 border border-border bg-white shadow-xs">
                                                    <img src="{{ $student->avatar_url }}" alt="Avatar" width="36" height="36" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-foreground text-sm">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_initial }}</div>
                                                    <div class="text-xs text-foreground/60">{{ $student->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-foreground font-medium text-sm">{{ $student->student_id ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-foreground text-sm font-medium">
                                                {{ $student->structuredProgram ? $student->structuredProgram->code : ($student->program ?? 'N/A') }}
                                                @if($student->section)
                                                    <span class="text-foreground/60 font-normal">({{ $student->section }})</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-foreground/50">Year: {{ $student->year_level ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if($hasSubmitted)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Submitted
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('staff.students.show', $student) }}" class="inline-flex items-center gap-1 text-primary hover:text-primary-dark transition-colors bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg text-xs font-semibold">
                                                View Profile &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-12 text-center text-foreground/50">
                                            <div class="max-w-xs mx-auto">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-foreground/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <p class="font-medium text-foreground text-sm">No students found</p>
                                                <p class="text-xs text-foreground/50 mt-1">Try adjusting your filters or search terms.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-staff-layout>
