<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                Inventory Verification View
            </h2>
            <a href="{{ route('dashboard') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border p-lg mb-6">
                <h3 class="text-xl font-heading font-semibold mb-4 text-primary border-b border-border pb-2">Student Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="block text-foreground/60 font-medium">Name</span>
                        <span class="block font-semibold text-foreground">{{ $submission->user->last_name }}, {{ $submission->user->first_name }} {{ $submission->user->middle_initial }}</span>
                    </div>
                    <div>
                        <span class="block text-foreground/60 font-medium">Program & Section</span>
                        <span class="block font-semibold text-foreground">{{ $submission->user->program }} - {{ $submission->user->section }}</span>
                    </div>
                    <div>
                        <span class="block text-foreground/60 font-medium">Year Level</span>
                        <span class="block font-semibold text-foreground">{{ $submission->user->year_level }}</span>
                    </div>
                    <div>
                        <span class="block text-foreground/60 font-medium">Submitted At</span>
                        <span class="block font-semibold text-foreground">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : 'Incomplete' }}</span>
                    </div>
                </div>
            </div>

            @if($submission->flags->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-destructive/20 mb-6">
                    <div class="px-6 py-4 bg-destructive/10 border-b border-destructive/20 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-destructive">
                            ⚠ Automated Flags Detected ({{ $submission->flags->count() }})
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @foreach($submission->flags as $flag)
                            <div class="flex items-start justify-between p-4 rounded-xl border {{ $flag->is_reviewed ? 'bg-muted border-border opacity-75' : 'bg-white border-destructive/20 shadow-sm' }}">
                                <div>
                                    <div class="flex items-center space-x-2 mb-1">
                                        @if($flag->is_reviewed)
                                            <span class="text-primary font-medium" title="Reviewed by {{ $flag->reviewer->first_name ?? 'Staff' }} on {{ $flag->reviewed_at->format('M d, Y') }}">
                                                ✓ Reviewed
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-destructive/10 text-destructive uppercase tracking-wide">
                                                {{ str_replace('_', ' ', $flag->flag_type) }}
                                            </span>
                                        @endif
                                        <h4 class="font-bold text-foreground">
                                            @if($flag->flag_type === 'speed')
                                                Suspiciously fast completion
                                            @elseif($flag->flag_type === 'straight_line')
                                                Straight-lined category: {{ $flag->category }}
                                            @elseif($flag->flag_type === 'contradiction')
                                                Inconsistent responses in: {{ $flag->subscale_tag ?? $flag->category }}
                                            @endif
                                        </h4>
                                    </div>
                                    <p class="text-sm text-foreground/80 mt-1">
                                        @if($flag->flag_type === 'speed')
                                            Average time per item: {{ $flag->details['avg_seconds_per_item'] }}s (Threshold: {{ $flag->details['threshold'] }}s).
                                        @elseif($flag->flag_type === 'straight_line')
                                            Selected the same response for {{ $flag->details['percentage'] }}% of items in this category.
                                        @elseif($flag->flag_type === 'contradiction')
                                            Response spread of {{ $flag->details['spread'] }} out of max {{ $flag->details['max_range'] }} ({{ $flag->details['spread_percentage'] }}%).
                                        @endif
                                    </p>
                                    @if($flag->is_reviewed && $flag->reviewer_notes)
                                        <div class="mt-2 text-sm italic text-foreground/60 bg-muted/50 p-3 rounded-lg border border-border">
                                            Note: {{ $flag->reviewer_notes }}
                                        </div>
                                    @endif
                                </div>
                                
                                @if(!$flag->is_reviewed)
                                    <form method="POST" action="{{ route('staff.inventory.flags.review', $flag) }}" class="flex flex-col space-y-2 items-end">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="reviewer_notes" placeholder="Optional notes..." class="text-sm border-border rounded-lg shadow-sm focus:ring-primary focus:border-primary">
                                        <button type="submit" class="btn-primary text-xs py-1.5 px-3">
                                            Mark as Reviewed
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($submission->scores->isEmpty())
                <div class="bg-secondary/10 border-l-4 border-secondary p-4 rounded-xl text-secondary">
                    No computed scores found for this submission. It may still be in progress.
                </div>
            @else
                <div x-data="{ activeTab: '{{ $scoresByCategory->keys()->first() }}' }">
                    <!-- Tab Navigation -->
                    <div class="border-b border-border mb-6">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                            @foreach($scoresByCategory as $category => $scores)
                                <button 
                                    @click="activeTab = '{{ $category }}'"
                                    :class="activeTab === '{{ $category }}' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-colors duration-150 uppercase"
                                    :aria-selected="activeTab === '{{ $category }}'"
                                    role="tab"
                                >
                                    {{ $category }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Panels -->
                    <div>
                        @foreach($scoresByCategory as $category => $scores)
                            <div 
                                x-show="activeTab === '{{ $category }}'"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                style="display: none;"
                                role="tabpanel"
                            >
                                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                                    <div class="px-6 py-4 border-b border-border bg-muted/30 flex justify-between items-center">
                                        <h4 class="text-lg font-bold text-foreground uppercase">{{ $category }}</h4>
                                        @if(strtolower($category) === 'dass21')
                                            <span class="text-xs text-accent bg-accent/10 px-3 py-1 rounded-full font-semibold border border-accent/20">Severity Labels Apply</span>
                                        @endif
                                    </div>
                                    
                                    <div class="p-6">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            @foreach($scores as $score)
                                                <div class="p-5 border border-border rounded-2xl bg-muted/20 shadow-sm">
                                                    <div class="text-sm text-foreground/60 uppercase tracking-wide font-medium mb-1">
                                                        {{ $score->subscale_name ?? 'Overall Total' }}
                                                    </div>
                                                    <div class="text-3xl font-light text-primary">
                                                        @if(strtolower($category) === 'learning_style')
                                                            {{ $score->raw_score }}
                                                        @elseif(strtolower($category) === 'dass21')
                                                            {{ $score->scaled_score }} <span class="text-sm text-foreground/40 ml-1">/ 42</span>
                                                        @else
                                                            {{ $score->raw_score }}
                                                        @endif
                                                    </div>
                                                    @if($score->severity_label)
                                                        <div class="mt-3">
                                                            @php
                                                                $colorClass = match(strtolower($score->severity_label)) {
                                                                    'normal' => 'bg-primary/10 text-primary border-primary/20',
                                                                    'mild' => 'bg-secondary/10 text-secondary border-secondary/20',
                                                                    'moderate' => 'bg-accent/10 text-accent border-accent/20',
                                                                    'severe', 'extremely severe' => 'bg-destructive/10 text-destructive border-destructive/20',
                                                                    default => 'bg-muted text-foreground/80 border-border'
                                                                };
                                                            @endphp
                                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full border {{ $colorClass }}">
                                                                {{ $score->severity_label }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if(strtolower($category) === 'dass21')
                                                        <div class="mt-3 text-xs font-medium text-foreground/50 bg-white inline-block px-2 py-1 rounded shadow-sm border border-border">Raw Sum: {{ $score->raw_score }} (x2 applied)</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        
                                        @if(strtolower($category) === 'dass21')
                                            <div class="mt-6 text-sm italic text-foreground/60 bg-muted/50 p-4 rounded-xl border border-border">
                                                Note: These severity labels reflect symptom severity, not a clinical diagnosis.
                                            </div>
                                        @endif

                                        <!-- Raw Item Responses -->
                                        @if(isset($responsesByCategory[$category]) && $responsesByCategory[$category]->isNotEmpty())
                                            <div class="mt-8 border-t border-border pt-6">
                                                <h5 class="text-md font-semibold text-foreground mb-4 uppercase tracking-wide">Item Responses</h5>
                                                <div class="overflow-x-auto bg-white shadow-sm ring-1 ring-border sm:rounded-2xl">
                                                    <table class="min-w-full divide-y divide-border">
                                                        <thead class="bg-muted/50">
                                                            <tr>
                                                                <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider sm:pl-6 w-16">Item</th>
                                                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Prompt</th>
                                                                <th scope="col" class="px-3 py-3 text-right text-xs font-semibold text-foreground/70 uppercase tracking-wider w-32 sm:pr-6">Response</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-border bg-white">
                                                            @foreach($responsesByCategory[$category]->sortBy('item_number') as $response)
                                                                <tr class="hover:bg-muted/30 transition-colors">
                                                                    <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm font-medium text-foreground sm:pl-6">
                                                                        {{ $response->item_number }}
                                                                    </td>
                                                                    <td class="px-3 py-3 text-sm text-foreground/80">
                                                                        {{ $response->questionItem->prompt ?? 'Question prompt unavailable' }}
                                                                    </td>
                                                                    <td class="whitespace-nowrap px-3 py-3 text-sm font-bold text-foreground text-right sm:pr-6">
                                                                        @if(strtolower($category) === 'learning_style')
                                                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent border border-accent/20">
                                                                                {{ $response->response_value }}
                                                                            </span>
                                                                        @else
                                                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-muted text-foreground/80 border border-border shadow-sm">
                                                                                {{ $response->response_value }}
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
