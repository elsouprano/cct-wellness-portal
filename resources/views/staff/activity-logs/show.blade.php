@php
    $backRoute = $isCounselorView ? route('my-activity.index') : route('activity-logs.index');
    $backLabel = $isCounselorView ? 'Back to My activity' : 'Back to Activity Logs';
    $formattedChanges = $activityLog->formattedChanges();
@endphp

@section('title', 'Activity Details')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 min-w-0">
            <div class="min-w-0">
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight truncate">
                    {{ __('Activity Details') }}
                </h2>
                <p class="text-sm text-foreground/60 mt-0.5">
                    {{ $activityLog->when_label }}
                </p>
            </div>
            <a href="{{ $backRoute }}" class="btn-secondary text-sm min-h-[44px] px-4 py-2 rounded-xl self-start sm:self-auto">
                &larr; {{ $backLabel }}
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Plain-Language Summary Card -->
        <div class="bg-white rounded-2xl border border-border shadow-xs p-5 sm:p-8 space-y-6">
            <div class="pb-5 border-b border-border/70 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                        {{ $activityLog->area_label }}
                    </span>
                    <span class="text-xs font-medium text-foreground/60">
                        {{ $activityLog->action_label }}
                    </span>
                </div>
                <h3 class="font-heading text-lg sm:text-xl font-bold text-foreground leading-snug break-words">
                    {{ $activityLog->plain_description }}
                </h3>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Who</dt>
                    <dd class="mt-1 font-semibold text-foreground break-words">{{ $activityLog->actor_display_name }}</dd>
                    <dd class="text-xs text-foreground/65 mt-0.5">{{ $activityLog->role_label }}</dd>
                </div>

                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Area</dt>
                    <dd class="mt-1 font-semibold text-foreground">{{ $activityLog->area_label }}</dd>
                </div>

                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">When</dt>
                    <dd class="mt-1 font-semibold text-foreground">{{ $activityLog->when_label }}</dd>
                    @if($activityLog->created_at)
                        <dd class="text-xs text-foreground/60 mt-0.5">{{ $activityLog->created_at->format('F j, Y \a\t g:i A') }}</dd>
                    @endif
                </div>

                <div class="p-4 rounded-xl bg-muted/30 border border-border/70">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-foreground/55">Record</dt>
                    <dd class="mt-1 font-semibold text-foreground break-words">{{ $activityLog->subject_display ?? 'Not applicable' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Plain-Language "What changed" (Before -> After) -->
        @if(!empty($formattedChanges))
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="px-5 sm:px-6 py-4 border-b border-border bg-muted/30">
                    <h4 class="font-heading text-base font-bold text-foreground">What changed</h4>
                    <p class="text-xs text-foreground/60 mt-0.5">Before and after values recorded for this action.</p>
                </div>

                <div class="divide-y divide-border/70">
                    @foreach($formattedChanges as $row)
                        <div class="px-5 sm:px-6 py-4 grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-start md:items-center text-sm">
                            <div class="md:col-span-4 font-semibold text-foreground">
                                {{ $row['label'] }}
                            </div>
                            <div class="md:col-span-8 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 min-w-0">
                                <span class="px-3 py-1.5 rounded-lg bg-muted/60 border border-border text-foreground/75 break-words">
                                    {{ $row['before'] }}
                                </span>
                                <span aria-hidden="true" class="text-foreground/45 font-bold hidden sm:inline">&rarr;</span>
                                <span aria-hidden="true" class="text-xs font-semibold uppercase tracking-wider text-foreground/45 sm:hidden">Changed to</span>
                                <span class="px-3 py-1.5 rounded-lg bg-primary/8 border border-primary/20 text-foreground font-semibold break-words">
                                    {{ $row['after'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-staff-layout>
