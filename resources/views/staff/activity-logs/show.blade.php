@section('title', 'Activity Details')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Activity Details') }}
                </h2>
                <p class="text-sm text-foreground/60 mt-0.5">
                    {{ $activityLog->created_at?->format('F j, Y \a\t h:i A') }}
                </p>
            </div>
            <a href="{{ route('activity-logs.index') }}" class="btn-secondary text-sm px-4 py-2 rounded-xl self-start sm:self-auto">
                &larr; Back to Activity Logs
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Summary Card -->
        <div class="bg-white rounded-2xl border border-border shadow-xs p-6 sm:p-8 space-y-6">
            <div class="pb-5 border-b border-border/70">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                    {{ $activityLog->module }}
                </span>
                <h3 class="font-heading text-xl font-bold text-foreground mt-2.5">{{ $activityLog->action_label }}</h3>
                <p class="text-sm sm:text-base text-foreground/80 mt-1.5 leading-relaxed">{{ $activityLog->description }}</p>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Performed By</dt>
                    <dd class="mt-1 font-semibold text-foreground">{{ $activityLog->actor_name ?? 'System' }}</dd>
                </div>

                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Role</dt>
                    <dd class="mt-1 font-semibold text-foreground">{{ $activityLog->role_label }}</dd>
                </div>

                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Date &amp; Time</dt>
                    <dd class="mt-1 font-semibold text-foreground">{{ $activityLog->created_at?->format('M d, Y h:i A') }}</dd>
                </div>

                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Affected Student / Record</dt>
                    <dd class="mt-1 font-semibold text-foreground">{{ $activityLog->subject_display ?? 'Not applicable' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Plain-Language Before / After Changes -->
        @php $formattedChanges = $activityLog->formattedChanges(); @endphp
        @if(!empty($formattedChanges))
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-border bg-muted/30">
                    <h4 class="font-heading text-base font-bold text-foreground">Summary of Changes</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/20">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-foreground/60">Information</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-foreground/60">Previous</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wider text-foreground/60">Updated To</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/70">
                            @foreach($formattedChanges as $row)
                                <tr>
                                    <td class="px-6 py-3.5 font-semibold text-foreground whitespace-nowrap">{{ $row['label'] }}</td>
                                    <td class="px-6 py-3.5 text-foreground/75 break-words">{{ $row['before'] }}</td>
                                    <td class="px-6 py-3.5 text-foreground font-medium break-words">{{ $row['after'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-staff-layout>
