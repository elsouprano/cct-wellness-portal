@section('title', 'Student Profiles')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-heading text-xl lg:text-2xl font-bold text-foreground">
                    {{ __('Student Profiles') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    View and monitor Student Individual Inventory records.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col lg:h-[calc(100dvh-13.5rem)] lg:min-h-[420px]"
         x-data="{ filtersOpen: {{ (request('program') || request('year_level') || request('section') || request('submission_status') || (request('status') && request('status') !== 'active')) ? 'true' : 'false' }} }">
        @php
            $activeFilterCount = collect([
                request('program'),
                request('year_level'),
                request('section'),
                request('submission_status'),
                (request('status') && request('status') !== 'active') ? request('status') : null,
            ])->filter()->count();
            $isFiltered = request('search') || $activeFilterCount > 0;
        @endphp

        <!-- 2-Row Responsive Toolbar -->
        <div class="bg-white p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl border border-border shadow-xs shrink-0 mb-2.5 sm:mb-3.5">
            <form id="filterForm" x-ref="filterForm" method="GET" action="{{ route('staff.students.index') }}" class="space-y-2 sm:space-y-2.5">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <!-- Row 1: Search + Mobile Filters Toggle + Desktop Account Status + Clear -->
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                    <!-- Search Input with 300ms Debounce -->
                    <div class="relative flex-1 min-w-0">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-foreground/40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               @input.debounce.300ms="$refs.filterForm.submit()"
                               placeholder="Search name or ID..." 
                               class="w-full h-9 sm:h-10 pl-9 pr-3 border-border focus:border-primary focus:ring-primary rounded-xl text-xs sm:text-sm placeholder:text-foreground/40 shadow-2xs">
                    </div>

                    <!-- Mobile Filters Drawer Toggle (< 640px) -->
                    <button type="button"
                            @click="filtersOpen = !filtersOpen"
                            :aria-expanded="filtersOpen.toString()"
                            class="sm:hidden inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-border bg-white text-xs font-semibold text-foreground/80 hover:bg-muted/40 transition-colors shrink-0 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-foreground/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Filters</span>
                        @if($activeFilterCount > 0)
                            <span class="inline-flex items-center justify-center min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-primary text-white text-[10px] font-bold leading-none tabular-nums">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                        <svg class="w-3.5 h-3.5 text-foreground/50 transition-transform" :class="filtersOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Account Status Filter (inline on sm+, inside expandable tray on mobile) -->
                    <div :class="filtersOpen ? 'block' : 'hidden sm:block'" class="w-full sm:w-[190px] shrink-0 relative">
                        <select name="status" @change="$refs.filterForm.submit()" class="w-full h-9 sm:h-10 appearance-none pl-2.5 sm:pl-3 pr-7 sm:pr-8 border-border focus:border-primary focus:ring-primary rounded-xl text-xs sm:text-sm shadow-2xs text-foreground/80 bg-white font-medium">
                            <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active students</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived students</option>
                            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All accounts</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-2.5 text-foreground/40">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    @if($isFiltered)
                        <a href="{{ route('staff.students.index', request('per_page') ? ['per_page' => request('per_page')] : []) }}" 
                           class="inline-flex items-center gap-1 text-xs font-semibold text-foreground/60 hover:text-destructive px-2 py-1.5 transition-colors shrink-0 rounded-lg hover:bg-destructive/10"
                           title="Clear all filters">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear
                        </a>
                    @endif
                </div>

                <!-- Row 2: Compact 2-Column Mobile / 4-Column Desktop Select Grid -->
                <div :class="filtersOpen ? 'grid' : 'hidden sm:grid'" class="grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5 pt-2 border-t border-border/40">
                    <!-- Program Filter -->
                    <div class="relative">
                        <select name="program" @change="$refs.filterForm.submit()" class="w-full h-9 sm:h-10 appearance-none pl-2.5 sm:pl-3 pr-7 sm:pr-8 border-border focus:border-primary focus:ring-primary rounded-xl text-xs sm:text-sm shadow-2xs text-foreground/80 bg-white font-medium">
                            <option value="">All programs</option>
                            @foreach($programs as $prog)
                                <option value="{{ $prog->id }}" {{ (string)request('program') === (string)$prog->id || request('program') === $prog->name || request('program') === $prog->code ? 'selected' : '' }}>
                                    {{ $prog->code ?? $prog->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-2.5 text-foreground/40">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Year Level Filter -->
                    <div class="relative">
                        <select name="year_level" @change="$refs.filterForm.submit()" class="w-full h-9 sm:h-10 appearance-none pl-2.5 sm:pl-3 pr-7 sm:pr-8 border-border focus:border-primary focus:ring-primary rounded-xl text-xs sm:text-sm shadow-2xs text-foreground/80 bg-white font-medium">
                            <option value="">All year levels</option>
                            <option value="1st" {{ request('year_level') === '1st' ? 'selected' : '' }}>1st Year</option>
                            <option value="2nd" {{ request('year_level') === '2nd' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3rd" {{ request('year_level') === '3rd' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4th" {{ request('year_level') === '4th' ? 'selected' : '' }}>4th Year</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-2.5 text-foreground/40">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Section Filter -->
                    <div class="relative">
                        <select name="section" @change="$refs.filterForm.submit()" class="w-full h-9 sm:h-10 appearance-none pl-2.5 sm:pl-3 pr-7 sm:pr-8 border-border focus:border-primary focus:ring-primary rounded-xl text-xs sm:text-sm shadow-2xs text-foreground/80 bg-white font-medium">
                            <option value="">All sections</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec }}" {{ request('section') === $sec ? 'selected' : '' }}>
                                    Section {{ $sec }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-2.5 text-foreground/40">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Submission Status Filter -->
                    <div class="relative">
                        <select name="submission_status" @change="$refs.filterForm.submit()" class="w-full h-9 sm:h-10 appearance-none pl-2.5 sm:pl-3 pr-7 sm:pr-8 border-border focus:border-primary focus:ring-primary rounded-xl text-xs sm:text-sm shadow-2xs text-foreground/80 bg-white font-medium">
                            <option value="">All submissions</option>
                            <option value="submitted" {{ request('submission_status') === 'submitted' ? 'selected' : '' }}>Submitted (A.Y. {{ $academicYear ?? 'Current' }})</option>
                            <option value="pending" {{ request('submission_status') === 'pending' ? 'selected' : '' }}>Pending (A.Y. {{ $academicYear ?? 'Current' }})</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-2.5 text-foreground/40">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Card (Flex-1, Pinned Header and Footer, Internal Scroll Body) -->
        <x-data-table
            caption="Student profiles directory"
            :stack-mobile="true"
            :compact-cards="true"
            class="flex-1 min-h-0 flex flex-col"
        >
            <thead class="sticky top-0 bg-surface-subtle z-10">
                <tr class="text-muted-foreground text-xs font-semibold uppercase tracking-wider">
                    <th scope="col" class="px-3 lg:px-5 py-3">Student</th>
                    <th scope="col" class="px-3 lg:px-5 py-3">Student ID</th>
                    <th scope="col" class="px-3 lg:px-5 py-3">Program &amp; section</th>
                    <th scope="col" class="px-3 lg:px-5 py-3">Submission status</th>
                    <th scope="col" class="px-3 lg:px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border bg-surface">
                        @forelse($students as $student)
                            @php
                                $hasSubmitted = $student->inventorySubmissions->isNotEmpty();
                                $progCode = $student->structuredProgram ? $student->structuredProgram->code : ($student->program ?? 'N/A');
                                $profileUrl = route('staff.students.show', array_merge(['student' => $student->id], $filtersWhitelist));
                            @endphp
                            <tr class="hover:bg-muted/40 transition-colors cursor-pointer group lg:h-[56px]" 
                                @click="if (!$event.target.closest('a, button')) { window.location = @js($profileUrl); }">
                                <td data-label="Student" class="px-3 lg:px-5 py-2.5 lg:whitespace-nowrap">
                                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full overflow-hidden shrink-0 border border-border bg-white shadow-2xs">
                                            <img src="{{ $student->avatar_url }}" alt="Avatar" width="36" height="36" loading="lazy" decoding="async" class="w-full h-full object-cover" />
                                        </div>
                                        <a href="{{ $profileUrl }}" class="font-semibold text-foreground group-hover:text-primary transition-colors text-sm sm:text-[14.5px] min-w-0 break-words leading-snug">
                                            {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_initial }}
                                        </a>
                                    </div>
                                </td>
                                <td data-label="Student ID" class="px-3 lg:px-5 py-2.5 lg:whitespace-nowrap">
                                    <span class="text-[14px] font-normal text-foreground/80 tabular-nums">{{ $student->student_id ?? 'N/A' }}</span>
                                </td>
                                <td data-label="Program & section" class="px-3 lg:px-5 py-2.5 lg:whitespace-nowrap">
                                    <span class="text-[14px] font-medium text-foreground/85">
                                        @if($student->section)
                                            {{ $progCode }} {{ $student->section }}
                                        @else
                                            {{ $progCode }} <span class="text-foreground/40 font-normal">—</span>
                                        @endif
                                    </span>
                                </td>
                                <td data-label="Submission status" class="px-3 lg:px-5 py-2.5 lg:whitespace-nowrap">
                                    @if($hasSubmitted)
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-success-foreground">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Submitted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-warning-foreground">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Action" class="px-3 lg:px-5 py-2.5 lg:whitespace-nowrap text-right">
                                    <a href="{{ $profileUrl }}" class="btn-secondary text-xs py-1.5 px-3.5 rounded-xl inline-flex items-center gap-1">
                                        <span>View profile</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center text-foreground/50">
                                    <div class="max-w-xs mx-auto">
                                        <div class="w-12 h-12 rounded-2xl bg-muted/60 text-foreground/40 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <p class="font-semibold text-foreground text-sm">No students found</p>
                                        <p class="text-xs text-foreground/50 mt-1">Try adjusting your filters or search terms.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
            <x-slot name="footer">
                <x-pagination
                    :paginator="$students"
                    :show-limit="true"
                    limit-param="per_page"
                    :limits="[10, 25, 50]"
                    item-label="students"
                    label="Student profiles pagination"
                />
            </x-slot>
        </x-data-table>
    </div>
</x-staff-layout>
