@section('title')Assessment Details ({{ $submission->academic_year }})@endsection
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    Assessment History
                </h2>
                <p class="text-foreground/60 text-sm mt-0.5">Submitted on {{ $submission->submitted_at->format('F j, Y \a\t g:i A') }} &middot; A.Y. {{ $submission->academic_year }}</p>
            </div>
            <a href="{{ route('inventory.history') }}" class="btn-secondary text-sm px-4 py-2 flex items-center gap-2 self-start sm:self-auto">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
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
            <div class="bg-white rounded-2xl border border-border shadow-xs p-8 text-center">
                <p class="text-foreground/60 text-sm">No responses found for this submission.</p>
            </div>
        @else
            <div x-data="{ activeCategory: '{{ $responsesByCategory->keys()->first() }}' }">
                <!-- Category Tabs -->
                <div class="border-b border-border mb-6">
                    <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                        @foreach($responsesByCategory->keys() as $category)
                            <button 
                                @click="activeCategory = '{{ $category }}'"
                                :class="activeCategory === '{{ $category }}' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border font-medium'"
                                class="whitespace-nowrap py-3 px-2 border-b-2 text-sm transition-colors duration-200 uppercase tracking-wide focus:outline-none cursor-pointer"
                            >
                                {{ str_replace('_', ' ', $category) }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <!-- Category Content -->
                @foreach($responsesByCategory as $category => $responses)
                    @php
                        $categoryConfig = $inventoryConfig->firstWhere('name', $category);
                    @endphp
                    <div x-show="activeCategory === '{{ $category }}'" style="display: none;" x-transition.opacity>
                        <div class="bg-white overflow-hidden shadow-xs rounded-2xl border border-border">
                            <div class="px-6 py-4 border-b border-border bg-muted/20">
                                <h3 class="text-base font-bold text-foreground uppercase tracking-wide">
                                    {{ str_replace('_', ' ', $category) }} Answers
                                </h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border">
                                    <thead class="bg-muted">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-foreground/60 uppercase tracking-wider w-16">No.</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-foreground/60 uppercase tracking-wider">Question</th>
                                            <th scope="col" class="px-6 py-3.5 text-right text-sm font-semibold text-foreground/60 uppercase tracking-wider w-32">Your Answer</th>
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
                                                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-foreground/70">
                                                    {{ $response->item_number }}
                                                </td>
                                                <td class="px-3 py-4 text-sm text-foreground/90 leading-relaxed">
                                                    {{ $questionPrompt }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-3 py-1 rounded-full bg-primary/10 text-primary font-bold border border-primary/20 text-sm">
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
