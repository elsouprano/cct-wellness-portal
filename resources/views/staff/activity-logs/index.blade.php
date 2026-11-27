@php
    $pageTitle = $isCounselorView ? 'My activity' : 'Activity Logs';
    $formAction = $isCounselorView ? route('my-activity.index') : route('activity-logs.index');
    $groupedLogs = $logs->getCollection()->groupBy(fn ($item) => $item->day_group_label);

    $areaIcons = [
        'Announcements' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73',
        'Schedules' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'Question Bank' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'Students' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'Accounts' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
        'Institution' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21',
        'Flagged Responses' => 'M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.73a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5',
        'Settings' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
    ];
@endphp

@section('title', $pageTitle)
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 min-w-0">
            <div class="min-w-0">
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight truncate">
                    {{ $pageTitle }}
                </h2>
                <p class="text-sm text-foreground/60 mt-0.5">
                    @if($isCounselorView)
                        A plain-language timeline of your own actions across schedules, students, announcements, and reviews.
                    @else
                        A plain-language timeline of actions taken by system administrators and guidance counselors.
                    @endif
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20 self-start sm:self-auto shrink-0">
                {{ number_format($logs->total()) }} {{ Str::plural('activity', $logs->total()) }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-border shadow-xs p-4 sm:p-6">
            <form method="GET" action="{{ $formAction }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    @unless($isCounselorView)
                        <!-- Person -->
                        <div>
                            <label for="person" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Person</label>
                            <select id="person" name="person" class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                                <option value="">All Staff</option>
                                @foreach($staffPeople as $staffMember)
                                    @php
                                        $staffFullName = trim("{$staffMember->first_name} {$staffMember->last_name}");
                                        $staffRoleText = $staffMember->role === 'system_admin' ? 'System Administrator' : 'Guidance Counselor';
                                    @endphp
                                    <option value="{{ $staffMember->id }}" @selected($person === $staffMember->id)>
                                        {{ $staffFullName }} ({{ $staffRoleText }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Role</label>
                            <select id="role" name="role" class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                                <option value="">All Staff Roles</option>
                                <option value="system_admin" @selected($role === 'system_admin')>System Administrator</option>
                                <option value="guidance_counselor" @selected($role === 'guidance_counselor')>Guidance Counselor</option>
                            </select>
                        </div>
                    @endunless

                    <!-- Area -->
                    <div>
                        <label for="area" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Area</label>
                        <select id="area" name="area" class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                            <option value="">All Areas</option>
                            @foreach($areas as $areaOption)
                                <option value="{{ $areaOption }}" @selected($area === $areaOption)>{{ $areaOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action -->
                    <div>
                        <label for="action" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Action</label>
                        <select id="action" name="action" class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                            <option value="">All Actions</option>
                            @foreach($actionOptions as $slug => $label)
                                <option value="{{ $slug }}" @selected($action === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">From Date</label>
                        <input type="date"
                               id="date_from"
                               name="date_from"
                               value="{{ $dateFrom }}"
                               class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">To Date</label>
                        <input type="date"
                               id="date_to"
                               name="date_to"
                               value="{{ $dateTo }}"
                               class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                    </div>

                    <!-- Search -->
                    <div class="{{ $isCounselorView ? 'sm:col-span-2 lg:col-span-1' : '' }}">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Search</label>
                        <input type="text"
                               id="search"
                               name="search"
                               value="{{ $search }}"
                               placeholder="{{ $isCounselorView ? 'Search your activity...' : 'Search by name or summary...' }}"
                               class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                    </div>

                    <!-- Sort -->
                    <div>
                        <label for="sort" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Order</label>
                        <select id="sort" name="sort" class="w-full min-h-[44px] rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                            <option value="newest" @selected($sort === 'newest')>Newest first</option>
                            <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                        </select>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="p-3 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-border/60">
                    <p class="text-sm text-foreground/60">
                        Showing
                        <span class="font-semibold text-foreground">{{ $logs->firstItem() ?? 0 }}</span>
                        to
                        <span class="font-semibold text-foreground">{{ $logs->lastItem() ?? 0 }}</span>
                        of
                        <span class="font-semibold text-foreground">{{ number_format($logs->total()) }}</span>
                        {{ Str::plural('activity', $logs->total()) }}
                    </p>
                    <div class="flex items-center gap-2.5">
                        <a href="{{ $formAction }}" class="btn-secondary text-sm min-h-[44px] px-4 py-2 rounded-xl flex-1 sm:flex-initial text-center">Reset</a>
                        <button type="submit" class="btn-primary text-sm min-h-[44px] px-5 py-2 rounded-xl flex-1 sm:flex-initial">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Day-Grouped Timeline -->
        @if($logs->count() > 0)
            <div class="space-y-6">
                @foreach($groupedLogs as $dayLabel => $dayEntries)
                    <section aria-label="{{ $dayLabel }}" class="space-y-3">
                        <div class="flex items-center gap-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground/65 bg-muted px-3 py-1 rounded-full border border-border shrink-0">
                                {{ $dayLabel }}
                            </h3>
                            <div class="h-px flex-1 bg-border/80"></div>
                        </div>

                        <div class="bg-white rounded-2xl border border-border shadow-xs divide-y divide-border/75 overflow-hidden">
                            @foreach($dayEntries as $log)
                                @php
                                    $areaName = $log->area_label;
                                    $iconPath = $areaIcons[$areaName] ?? $areaIcons['Settings'];
                                    $roleBadgeClass = $log->actor_role === 'system_admin'
                                        ? 'bg-primary/10 text-primary border-primary/20'
                                        : 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                    $detailUrl = $isCounselorView
                                        ? route('my-activity.show', $log)
                                        : route('activity-logs.show', $log);
                                @endphp
                                <article class="p-4 sm:p-5 hover:bg-muted/20 transition-colors">
                                    <div class="flex items-start gap-3.5 sm:gap-4">
                                        <!-- Area Timeline Icon -->
                                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary border border-primary/15 flex items-center justify-center shrink-0 mt-0.5">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                                            </svg>
                                        </div>

                                        <!-- Who + What + Area + When -->
                                        <div class="min-w-0 flex-1 space-y-1.5">
                                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                                <div class="flex flex-wrap items-center gap-2 min-w-0">
                                                    <span class="font-semibold text-sm text-foreground">{{ $log->actor_display_name }}</span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $roleBadgeClass }}">
                                                        {{ $log->role_label }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-foreground/75 border border-border">
                                                        {{ $areaName }}
                                                    </span>
                                                </div>
                                                <time datetime="{{ $log->created_at?->toIso8601String() }}" class="text-xs font-medium text-foreground/55 whitespace-nowrap">
                                                    {{ $log->when_label }}
                                                </time>
                                            </div>

                                            <p class="text-sm sm:text-[15px] text-foreground leading-relaxed break-words">
                                                {{ $log->plain_description }}
                                            </p>

                                            <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
                                                <span class="text-xs text-foreground/55">{{ $log->action_label }}</span>
                                                <a href="{{ $detailUrl }}" class="inline-flex items-center gap-1 min-h-[36px] sm:min-h-0 py-1 text-xs sm:text-sm font-semibold text-primary hover:underline">
                                                    <span>{{ $log->hasRecordedChanges() ? 'View changes' : 'View details' }}</span>
                                                    <span aria-hidden="true">&rarr;</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                    <x-pagination
                        :paginator="$logs"
                        :show-limit="true"
                        limit-param="per_page"
                        :limits="[10, 15, 25, 50]"
                        label="Activity timeline pagination"
                    />
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-border shadow-xs p-8 sm:p-12 text-center">
                <p class="font-heading text-base font-bold text-foreground">No activity records found</p>
                <p class="text-sm text-foreground/60 mt-1">
                    @if($isCounselorView)
                        Your actions will appear here once you manage schedules, announcements, or student submissions.
                    @else
                        Try adjusting your person, area, action, or date filters.
                    @endif
                </p>
            </div>
        @endif
    </div>
</x-staff-layout>
