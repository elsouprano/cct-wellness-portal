@section('title', 'Assessment History')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Assessment History') }}
                </h2>
                <p class="text-sm text-foreground/60 mt-0.5">View your past Student Individual Inventory submissions and answers.</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if($submissions->isEmpty())
            <div class="bg-white rounded-2xl border border-border shadow-xs p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-foreground/30 mx-auto mb-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <h3 class="font-heading text-lg font-bold text-foreground mb-1">No Assessments Found</h3>
                <p class="text-foreground/60 text-sm">You have not completed any psychological assessments yet.</p>
            </div>
        @else
            <div class="bg-white border border-border rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-foreground/60 uppercase tracking-wider">Academic Year</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-foreground/60 uppercase tracking-wider">Year Level</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-foreground/60 uppercase tracking-wider">Date Submitted</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-foreground/60 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-right text-sm font-semibold text-foreground/60 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border">
                            @foreach($submissions as $submission)
                                <tr class="hover:bg-muted/50 transition-colors cursor-pointer" onclick="window.location='{{ route('inventory.history.show', $submission->id) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-foreground">{{ $submission->academic_year }} Academic Year</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/75">
                                        {{ auth()->user()->year_level ? auth()->user()->year_level . ' Year' : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                        Submitted on {{ $submission->submitted_at->format('F j, Y \a\t g:i A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-semibold bg-secondary/10 text-secondary border border-secondary/20">
                                            Submitted
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" onclick="event.stopPropagation()">
                                        <a href="{{ route('inventory.history.show', $submission->id) }}" class="inline-flex items-center gap-1 text-primary hover:underline font-bold text-sm">
                                            View Answers
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
