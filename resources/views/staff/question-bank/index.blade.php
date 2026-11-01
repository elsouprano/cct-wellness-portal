@section('title', 'Question Bank')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Question Bank') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    Centralized Question Repository & Year-Level Assessment Inventories
                </p>
            </div>
            @if($currentYear)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-foreground/50 bg-white px-3 py-1.5 rounded-xl border border-border shadow-xs">
                        Academic Year: <span class="font-bold text-foreground">{{ $currentYear->label }}</span>
                    </span>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12" x-data="{ 
            activeSection: '{{ request('view', $activeView ?? 'assignments') }}',
            activeTab: '{{ request('year_level', $activeTab ?? '1st') }}',
            showAssignModal: false,
            assignYearLevelTarget: '1st',
            showQuickAddModal: false,
            showSyncModal: false,
            syncCategory: null,
            syncAssignedLevels: []
         }" x-cloak>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-start gap-2 shadow-xs">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        @if(session('error'))
                            <div>{{ session('error') }}</div>
                        @endif
                        @foreach($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Top View Mode Switcher -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="inline-flex p-1 bg-muted/60 rounded-2xl border border-border shadow-xs">
                    <button type="button" 
                            @click="activeSection = 'assignments'"
                            :class="activeSection === 'assignments' ? 'bg-white text-primary font-bold shadow-xs' : 'text-foreground/70 hover:text-foreground font-medium'"
                            class="px-5 py-2 rounded-xl text-xs transition-all">
                        📅 Year-Level Inventories
                    </button>
                    <button type="button" 
                            @click="activeSection = 'repository'"
                            :class="activeSection === 'repository' ? 'bg-white text-primary font-bold shadow-xs' : 'text-foreground/70 hover:text-foreground font-medium'"
                            class="px-5 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                        📦 Centralized Repository
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-primary/10 text-primary font-bold">
                            {{ $allRepositoryCategories->count() }}
                        </span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    @if($currentYear)
                        <button type="button" @click="showQuickAddModal = true" class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Category to Repository
                        </button>
                    @endif
                </div>
            </div>

            @if(!$currentYear)
                <div class="bg-white p-12 text-center text-foreground/50 rounded-3xl border border-border flex flex-col items-center justify-center">
                    <svg class="h-12 w-12 text-foreground/30 mb-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Please set up an Academic Year first to manage questions.
                </div>
            @else

                <!-- SECTION 1: YEAR-LEVEL ASSIGNMENTS -->
                <div x-show="activeSection === 'assignments'" class="space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                        <!-- Header & Tabs -->
                        <div class="p-6 border-b border-border bg-muted/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-heading font-bold text-foreground">
                                    Year-Level Question Inventories
                                </h3>
                                <p class="text-xs text-foreground/60 mt-0.5">
                                    Assign existing categories from the centralized repository to specific year levels. Removing a category here only removes the assignment.
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        @click="assignYearLevelTarget = activeTab; showAssignModal = true"
                                        class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-xs">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Assign Category to <span x-text="activeTab + ' Year'"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Year Tabs -->
                        <div class="border-b border-border px-6 bg-white">
                            <nav class="-mb-px flex space-x-8" aria-label="Year Level Tabs">
                                @foreach($yearLevels as $yl)
                                    @php
                                        $countAssigned = isset($assignedCategoriesByLevel[$yl]) ? $assignedCategoriesByLevel[$yl]->count() : 0;
                                    @endphp
                                    <button type="button"
                                            @click="activeTab = '{{ $yl }}'"
                                            :class="activeTab === '{{ $yl }}' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border font-medium'"
                                            class="whitespace-nowrap py-4 px-1 border-b-2 text-xs transition-colors focus:outline-none flex items-center gap-2">
                                        <span>{{ $yl }} Year</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ $countAssigned > 0 ? 'bg-primary/10 text-primary font-bold' : 'bg-muted text-foreground/50' }}">
                                            {{ $countAssigned }}
                                        </span>
                                    </button>
                                @endforeach
                            </nav>
                        </div>

                        <!-- Tab Content per Year Level -->
                        <div class="p-6">
                            @foreach($yearLevels as $yl)
                                @php
                                    $assignedCats = $assignedCategoriesByLevel[$yl] ?? collect();
                                    $totalQuestionsInYear = $assignedCats->sum('question_items_count');
                                @endphp

                                <div x-show="activeTab === '{{ $yl }}'" style="display: none;" class="space-y-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-foreground/60 pb-2 border-b border-border/50">
                                        <div>
                                            Currently Assigned to <strong>{{ $yl }} Year</strong>: 
                                            <span class="font-bold text-foreground">{{ $assignedCats->count() }} Categories</span> 
                                            ({{ $totalQuestionsInYear }} total questions)
                                        </div>
                                        <div class="text-[11px] text-foreground/50">
                                            Students in {{ $yl }} Year will answer these categories during their assessment.
                                        </div>
                                    </div>

                                    @if($assignedCats->isEmpty())
                                        <div class="py-12 text-center text-foreground/50 flex flex-col items-center justify-center space-y-3">
                                            <svg class="h-10 w-10 text-foreground/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            <p class="text-xs">No categories assigned to {{ $yl }} Year yet.</p>
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="assignYearLevelTarget = '{{ $yl }}'; showAssignModal = true" class="btn-primary text-xs py-1.5 px-3">
                                                    Assign Category from Repository
                                                </button>
                                                <a href="{{ route('question-bank.create') }}?year_level={{ $yl }}" class="btn-secondary text-xs py-1.5 px-3">
                                                    Create New Category
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-border">
                                                <thead class="bg-muted/30">
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase w-16">Order</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Category Name</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Questions</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Scale Type</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Also Assigned To</th>
                                                        <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Status</th>
                                                        <th class="px-4 py-3 text-right text-xs font-semibold text-foreground/70 uppercase">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-border bg-white text-xs">
                                                    @foreach($assignedCats as $category)
                                                        @php
                                                            $assignment = $category->yearLevelAssignments->firstWhere('year_level', $yl);
                                                            $otherLevels = array_diff($category->assigned_year_levels, [$yl]);
                                                        @endphp
                                                        <tr class="hover:bg-muted/20 transition-colors">
                                                            <td class="px-4 py-3 font-semibold text-foreground/70">
                                                                {{ $assignment?->display_order ?? $category->display_order }}
                                                            </td>
                                                            <td class="px-4 py-3 font-bold text-foreground">
                                                                <a href="{{ route('question-bank.edit', $category) }}" class="hover:text-primary transition-colors">
                                                                    {{ $category->name }}
                                                                </a>
                                                            </td>
                                                            <td class="px-4 py-3 text-foreground/80 font-medium">
                                                                <span class="inline-flex items-center gap-1 bg-muted px-2 py-0.5 rounded-lg border border-border">
                                                                    {{ $category->question_items_count }} items
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-foreground/70">
                                                                @if($category->scale_type === 'numeric_scale')
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-semibold">
                                                                        Numeric ({{ $category->scale_min }}–{{ $category->scale_max }})
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-[11px] font-semibold">
                                                                        Multiple Choice
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-foreground/60">
                                                                @if(!empty($otherLevels))
                                                                    <div class="flex items-center gap-1 flex-wrap">
                                                                        @foreach($otherLevels as $ol)
                                                                            <span class="px-1.5 py-0.5 rounded bg-muted/80 text-[10px] text-foreground/70 font-semibold border border-border">
                                                                                {{ $ol }} Year
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <span class="text-foreground/40 text-[11px]">Only {{ $yl }} Year</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                @if($category->is_locked)
                                                                    <span class="px-2 py-0.5 inline-flex text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                                                        Active Schedule
                                                                    </span>
                                                                @else
                                                                    <span class="px-2 py-0.5 inline-flex text-[11px] font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                        Editable
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                                                <div class="flex items-center justify-end gap-2">
                                                                    <a href="{{ route('question-bank.edit', $category) }}" class="text-primary hover:underline font-semibold text-xs">
                                                                        Manage Questions
                                                                    </a>

                                                                    <span class="text-foreground/30">&bull;</span>

                                                                    @if($assignment)
                                                                        <form method="POST" action="{{ route('question-bank.remove-year-level', $assignment) }}" class="inline" onsubmit="return confirm('Remove \'{{ addslashes($category->name) }}\' from {{ $yl }} Year? The category and all its questions will remain safely stored in the Centralized Repository.');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline font-semibold text-xs" title="Remove assignment without deleting category from repository">
                                                                                Remove from {{ $yl }} Year
                                                                            </button>
                                                                        </form>
                                                                    @else
                                                                        <span class="text-foreground/40 text-[11px]">Legacy Assigned</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: CENTRALIZED REPOSITORY -->
                <div x-show="activeSection === 'repository'" style="display: none;" class="space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                        <!-- Repository Header -->
                        <div class="p-6 border-b border-border bg-muted/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-heading font-bold text-foreground">
                                    Centralized Category & Question Repository
                                </h3>
                                <p class="text-xs text-foreground/60 mt-0.5">
                                    Permanent repository for all categories and questions. Stored once, assignable to multiple year levels without re-encoding.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <!-- Search Box -->
                                <form method="GET" action="{{ route('question-bank.index') }}" class="flex items-center gap-1">
                                    <input type="hidden" name="view" value="repository">
                                    <input type="text" name="search" value="{{ $search }}" placeholder="Search categories & questions..." class="text-xs rounded-xl border-border focus:ring-primary focus:border-primary py-1.5 px-3 w-56">
                                    <button type="submit" class="btn-secondary text-xs py-1.5 px-2.5">
                                        Search
                                    </button>
                                    @if(!empty($search))
                                        <a href="{{ route('question-bank.index', ['view' => 'repository']) }}" class="text-xs text-foreground/40 hover:text-foreground">Clear</a>
                                    @endif
                                </form>

                                <button type="button" @click="showQuickAddModal = true" class="btn-primary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-xs shrink-0">
                                    + Add Category
                                </button>
                            </div>
                        </div>

                        <!-- Repository Table -->
                        <div class="p-6">
                            @if($allRepositoryCategories->isEmpty())
                                <div class="py-12 text-center text-foreground/50 flex flex-col items-center justify-center space-y-2">
                                    <p class="text-xs font-medium">No categories found in repository matching your criteria.</p>
                                    <button type="button" @click="showQuickAddModal = true" class="btn-primary text-xs py-1.5 px-3">
                                        Create New Category
                                    </button>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-border">
                                        <thead class="bg-muted/30">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Category Name</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Scale Type</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Total Questions</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Assigned Year Levels</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/70 uppercase">Status</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold text-foreground/70 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border bg-white text-xs">
                                            @foreach($allRepositoryCategories as $category)
                                                @php
                                                    $assignedLevels = $category->assigned_year_levels;
                                                    $canDelete = $category->can_delete ?? !$category->is_locked;
                                                @endphp
                                                <tr class="hover:bg-muted/20 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="font-bold text-foreground">
                                                            <a href="{{ route('question-bank.edit', $category) }}" class="hover:text-primary transition-colors">
                                                                {{ $category->name }}
                                                            </a>
                                                        </div>
                                                        @if($category->instructions)
                                                            <div class="text-[11px] text-foreground/50 truncate max-w-xs mt-0.5">
                                                                {{ \Illuminate\Support\Str::limit($category->instructions, 60) }}
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3 text-foreground/70">
                                                        @if($category->scale_type === 'numeric_scale')
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-semibold">
                                                                Numeric ({{ $category->scale_min }}–{{ $category->scale_max }})
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-[11px] font-semibold">
                                                                Multiple Choice
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3 font-semibold text-foreground">
                                                        <span class="inline-flex items-center gap-1 bg-muted px-2.5 py-0.5 rounded-lg border border-border">
                                                            {{ $category->question_items_count }} items
                                                        </span>
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if(!empty($assignedLevels))
                                                            <div class="flex items-center gap-1 flex-wrap">
                                                                @foreach($assignedLevels as $al)
                                                                    <span class="px-2 py-0.5 rounded-md bg-primary/10 text-primary font-bold text-[10px] border border-primary/20">
                                                                        {{ $al }} Year
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-semibold text-[10px] border border-amber-200">
                                                                Repository Only (Unassigned)
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if($category->is_locked)
                                                            <span class="px-2 py-0.5 inline-flex text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                                                Active Schedule
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-0.5 inline-flex text-[11px] font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                Editable
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                                        <div class="flex items-center justify-end gap-2.5">
                                                            <a href="{{ route('question-bank.edit', $category) }}" class="text-primary hover:underline font-semibold text-xs">
                                                                Manage Questions
                                                            </a>

                                                            <span class="text-foreground/30">&bull;</span>

                                                            <button type="button" 
                                                                    @click="syncCategory = {{ json_encode(['id' => $category->id, 'name' => $category->name]); }}; syncAssignedLevels = {{ json_encode($assignedLevels); }}; showSyncModal = true"
                                                                    class="text-foreground/70 hover:text-foreground hover:underline font-medium text-xs">
                                                                Assign Year Levels
                                                            </button>

                                                            <span class="text-foreground/30">&bull;</span>

                                                            @if($canDelete)
                                                                <form action="{{ route('question-bank.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete \'{{ addslashes($category->name) }}\' and all its questions from the repository? This action cannot be undone.');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="text-rose-600 hover:text-rose-800 hover:underline font-semibold text-xs">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <span class="text-foreground/30 text-[11px] cursor-not-allowed" title="{{ $category->is_locked ? 'Locked by active assessment schedule.' : 'Protected: Category has submitted student responses.' }}">
                                                                    Protected
                                                                </span>
                                                            @endif
                                                        </div>
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

            @endif

        </div>

        <!-- MODAL 1: ASSIGN EXISTING CATEGORY TO YEAR LEVEL -->
        <div x-show="showAssignModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" @click.self="showAssignModal = false">
            <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-border text-left animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-border/60">
                    <h3 class="font-heading font-bold text-lg text-primary">
                        Assign Category to <span x-text="assignYearLevelTarget + ' Year'"></span>
                    </h3>
                    <button type="button" @click="showAssignModal = false" class="text-foreground/40 hover:text-foreground text-lg leading-none">&times;</button>
                </div>

                <form method="POST" action="{{ route('question-bank.assign-year-level') }}" class="space-y-4 pt-4">
                    @csrf
                    <input type="hidden" name="year_level" :value="assignYearLevelTarget">

                    <div>
                        <label class="block text-xs font-semibold text-foreground/70 mb-1.5">
                            Select Category from Centralized Repository
                        </label>
                        <select name="category_id" required class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                            <option value="" disabled selected>Choose a category...</option>
                            @foreach($allRepositoryCategories as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ $cat->name }} ({{ $cat->question_items_count }} items &bull; {{ $category->scale_type === 'numeric_scale' ? 'Numeric' : 'Multiple Choice' }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-foreground/50 mt-1 block">
                            Categories can be assigned to multiple year levels without re-encoding questions.
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-foreground/70 mb-1.5">
                            Display Order in <span x-text="assignYearLevelTarget + ' Year'"></span> (Optional)
                        </label>
                        <input type="number" name="display_order" placeholder="Auto-assigned if left blank" class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 bg-muted text-foreground/70 text-xs font-semibold rounded-xl hover:bg-muted/80">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-xs py-2 px-4 shadow-xs font-bold">
                            Assign to <span x-text="assignYearLevelTarget + ' Year'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: QUICK ADD CATEGORY TO REPOSITORY -->
        <div x-show="showQuickAddModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" @click.self="showQuickAddModal = false" x-data="{ scaleType: 'numeric_scale' }">
            <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-border text-left animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-border/60">
                    <h3 class="font-heading font-bold text-lg text-primary">
                        Add Category to Centralized Repository
                    </h3>
                    <button type="button" @click="showQuickAddModal = false" class="text-foreground/40 hover:text-foreground text-lg leading-none">&times;</button>
                </div>

                <form method="POST" action="{{ route('question-bank.store') }}" class="space-y-4 pt-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-foreground/70 mb-1">Category Name</label>
                        <input type="text" name="name" required placeholder="e.g., Emotional Regulation, DASS-21, CAT..." class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-foreground/70 mb-1">Scale Type</label>
                            <select name="scale_type" x-model="scaleType" required class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                                <option value="numeric_scale">Numeric Scale</option>
                                <option value="multiple_choice_unscored">Multiple Choice (Unscored)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-foreground/70 mb-1">Display Order</label>
                            <input type="number" name="display_order" value="1" class="w-full text-xs rounded-xl border-border py-2 px-3 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <!-- Numeric Scale Min / Max -->
                    <template x-if="scaleType === 'numeric_scale'">
                        <div class="grid grid-cols-2 gap-3 p-3 bg-muted/30 rounded-xl border border-border">
                            <div>
                                <label class="block text-xs font-semibold text-foreground/70 mb-1">Scale Minimum</label>
                                <input type="number" name="scale_min" value="0" class="w-full text-xs rounded-xl border-border py-1.5 px-3 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-foreground/70 mb-1">Scale Maximum</label>
                                <input type="number" name="scale_max" value="3" class="w-full text-xs rounded-xl border-border py-1.5 px-3 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </template>

                    <!-- Multiple choice default options -->
                    <template x-if="scaleType === 'multiple_choice_unscored'">
                        <div class="p-3 bg-muted/30 rounded-xl border border-border">
                            <label class="block text-xs font-semibold text-foreground/70 mb-1">Default Options (One per line)</label>
                            <textarea name="default_options" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3" class="w-full text-xs rounded-xl border-border p-2 focus:ring-primary focus:border-primary"></textarea>
                        </div>
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-foreground/70 mb-1">Instructions (Optional)</label>
                        <textarea name="instructions" rows="2" placeholder="Student guidance or category instructions..." class="w-full text-xs rounded-xl border-border p-2 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <!-- Initial Year Level Assignment Checkboxes -->
                    <div>
                        <label class="block text-xs font-semibold text-foreground/70 mb-1.5">Assign to Year Levels (Optional)</label>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($yearLevels as $yl)
                                <label class="flex items-center gap-1.5 p-2 rounded-xl border border-border hover:bg-muted/50 cursor-pointer text-xs">
                                    <input type="checkbox" name="year_levels[]" value="{{ $yl }}" class="rounded text-primary focus:ring-primary">
                                    <span>{{ $yl }} Year</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-border">
                        <a href="{{ route('question-bank.create') }}" class="text-xs text-primary hover:underline font-semibold">
                            Open Full Form with Questions &rarr;
                        </a>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="showQuickAddModal = false" class="px-3.5 py-2 bg-muted text-foreground/70 text-xs font-semibold rounded-xl hover:bg-muted/80">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary text-xs py-2 px-4 shadow-xs font-bold">
                                Save to Repository
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: QUICK SYNC YEAR LEVELS FOR A CATEGORY -->
        <div x-show="showSyncModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs" @click.self="showSyncModal = false">
            <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-border text-left animate-in fade-in zoom-in-95 duration-200" x-show="syncCategory">
                <div class="flex items-center justify-between pb-3 border-b border-border/60">
                    <h3 class="font-heading font-bold text-lg text-primary">
                        Assign Year Levels
                    </h3>
                    <button type="button" @click="showSyncModal = false" class="text-foreground/40 hover:text-foreground text-lg leading-none">&times;</button>
                </div>

                <form method="POST" :action="'/manage/question-bank/sync-assignments/' + (syncCategory ? syncCategory.id : '')" class="space-y-4 pt-4">
                    @csrf

                    <div>
                        <p class="text-xs text-foreground/70 mb-3">
                            Select which year levels will include category <strong x-text="syncCategory ? syncCategory.name : ''"></strong>:
                        </p>

                        <div class="space-y-2">
                            @foreach($yearLevels as $yl)
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-border hover:bg-muted/30 cursor-pointer text-xs font-medium">
                                    <input type="checkbox" name="year_levels[]" value="{{ $yl }}" :checked="syncAssignedLevels.includes('{{ $yl }}')" class="rounded text-primary focus:ring-primary">
                                    <div>
                                        <div class="font-bold text-foreground">{{ $yl }} Year</div>
                                        <div class="text-[11px] text-foreground/50">Students in {{ $yl }} Year will receive this assessment section</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <button type="button" @click="showSyncModal = false" class="px-4 py-2 bg-muted text-foreground/70 text-xs font-semibold rounded-xl hover:bg-muted/80">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-xs py-2 px-4 shadow-xs font-bold">
                            Save Year Assignments
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-staff-layout>
