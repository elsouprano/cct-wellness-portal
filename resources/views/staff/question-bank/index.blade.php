<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Question Bank') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-primary/10 border-l-4 border-primary p-4 rounded-r-lg mb-6">
                    <p class="text-sm text-primary font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg text-foreground border-b border-border flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-heading font-medium text-foreground">Current Academic Year: {{ $currentYear ? $currentYear->label : 'None' }}</h3>
                        <p class="text-sm text-foreground/70 mt-1">Manage questions for this academic year.</p>
                    </div>
                    @if($currentYear)
                    <a href="{{ route('question-bank.create') }}" class="btn-primary">Add Category</a>
                    @endif
                </div>
                
                @if(!$currentYear)
                    <div class="p-12 text-center text-foreground/50">
                        <svg class="mx-auto h-12 w-12 text-foreground/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Please set up an Academic Year first.
                    </div>
                @elseif($categories->isEmpty())
                    <div class="p-12 text-center text-foreground/50">
                        <svg class="mx-auto h-12 w-12 text-foreground/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        No question categories found for this academic year.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Order</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Name (Internal)</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Level</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-foreground/70 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-border">
                                @foreach($categories as $category)
                                    <tr class="hover:bg-muted/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">{{ $category->display_order }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-foreground">{{ $category->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">{{ $category->year_level }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">{{ $category->question_items_count }} items</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($category->is_locked)
                                                <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-destructive/10 text-destructive border border-destructive/20">
                                                    Locked
                                                </span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">
                                                    Editable
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @if(!$category->is_locked)
                                                <a href="{{ route('question-bank.edit', $category) }}" class="text-primary hover:text-primary/80 mr-4 font-semibold transition-colors">Edit</a>
                                                <form action="{{ route('question-bank.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-destructive hover:text-destructive/80 font-semibold transition-colors">Delete</button>
                                                </form>
                                            @else
                                                <span class="text-foreground/40 italic text-xs" title="Category is locked because there are submissions tied to it.">Locked (In Use)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
