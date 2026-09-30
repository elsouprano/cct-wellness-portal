<x-app-layout>
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 space-y-8">
        <div>
            <h1 class="font-heading text-3xl font-bold text-foreground">Assessment History</h1>
            <p class="text-foreground/70 mt-1">View your past individual inventory submissions and answers.</p>
        </div>

        @if($submissions->isEmpty())
            <div class="bg-white rounded-2xl border border-border shadow-sm p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-foreground/30 mx-auto mb-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <h3 class="font-heading text-xl font-bold text-foreground mb-2">No Assessments Found</h3>
                <p class="text-foreground/60 text-lg">You have not completed any psychological assessments yet.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
                <ul class="divide-y divide-border">
                    @foreach($submissions as $submission)
                        <li>
                            <a href="{{ route('inventory.history.show', $submission->id) }}" class="block p-6 hover:bg-muted/30 transition-colors group">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <h3 class="font-bold text-lg text-foreground group-hover:text-primary transition-colors">
                                            {{ $submission->academic_year }} Academic Year
                                        </h3>
                                        <p class="text-sm text-foreground/70 mt-1">
                                            Submitted on {{ $submission->submitted_at->format('F j, Y \a\t g:i A') }}
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center gap-1 text-primary text-sm font-bold group-hover:underline">
                                            View Answers
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 transition-transform group-hover:translate-x-1">
                                              <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
