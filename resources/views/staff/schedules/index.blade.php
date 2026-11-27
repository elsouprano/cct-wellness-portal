@section('title', 'Assessment Schedules')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Assessment Schedules') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    Configure and monitor open assessment windows for students.
                </p>
            </div>
            @if(auth()->user()->isAdmin() || (auth()->user()->isCounselor() && auth()->user()->department_id))
                <a href="{{ route('schedules.create') }}" class="btn-primary w-full sm:w-auto text-center">Add Schedule</a>
            @endif
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl sm:rounded-3xl border border-border">
                <div class="p-4 sm:p-6 text-foreground border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-heading font-semibold text-foreground">Manage Schedules</h3>
                        <p class="text-sm text-foreground/70 mt-1">Schedules automatically archive when their closing time passes.</p>
                    </div>

                    <!-- Segmented Active / Archived Filter Tabs -->
                    <div class="grid grid-cols-2 sm:inline-flex items-center p-1 bg-muted/60 rounded-xl border border-border">
                        <a href="{{ route('schedules.index') }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 text-xs font-semibold rounded-lg transition-all {{ !$viewArchived ? 'bg-white text-foreground shadow-2xs font-bold' : 'text-foreground/60 hover:text-foreground' }}">
                            Active ({{ $activeCount }})
                        </a>
                        <a href="{{ route('schedules.index', ['archived' => 1]) }}"
                           class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 text-xs font-semibold rounded-lg transition-all {{ $viewArchived ? 'bg-white text-foreground shadow-2xs font-bold' : 'text-foreground/60 hover:text-foreground' }}">
                            Archived ({{ $archivedCount }})
                        </a>
                    </div>
                </div>

                @if($schedules->isEmpty())
                    <div class="p-8 sm:p-12 text-center text-foreground/50">
                        <svg class="mx-auto h-12 w-12 text-foreground/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        @if($viewArchived)
                            No archived schedules found.
                        @else
                            No active schedules found. Students will not be able to access the assessment.
                        @endif
                    </div>
                @else
                    <!-- Mobile Schedule Cards (< md) -->
                    <div class="md:hidden divide-y divide-border">
                        @foreach($schedules as $schedule)
                            @php $status = $schedule->computed_status; @endphp
                            <div class="p-4 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-foreground/55">{{ $schedule->academicYear?->label }}</p>
                                        <p class="font-semibold text-sm text-foreground mt-0.5 break-words">
                                            {{ $schedule->year_level }} Year
                                            @if($schedule->structuredProgram)
                                                &middot; {{ $schedule->structuredProgram->code ?: $schedule->structuredProgram->name }}
                                            @elseif($schedule->program)
                                                &middot; {{ $schedule->program }}
                                            @else
                                                <span class="text-foreground/55 font-normal">(All Programs)</span>
                                            @endif
                                        </p>
                                    </div>
                                    <span class="shrink-0">
                                        @if($status === 'Upcoming')
                                            <span class="inline-flex items-center text-xs font-semibold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">
                                                Upcoming
                                            </span>
                                        @elseif($status === 'Open')
                                            <span class="inline-flex items-center text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                                Open
                                            </span>
                                        @elseif($status === 'Closing soon')
                                            <span class="inline-flex items-center text-xs font-semibold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                                                Closing soon
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-xs font-semibold text-foreground/65 bg-muted px-2.5 py-1 rounded-full border border-border">
                                                Archived
                                            </span>
                                        @endif
                                    </span>
                                </div>

                                <dl class="grid grid-cols-1 gap-1.5 text-xs bg-muted/30 rounded-xl p-3 border border-border/70">
                                    <div class="flex flex-wrap items-center justify-between gap-1">
                                        <dt class="text-foreground/60 font-medium">Opens</dt>
                                        <dd class="text-foreground font-semibold">{{ $schedule->open_at?->format('M j, Y · g:i A') }}</dd>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-1">
                                        <dt class="text-foreground/60 font-medium">Closes</dt>
                                        <dd class="text-foreground font-semibold">{{ $schedule->close_at?->format('M j, Y · g:i A') }}</dd>
                                    </div>
                                </dl>

                                <div class="flex items-center justify-end">
                                    @if($schedule->isArchived())
                                        <span class="text-xs text-foreground/50 italic">
                                            Closed on {{ $schedule->close_at?->format('M j, Y · g:i A') }}
                                        </span>
                                    @elseif(auth()->user()->canManageSchedule($schedule))
                                        <a href="{{ route('schedules.edit', $schedule) }}" class="btn-secondary text-xs min-h-[44px] px-4 py-2 rounded-xl">Edit Schedule</a>
                                    @else
                                        <span class="text-xs text-foreground/50 italic">Read-only (All Programs)</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desktop Table (md+) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-5 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Acad Year</th>
                                    <th class="px-5 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Level &amp; Program</th>
                                    <th class="px-5 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Opens At</th>
                                    <th class="px-5 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Closes At</th>
                                    <th class="px-5 py-4 text-right text-xs font-semibold text-foreground/70 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-border">
                                @foreach($schedules as $schedule)
                                    @php $status = $schedule->computed_status; @endphp
                                    <tr class="hover:bg-muted/30 transition-colors">
                                        <td class="px-5 py-4 whitespace-nowrap text-sm font-semibold text-foreground">
                                            {{ $schedule->academicYear?->label }}
                                        </td>
                                        <td class="px-5 py-4 text-sm text-foreground/80">
                                            <span class="font-medium text-foreground">{{ $schedule->year_level }}</span>
                                            @if($schedule->structuredProgram)
                                                <span class="text-foreground/70">({{ $schedule->structuredProgram->name }})</span>
                                            @elseif($schedule->program)
                                                <span class="text-foreground/70">({{ $schedule->program }})</span>
                                            @else
                                                <span class="text-foreground/50 italic">(All Programs)</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm">
                                            @if($status === 'Upcoming')
                                                <span class="inline-flex items-center text-xs font-medium text-blue-700">
                                                    <span class="w-2 h-2 rounded-full bg-blue-500 inline-block mr-1.5"></span>
                                                    Upcoming
                                                </span>
                                            @elseif($status === 'Open')
                                                <span class="inline-flex items-center text-xs font-medium text-emerald-700">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5"></span>
                                                    Open
                                                </span>
                                            @elseif($status === 'Closing soon')
                                                <span class="inline-flex items-center text-xs font-medium text-amber-700">
                                                    <span class="w-2 h-2 rounded-full bg-amber-500 inline-block mr-1.5"></span>
                                                    Closing soon
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-xs font-medium text-foreground/60">
                                                    <span class="w-2 h-2 rounded-full bg-gray-400 inline-block mr-1.5"></span>
                                                    Archived
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-foreground/70">
                                            {{ $schedule->open_at?->format('M j, Y · g:i A') }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-foreground/70">
                                            {{ $schedule->close_at?->format('M j, Y · g:i A') }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if($schedule->isArchived())
                                                <span class="text-xs text-foreground/50 italic">
                                                    Closed on {{ $schedule->close_at?->format('M j, Y · g:i A') }}
                                                </span>
                                            @else
                                                @if(auth()->user()->canManageSchedule($schedule))
                                                    <a href="{{ route('schedules.edit', $schedule) }}" class="inline-flex items-center min-h-[44px] px-2 text-primary hover:text-primary/80 font-semibold transition-colors">Edit</a>
                                                @else
                                                    <span class="text-xs text-foreground/50 italic">Read-only (All Programs)</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-border bg-muted/20">
                        <x-pagination :paginator="$schedules" label="Assessment schedules pagination" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-staff-layout>
