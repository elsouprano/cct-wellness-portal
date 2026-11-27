@section('title', 'Year Level Audit Logs')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Year Level Audit Logs') }}
            </h2>
            <a href="{{ route('year-levels.index') }}" class="btn-secondary text-sm px-4 py-2 self-start sm:self-auto">&larr; Back to Management</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-4 sm:p-lg border-b border-border bg-muted/30 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                    <h3 class="text-lg font-heading font-semibold text-foreground">Audit History</h3>
                    <p class="text-sm text-foreground/70">Record of all year level changes and registrations.</p>
                </div>
                
                <div tabindex="0" role="region" aria-label="Year level audit history" class="data-table-wrapper" data-stack-mobile="true">
                    <table class="min-w-full divide-y divide-border">
                        <caption class="sr-only">Year level audit history</caption>
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Timestamp</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Student</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Change (Level / Section)</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase">Actor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border">
                            @forelse($logs as $log)
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td data-label="Timestamp" class="px-6 py-4 lg:whitespace-nowrap text-sm text-foreground/70">
                                        {{ $log->created_at->format('M d, Y H:i:s') }}
                                    </td>
                                    <td data-label="Student" class="px-6 py-4 lg:whitespace-nowrap text-sm font-semibold text-foreground">
                                        @if($log->user)
                                            {{ $log->user->last_name }}, {{ $log->user->first_name }}
                                        @else
                                            <span class="text-foreground/40 italic">Deleted User</span>
                                        @endif
                                    </td>
                                    <td data-label="Action" class="px-6 py-4 lg:whitespace-nowrap">
                                        @if($log->action === 'registration')
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-accent/10 text-accent border border-accent/20">Registration</span>
                                        @elseif($log->action === 'academic_year_promotion')
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">AY Promotion</span>
                                        @elseif($log->action === 'student_section_confirmation')
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">Section Confirmed</span>
                                        @elseif($log->action === 'bulk_promote')
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-secondary/10 text-secondary border border-secondary/20">Bulk Promote</span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">Manual Override</span>
                                        @endif
                                    </td>
                                    <td data-label="Change" class="px-6 py-4 lg:whitespace-nowrap text-sm text-foreground/70">
                                        <div class="mb-1">
                                            <span class="text-xs text-foreground/50 uppercase tracking-wider">Level:</span> 
                                            {{ $log->old_year_level ?? 'None' }} &rarr; <strong class="text-primary">{{ $log->new_year_level }}</strong>
                                        </div>
                                        @if($log->old_section || $log->new_section)
                                        <div>
                                            <span class="text-xs text-foreground/50 uppercase tracking-wider">Section:</span> 
                                            {{ $log->old_section ?? '—' }} &rarr; <strong class="text-primary">{{ $log->new_section ?? '—' }}</strong>
                                        </div>
                                        @else
                                        <div>
                                            <span class="text-xs text-foreground/50 uppercase tracking-wider">Section:</span> 
                                            — &rarr; <strong class="text-primary">—</strong>
                                        </div>
                                        @endif
                                    </td>
                                    <td data-label="Actor" class="px-6 py-4 lg:whitespace-nowrap text-sm text-foreground/70">
                                        @if($log->actor)
                                            {{ $log->actor->last_name }}, {{ $log->actor->first_name }}
                                        @else
                                            <span class="text-foreground/40 italic">System</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-foreground/50">No audit logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-border bg-muted/20">
                    <x-pagination :paginator="$logs" label="Year level audit pagination" />
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
