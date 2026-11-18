@section('title', 'Activity Logs')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Activity Logs') }}
                </h2>
                <p class="text-sm text-foreground/60 mt-0.5">
                    Review a clear record of important account, student, and assessment activities.
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20 self-start sm:self-auto">
                {{ number_format($logs->total()) }} {{ Str::plural('record', $logs->total()) }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search & Filter Card -->
        <div class="bg-white rounded-2xl border border-border shadow-xs p-5 sm:p-6">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Search</label>
                        <input type="text"
                               id="search"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search by person's name, action, or summary..."
                               class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                    </div>

                    <!-- Module -->
                    <div>
                        <label for="module" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Module</label>
                        <select id="module" name="module" class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                            <option value="">All Modules</option>
                            @foreach($modules as $mod)
                                <option value="{{ $mod }}" @selected($module === $mod)>{{ $mod }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Role</label>
                        <select id="role" name="role" class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                            <option value="">All Roles</option>
                            <option value="system_admin" @selected($role === 'system_admin')>System Admin</option>
                            <option value="guidance_counselor" @selected($role === 'guidance_counselor')>Guidance Counselor</option>
                            <option value="student" @selected($role === 'student')>Student</option>
                            <option value="system" @selected($role === 'system')>System</option>
                            <option value="guest" @selected($role === 'guest')>Guest</option>
                        </select>
                    </div>

                    <!-- Action Type -->
                    <div>
                        <label for="action" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Action Type</label>
                        <select id="action" name="action" class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                            <option value="">All Actions</option>
                            @foreach($actionOptions as $slug => $label)
                                <option value="{{ $slug }}" @selected($action === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Date From</label>
                        <input type="date"
                               id="date_from"
                               name="date_from"
                               value="{{ $dateFrom }}"
                               class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Date To</label>
                        <input type="date"
                               id="date_to"
                               name="date_to"
                               value="{{ $dateTo }}"
                               class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                    </div>

                    <!-- Sort & Per Page -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="sort" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Order</label>
                            <select id="sort" name="sort" class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                            </select>
                        </div>
                        <div>
                            <label for="per_page" class="block text-xs font-semibold uppercase tracking-wider text-foreground/60 mb-1.5">Rows</label>
                            <select id="per_page" name="per_page" class="w-full rounded-xl border-border text-sm focus:border-primary focus:ring-primary shadow-xs">
                                @foreach([10, 15, 25, 50] as $size)
                                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="p-3 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-border/60">
                    <p class="text-sm text-foreground/60">
                        Showing
                        <span class="font-semibold text-foreground">{{ $logs->firstItem() ?? 0 }}</span>
                        to
                        <span class="font-semibold text-foreground">{{ $logs->lastItem() ?? 0 }}</span>
                        of
                        <span class="font-semibold text-foreground">{{ number_format($logs->total()) }}</span>
                        results
                    </p>
                    <div class="flex items-center gap-2.5">
                        <a href="{{ route('activity-logs.index') }}" class="btn-secondary text-sm px-4 py-2 rounded-xl">Reset</a>
                        <button type="submit" class="btn-primary text-sm px-5 py-2 rounded-xl">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Activity Logs Table (5 scannable columns, no technical internals) -->
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-foreground/60 whitespace-nowrap">Date &amp; Time</th>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-foreground/60 whitespace-nowrap">User</th>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-foreground/60 whitespace-nowrap">Action</th>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-foreground/60 whitespace-nowrap">Module</th>
                                <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-foreground/60">Summary &amp; Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/70 bg-white">
                            @foreach($logs as $log)
                                @php
                                    $roleBadgeClass = match($log->actor_role) {
                                        'system_admin' => 'bg-primary/10 text-primary border-primary/20',
                                        'guidance_counselor' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'student' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        default => 'bg-muted text-foreground/70 border-border',
                                    };
                                @endphp
                                <tr class="hover:bg-muted/25 transition-colors">
                                    <td class="px-5 py-4 whitespace-nowrap text-foreground/80">
                                        <div class="font-medium text-foreground">{{ $log->created_at?->format('M d, Y') }}</div>
                                        <div class="text-xs text-foreground/55">{{ $log->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-foreground">{{ $log->actor_name ?? 'System' }}</div>
                                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold border {{ $roleBadgeClass }}">
                                            {{ $log->role_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="font-semibold text-foreground">{{ $log->action_label }}</span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-foreground/80 font-medium">
                                        {{ $log->module }}
                                    </td>
                                    <td class="px-5 py-4 text-foreground/80">
                                        <div class="flex items-center justify-between gap-4">
                                            <p class="line-clamp-2 max-w-md">{{ $log->description }}</p>
                                            <a href="{{ route('activity-logs.show', $log) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline shrink-0">
                                                View Details &rarr;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-5 py-4 border-t border-border bg-muted/20">
                        {{ $logs->links() }}
                    </div>
                @endif
            @else
                <div class="p-10 text-center">
                    <p class="font-heading text-base font-bold text-foreground">No activity records found</p>
                    <p class="text-sm text-foreground/60 mt-1">Try adjusting your search or filter selections.</p>
                </div>
            @endif
        </div>
    </div>
</x-staff-layout>
