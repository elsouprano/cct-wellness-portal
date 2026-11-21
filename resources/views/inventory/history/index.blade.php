@section('title', 'Assessment History')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="font-heading font-bold text-xl text-foreground leading-tight">
                    {{ __('Assessment History') }}
                </h1>
                <p class="text-sm text-muted-foreground mt-0.5">Review your submitted Student Individual Inventory records by academic year.</p>
            </div>
        </div>
    </x-slot>

    <div
        class="space-y-6"
        x-data="clientPaginator({ total: {{ $submissions->count() }}, defaultLimit: 10, pageParam: 'page', limitParam: 'limit' })"
    >
        <x-data-table
            caption="Submitted Student Individual Inventory records"
            :stack-mobile="true"
            :empty="$submissions->isEmpty()"
            empty-title="No assessments submitted yet"
            empty-message="Completed psychological inventories will appear here once submitted."
        >
            <thead class="bg-muted/60">
                <tr>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Academic Year</th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Year Level</th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date Submitted</th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-surface divide-y divide-border">
                @foreach($submissions as $index => $submission)
                    @php $historyShowUrl = route('inventory.history.show', $submission->id); @endphp
                    <tr
                        x-show="isVisible({{ $index }})"
                        class="hover:bg-muted/40 transition-colors cursor-pointer"
                        @click="if (!$event.target.closest('a, button')) { window.location = @js($historyShowUrl); }"
                    >
                        <td data-label="Academic Year" class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-foreground">{{ $submission->academic_year }} Academic Year</span>
                        </td>
                        <td data-label="Year Level" class="px-6 py-4 whitespace-nowrap text-sm text-foreground/80">
                            {{ auth()->user()->year_level ? auth()->user()->year_level . ' Year' : '—' }}
                        </td>
                        <td data-label="Date Submitted" class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                            {{ $submission->submitted_at->format('F j, Y \a\t g:i A') }}
                        </td>
                        <td data-label="Status" class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-token-sm text-xs font-semibold bg-secondary/10 text-secondary border border-secondary/25">
                                <x-icon name="check" class="w-3.5 h-3.5" />
                                Submitted
                            </span>
                        </td>
                        <td data-label="Actions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('inventory.history.show', $submission->id) }}" class="inline-flex items-center gap-1 text-primary hover:underline font-semibold text-sm">
                                View Answers
                                <x-icon name="arrow-right" class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <x-slot name="footer">
                <x-pagination mode="client" label="Assessment history pagination" />
            </x-slot>
        </x-data-table>
    </div>
</x-app-layout>
