@section('title')Assessment Details ({{ $submission->academic_year }})@endsection
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    Assessment History
                </h1>
                <p class="text-foreground/60 text-xs sm:text-sm mt-0.5 break-words">Submitted on {{ $submission->submitted_at->format('F j, Y \a\t g:i A') }} &middot; A.Y. {{ $submission->academic_year }}</p>
            </div>
            <a href="{{ route('inventory.history') }}" class="btn-secondary text-sm px-4 py-2 flex items-center gap-2 self-start sm:self-auto shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 shrink-0" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Back to History
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @php
            $responsesByCategory = $submission->responses->groupBy('category');
        @endphp

        @if($responsesByCategory->isEmpty())
            <div class="bg-white rounded-2xl border border-border shadow-xs p-6 sm:p-8 text-center">
                <p class="text-foreground/60 text-sm">No responses found for this submission.</p>
            </div>
        @else
            <div x-data="{ activeCategory: '{{ $responsesByCategory->keys()->first() }}' }">
                <!-- Category Tabs -->
                <div class="border-b border-border mb-5">
                    <nav class="flex space-x-2 sm:space-x-5 overflow-x-auto overflow-y-hidden" aria-label="Assessment sections">
                        @foreach($responsesByCategory->keys() as $category)
                            <button
                                type="button"
                                @click="activeCategory = '{{ $category }}'"
                                :class="activeCategory === '{{ $category }}' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border font-medium'"
                                class="min-h-[44px] whitespace-nowrap py-2.5 px-2.5 border-b-2 text-xs sm:text-sm transition-colors duration-150 focus:outline-none cursor-pointer"
                            >
                                {{ \App\Support\InstrumentLabel::for($category) }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <!-- Category Content -->
                @foreach($responsesByCategory as $category => $responses)
                    @php
                        $categoryConfig = $inventoryConfig->firstWhere('name', $category);
                    @endphp
                    <div x-show="activeCategory === '{{ $category }}'"
                         x-cloak
                         x-transition:enter="transition-opacity ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100">
                        <div class="bg-white overflow-hidden shadow-xs rounded-2xl border border-border">
                            <div class="px-4 sm:px-6 py-4 border-b border-border bg-muted/20">
                                <h2 class="text-sm sm:text-base font-bold text-foreground break-words">
                                    {{ \App\Support\InstrumentLabel::for($category) }} Answers
                                </h2>
                            </div>

                            <!-- Mobile Cards (< md) -->
                            <div class="md:hidden divide-y divide-border">
                                @foreach($responses->sortBy('item_number') as $response)
                                    @php
                                        $questionPrompt = 'Question prompt unavailable';
                                        if ($categoryConfig) {
                                            $questionItem = $categoryConfig->questionItems->firstWhere('item_number', $response->item_number);
                                            if ($questionItem) {
                                                $questionPrompt = $questionItem->prompt;
                                            }
                                        }
                                    @endphp
                                    <div class="p-4 space-y-2.5">
                                        <div class="flex items-start gap-2.5">
                                            <span class="shrink-0 w-6 h-6 rounded-full bg-muted text-foreground/80 border border-border flex items-center justify-center text-xs font-bold tabular-nums mt-0.5">
                                                {{ $response->item_number }}
                                            </span>
                                            <p class="text-sm text-foreground/90 leading-snug break-words min-w-0 flex-1">
                                                {{ $questionPrompt }}
                                            </p>
                                        </div>
                                        <div class="pl-8">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary font-bold border border-primary/20 text-xs sm:text-sm break-words max-w-full">
                                                {{ $response->response_value }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Desktop Table (md+) -->
                            <div class="hidden md:block overflow-x-auto" tabindex="0" role="region" aria-label="{{ \App\Support\InstrumentLabel::for($category) }} answers">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-foreground/60 uppercase tracking-wider w-16">No.</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-foreground/60 uppercase tracking-wider">Question</th>
                                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-foreground/60 uppercase tracking-wider w-56">Your Answer</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-white">
                                        @foreach($responses->sortBy('item_number') as $response)
                                            @php
                                                $questionPrompt = 'Question prompt unavailable';
                                                if ($categoryConfig) {
                                                    $questionItem = $categoryConfig->questionItems->firstWhere('item_number', $response->item_number);
                                                    if ($questionItem) {
                                                        $questionPrompt = $questionItem->prompt;
                                                    }
                                                }
                                            @endphp
                                            <tr class="hover:bg-muted/30 transition-colors">
                                                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-foreground/70 tabular-nums">
                                                    {{ $response->item_number }}
                                                </td>
                                                <td class="px-3 py-4 text-sm text-foreground/90 leading-relaxed break-words">
                                                    {{ $questionPrompt }}
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-3 py-1 rounded-full bg-primary/10 text-primary font-bold border border-primary/20 text-sm break-words text-right">
                                                        {{ $response->response_value }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
