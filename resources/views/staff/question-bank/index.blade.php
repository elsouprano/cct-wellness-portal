@section('title', 'Question Bank')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div class="min-w-0">
                <h1 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Question Bank') }}
                </h1>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Centralized Question Repository &amp; Year-Level Assessment Inventories
                </p>
            </div>
            @if($currentYear)
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-muted-foreground font-medium">
                        Academic Year: <span class="font-bold text-primary">{{ $currentYear->label }}</span>
                    </span>
                </div>
            @endif
        </div>
    </x-slot>

    @php
        $safeActiveSection = in_array(request('view', $activeView ?? 'assignments'), ['assignments', 'repository'], true)
            ? request('view', $activeView ?? 'assignments')
            : 'assignments';
        $safeActiveTab = in_array(request('year_level', $activeTab ?? '1st'), ['1st', '2nd', '3rd', '4th'], true)
            ? request('year_level', $activeTab ?? '1st')
            : '1st';

        $formatUsedIn = function (array $levels): string {
            if (empty($levels)) {
                return 'Not assigned';
            }
            if (count($levels) === 1) {
                return 'Used in: ' . $levels[0] . ' year only';
            }
            return 'Used in: ' . implode(', ', $levels) . ' year';
        };
    @endphp

    <div class="space-y-6" x-data="{
            activeSection: @js($safeActiveSection),
            activeTab: @js($safeActiveTab),
            showAssignModal: false,
            assignYearLevelTarget: @js($safeActiveTab),
            showSyncModal: false,
            syncCategory: null,
            syncAssignedLevels: []
         }" x-cloak>

        <!-- Flash Status Messages -->
        @if(session('success'))
            <div class="p-4 rounded-token-lg bg-success-subtle border border-success-border text-success-foreground text-sm font-medium flex items-center gap-2.5 shadow-2xs" role="status">
                <svg class="w-5 h-5 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="break-words min-w-0">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="p-4 rounded-token-lg bg-destructive-subtle border border-destructive-border text-destructive-foreground text-sm font-medium flex items-start gap-2.5 shadow-2xs" role="alert">
                <svg class="w-5 h-5 text-destructive shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="min-w-0 break-words">
                    @if(session('error'))
                        <div>{{ session('error') }}</div>
                    @endif
                    @foreach($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Top Switcher + SINGLE Primary Maroon Action per Area -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="inline-flex flex-wrap p-1 bg-muted/70 rounded-token-lg border border-border shadow-2xs">
                <button type="button"
                        @click="activeSection = 'assignments'"
                        :class="activeSection === 'assignments' ? 'bg-surface text-primary font-bold shadow-2xs' : 'text-muted-foreground hover:text-foreground font-medium'"
                        class="min-h-[44px] px-3.5 py-2 rounded-token-md text-xs sm:text-sm transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                    <x-icon name="calendar" class="w-4 h-4 shrink-0" />
                    <span>Year-Level Inventories</span>
                </button>
                <button type="button"
                        @click="activeSection = 'repository'"
                        :class="activeSection === 'repository' ? 'bg-surface text-primary font-bold shadow-2xs' : 'text-muted-foreground hover:text-foreground font-medium'"
                        class="min-h-[44px] px-3.5 py-2 rounded-token-md text-xs sm:text-sm transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                    <x-icon name="archive-box" class="w-4 h-4 shrink-0" />
                    <span>Centralized Repository</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] bg-primary/10 text-primary font-bold tabular-nums">
                        {{ $allRepositoryCategories->count() }}
                    </span>
                </button>
            </div>

            @if($currentYear)
                <div class="shrink-0">
                    <!-- Year-Level Tab: Single "Assign category" primary button -->
                    <button type="button"
                            x-show="activeSection === 'assignments'"
                            @click="assignYearLevelTarget = activeTab; showAssignModal = true"
                            class="btn-primary text-sm px-4 py-2 w-full sm:w-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Assign category</span>
                    </button>

                    <!-- Repository Tab: Single "New category" primary button -->
                    <a href="{{ route('question-bank.create') }}"
                       x-show="activeSection === 'repository'"
                       x-cloak
                       class="btn-primary text-sm px-4 py-2 w-full sm:w-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New category</span>
                    </a>
                </div>
            @endif
        </div>

        @if(!$currentYear)
            <div class="bg-surface p-8 sm:p-12 text-center text-muted-foreground rounded-token-lg border border-border flex flex-col items-center justify-center">
                <svg class="h-12 w-12 text-muted-foreground/40 mb-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-medium">Please set up an Academic Year first to manage questions.</p>
            </div>
        @else

            <!-- ==================== TAB 1: YEAR-LEVEL INVENTORIES ==================== -->
            <div x-show="activeSection === 'assignments'" class="space-y-6">
                <div class="bg-surface shadow-xs rounded-token-lg border border-border">
                    <!-- Section Intro -->
                    <div class="p-4 sm:p-6 border-b border-border bg-surface-subtle/50">
                        <h2 class="text-base sm:text-lg font-heading font-bold text-foreground">
                            Year-Level Question Inventories
                        </h2>
                        <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                            Assign instruments from the centralized repository to each year level. Removing a category from a year level keeps its questions safe in the repository.
                        </p>
                    </div>

                    <!-- Year-Level Tabs -->
                    <div class="border-b border-border px-4 sm:px-6 bg-surface">
                        <nav class="flex flex-wrap gap-x-4 sm:gap-x-6 gap-y-1" aria-label="Year Level Tabs">
                            @foreach($yearLevels as $yl)
                                @php
                                    $countAssigned = isset($assignedCategoriesByLevel[$yl]) ? $assignedCategoriesByLevel[$yl]->count() : 0;
                                @endphp
                                <button type="button"
                                        @click="activeTab = '{{ $yl }}'; assignYearLevelTarget = '{{ $yl }}'"
                                        :class="activeTab === '{{ $yl }}' ? 'border-primary text-primary font-bold' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border font-medium'"
                                        class="min-h-[44px] whitespace-nowrap py-2.5 px-1 border-b-2 text-xs sm:text-sm transition-colors focus:outline-none inline-flex items-center gap-2 cursor-pointer">
                                    <span>{{ $yl }} Year</span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] tabular-nums {{ $countAssigned > 0 ? 'bg-primary/10 text-primary font-bold' : 'bg-muted text-muted-foreground' }}">
                                        {{ $countAssigned }}
                                    </span>
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Per-Year Content -->
                    <div class="p-4 sm:p-6">
                        @foreach($yearLevels as $yl)
                            @php
                                $assignedCats = ($assignedCategoriesByLevel[$yl] ?? collect())->values();
                                $totalQuestionsInYear = $assignedCats->sum('question_items_count');
                            @endphp

                            <div x-show="activeTab === '{{ $yl }}'" x-cloak class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-xs text-muted-foreground pb-2 border-b border-border/60">
                                    <div>
                                        Assigned to <strong class="text-foreground">{{ $yl }} Year</strong>:
                                        <span class="font-bold text-foreground">{{ $assignedCats->count() }} {{ Str::plural('category', $assignedCats->count()) }}</span>
                                        ({{ $totalQuestionsInYear }} {{ Str::plural('question', $totalQuestionsInYear) }})
                                    </div>
                                    <div>
                                        Students in {{ $yl }} Year answer these sections during their open schedule.
                                    </div>
                                </div>

                                @if($assignedCats->isEmpty())
                                    <div class="py-10 text-center text-muted-foreground flex flex-col items-center justify-center space-y-2">
                                        <p class="text-sm font-medium text-foreground">No categories assigned to {{ $yl }} Year yet.</p>
                                        <p class="text-xs">Use the <strong>Assign category</strong> button above to assign an instrument from the repository.</p>
                                    </div>
                                @else
                                    <!-- Clean Responsive Row List (Zero Horizontal Scroll, Quiet Actions + ⋯ Menu) -->
                                    <div class="divide-y divide-border rounded-token-lg border border-border bg-surface">
                                        @foreach($assignedCats as $category)
                                            @php
                                                $assignment = $category->yearLevelAssignments->firstWhere('year_level', $yl);
                                                $assignedAll = $category->assigned_year_levels;
                                                $sharingLabel = count($assignedAll) <= 1
                                                    ? "{$yl} year only"
                                                    : 'Shared: ' . implode(', ', $assignedAll) . ' year';
                                                $openUpward = $loop->remaining < 2 && $assignedCats->count() > 2;
                                            @endphp
                                            <div class="p-3.5 sm:px-5 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-4 hover:bg-muted/25 transition-colors">
                                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                                    <span class="shrink-0 w-7 h-7 rounded-full bg-surface-subtle border border-border text-xs font-bold text-muted-foreground flex items-center justify-center tabular-nums mt-0.5">
                                                        {{ $assignment?->display_order ?? $category->display_order }}
                                                    </span>
                                                    <div class="min-w-0 flex-1">
                                                        <a href="{{ route('question-bank.edit', $category) }}"
                                                           @if($category->name === 'rapid_headsss') title="Domains: Home, Education/Employment, Activities, Drugs, Sexuality, Suicide/Depression, Safety" @endif
                                                           class="font-heading font-semibold text-sm sm:text-base text-foreground hover:text-primary hover:underline transition-colors break-words inline-block">
                                                            {{ $category->label() }}
                                                        </a>
                                                        <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-muted-foreground">
                                                            <span class="font-mono text-[11px] bg-muted px-1.5 py-0.5 rounded">{{ $category->name }}</span>
                                                            <span>&middot;</span>
                                                            <span class="tabular-nums">{{ $category->question_items_count }} {{ Str::plural('question', $category->question_items_count) }}</span>
                                                            <span>&middot;</span>
                                                            <span>{{ $category->scale_type === 'numeric_scale' ? "Scale ({$category->scale_min}–{$category->scale_max})" : 'Multiple choice' }}</span>
                                                            <span>&middot;</span>
                                                            <span class="font-medium text-foreground/80">{{ $sharingLabel }}</span>
                                                            <span>&middot;</span>
                                                            @if($category->is_locked)
                                                                <span class="inline-flex items-center gap-1 text-destructive font-medium">
                                                                    <span class="w-1.5 h-1.5 rounded-full bg-destructive"></span>
                                                                    Active schedule
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 text-success-foreground font-medium">
                                                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                                                    Editable
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Row Actions: Quiet "Manage" text link + "⋯" menu -->
                                                <div class="flex items-center justify-end gap-1 shrink-0 self-end sm:self-center" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
                                                    <a href="{{ route('question-bank.edit', $category) }}"
                                                       class="min-h-[44px] px-3 inline-flex items-center text-xs sm:text-sm font-semibold text-primary hover:underline rounded-token-md hover:bg-primary-subtle/50 transition-colors">
                                                        Manage
                                                    </a>

                                                    @if($assignment)
                                                        <div class="relative">
                                                            <button type="button"
                                                                    @click="menuOpen = !menuOpen"
                                                                    :aria-expanded="menuOpen.toString()"
                                                                    aria-label="More actions for {{ $category->label() }}"
                                                                    class="min-w-[44px] min-h-[44px] inline-flex items-center justify-center rounded-token-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors cursor-pointer font-bold text-lg leading-none">
                                                                &cdots;
                                                            </button>

                                                            <div x-show="menuOpen"
                                                                 x-cloak
                                                                 @click.outside="menuOpen = false"
                                                                 class="absolute right-0 {{ $openUpward ? 'bottom-full mb-1' : 'top-full mt-1' }} z-30 w-52 bg-surface border border-border rounded-token-md shadow-lg p-1">
                                                                <form method="POST"
                                                                      action="{{ route('question-bank.remove-year-level', $assignment) }}"
                                                                      data-confirm="delete"
                                                                      data-confirm-title="Remove from {{ $yl }} Year?"
                                                                      data-confirm-message="Remove '{{ addslashes($category->label()) }}' from {{ $yl }} Year? Its questions stay safely stored in the Centralized Repository.">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                            @click="menuOpen = false"
                                                                            class="w-full min-h-[44px] px-3 py-2 text-left text-xs sm:text-sm font-medium text-destructive hover:bg-destructive-subtle rounded-token-sm transition-colors cursor-pointer">
                                                                        Remove from {{ $yl }} year
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ==================== TAB 2: CENTRALIZED REPOSITORY ==================== -->
            <div x-show="activeSection === 'repository'" x-cloak class="space-y-6">
                <div class="bg-surface shadow-xs rounded-token-lg border border-border">
                    <!-- Repository Header + Search -->
                    <div class="p-4 sm:p-6 border-b border-border bg-surface-subtle/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="text-base sm:text-lg font-heading font-bold text-foreground">
                                Centralized Category &amp; Question Repository
                            </h2>
                            <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                                Each assessment instrument is stored once and assigned across year levels without duplicating questions.
                            </p>
                        </div>

                        <search class="w-full md:w-auto">
                            <form method="GET" action="{{ route('question-bank.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                                <input type="hidden" name="view" value="repository">
                                <label for="qb-repo-search" class="sr-only">Search categories and questions</label>
                                <input id="qb-repo-search"
                                       type="search"
                                       name="search"
                                       value="{{ $search }}"
                                       placeholder="Search categories or questions..."
                                       class="input-field text-xs sm:text-sm py-2 px-3 flex-1 min-w-0 sm:w-64">
                                <button type="submit" class="btn-secondary text-xs sm:text-sm px-4 py-2 shrink-0">
                                    Search
                                </button>
                                @if(!empty($search))
                                    <a href="{{ route('question-bank.index', ['view' => 'repository']) }}" class="min-h-[44px] px-2 inline-flex items-center text-xs font-semibold text-muted-foreground hover:text-foreground">
                                        Clear
                                    </a>
                                @endif
                            </form>
                        </search>
                    </div>

                    <!-- Repository List -->
                    <div class="p-4 sm:p-6">
                        @if($allRepositoryCategories->isEmpty())
                            <div class="py-10 text-center text-muted-foreground space-y-2">
                                <p class="text-sm font-medium text-foreground">No categories found in the repository.</p>
                                <p class="text-xs">Try clearing your search filter or use the <strong>New category</strong> button above.</p>
                            </div>
                        @else
                            <div x-data="clientPaginator({ total: {{ $allRepositoryCategories->count() }}, defaultLimit: 10, pageParam: 'page', limitParam: 'limit' })"
                                 class="rounded-token-lg border border-border bg-surface">
                                <div class="divide-y divide-border">
                                    @foreach($allRepositoryCategories as $category)
                                        @php
                                            $assignedLevels = $category->assigned_year_levels;
                                            $canDelete = $category->can_delete ?? !$category->is_locked;
                                            $usedInText = $formatUsedIn($assignedLevels);
                                            $openUpward = $loop->remaining < 2 && $allRepositoryCategories->count() > 2;
                                        @endphp
                                        <div x-show="isVisible({{ $loop->index }})"
                                             class="p-3.5 sm:px-5 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-4 hover:bg-muted/25 transition-colors">
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ route('question-bank.edit', $category) }}"
                                                   class="font-heading font-semibold text-sm sm:text-base text-foreground hover:text-primary hover:underline transition-colors break-words inline-block">
                                                    {{ $category->label() }}
                                                </a>
                                                <div class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-muted-foreground">
                                                    <span class="font-mono text-[11px] bg-muted px-1.5 py-0.5 rounded">{{ $category->name }}</span>
                                                    <span>&middot;</span>
                                                    <span class="tabular-nums">{{ $category->question_items_count }} {{ Str::plural('question', $category->question_items_count) }}</span>
                                                    <span>&middot;</span>
                                                    <span>{{ $category->scale_type === 'numeric_scale' ? "Scale ({$category->scale_min}–{$category->scale_max})" : 'Multiple choice' }}</span>
                                                    <span>&middot;</span>
                                                    <span class="font-medium {{ empty($assignedLevels) ? 'italic text-muted-foreground' : 'text-foreground/85' }}">{{ $usedInText }}</span>
                                                </div>
                                            </div>

                                            <!-- Row Actions: Quiet "Manage" text link + "⋯" menu -->
                                            <div class="flex items-center justify-end gap-1 shrink-0 self-end sm:self-center" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
                                                <a href="{{ route('question-bank.edit', $category) }}"
                                                   class="min-h-[44px] px-3 inline-flex items-center text-xs sm:text-sm font-semibold text-primary hover:underline rounded-token-md hover:bg-primary-subtle/50 transition-colors">
                                                    Manage
                                                </a>

                                                <div class="relative">
                                                    <button type="button"
                                                            @click="menuOpen = !menuOpen"
                                                            :aria-expanded="menuOpen.toString()"
                                                            aria-label="More actions for {{ $category->label() }}"
                                                            class="min-w-[44px] min-h-[44px] inline-flex items-center justify-center rounded-token-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors cursor-pointer font-bold text-lg leading-none">
                                                        &cdots;
                                                    </button>

                                                    <div x-show="menuOpen"
                                                         x-cloak
                                                         @click.outside="menuOpen = false"
                                                         class="absolute right-0 {{ $openUpward ? 'bottom-full mb-1' : 'top-full mt-1' }} z-30 w-52 bg-surface border border-border rounded-token-md shadow-lg p-1 space-y-0.5">
                                                        <button type="button"
                                                                @click="menuOpen = false; syncCategory = {{ json_encode(['id' => $category->id, 'name' => $category->label()]) }}; syncAssignedLevels = {{ json_encode($assignedLevels) }}; showSyncModal = true"
                                                                class="w-full min-h-[44px] px-3 py-2 text-left text-xs sm:text-sm font-medium text-foreground hover:bg-muted rounded-token-sm transition-colors cursor-pointer">
                                                            Edit year levels
                                                        </button>

                                                        @if($canDelete)
                                                            <form action="{{ route('question-bank.destroy', $category) }}"
                                                                  method="POST"
                                                                  data-confirm="delete"
                                                                  data-confirm-title="Delete Category?"
                                                                  data-confirm-message="Permanently delete '{{ addslashes($category->label()) }}' and its questions from the repository? This action cannot be undone.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        @click="menuOpen = false"
                                                                        class="w-full min-h-[44px] px-3 py-2 text-left text-xs sm:text-sm font-medium text-destructive hover:bg-destructive-subtle rounded-token-sm transition-colors cursor-pointer">
                                                                    Delete from repository
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="border-t border-border bg-muted/20">
                                    <x-pagination mode="client" label="Question bank repository pagination" />
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        @endif

        <!-- MODAL 1: ASSIGN EXISTING CATEGORY TO YEAR LEVEL -->
        <div x-show="showAssignModal"
             x-cloak
             role="dialog"
             aria-modal="true"
             aria-labelledby="assign-modal-title"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-foreground/50"
             @click.self="showAssignModal = false"
             @keydown.escape.window="showAssignModal = false">
            <div class="bg-surface rounded-2xl p-5 sm:p-6 max-w-lg w-full max-h-[90dvh] overflow-y-auto shadow-xl border border-border text-left">
                <div class="flex items-center justify-between pb-3 border-b border-border">
                    <h3 id="assign-modal-title" class="font-heading font-bold text-base sm:text-lg text-primary">
                        Assign Category to <span x-text="assignYearLevelTarget + ' Year'"></span>
                    </h3>
                    <button type="button" @click="showAssignModal = false" aria-label="Close dialog" class="min-w-[44px] min-h-[44px] inline-flex items-center justify-center rounded-token-md text-muted-foreground hover:text-foreground hover:bg-muted text-xl leading-none cursor-pointer">&times;</button>
                </div>

                <form method="POST" action="{{ route('question-bank.assign-year-level') }}" data-confirm="save" data-confirm-title="Assign Category" class="space-y-4 pt-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="assign_year_select" class="block text-xs font-semibold text-foreground mb-1.5 label-required">
                                Year Level
                            </label>
                            <select id="assign_year_select" name="year_level" x-model="assignYearLevelTarget" required class="input-field w-full text-sm">
                                @foreach($yearLevels as $yl)
                                    <option value="{{ $yl }}">{{ $yl }} Year</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="assign_category_select" class="block text-xs font-semibold text-foreground mb-1.5 label-required">
                                Select Category from Repository
                            </label>
                            <select id="assign_category_select" name="category_id" required class="input-field w-full text-sm">
                                <option value="" disabled selected>Choose a category...</option>
                                @foreach($allRepositoryCategories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->label() }} ({{ $cat->question_items_count }} questions)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="assign_display_order" class="block text-xs font-semibold text-foreground mb-1.5">
                                Display Order (Optional)
                            </label>
                            <input id="assign_display_order" type="number" min="1" name="display_order" placeholder="Auto-assigned if left blank" class="input-field w-full text-sm">
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-3 border-t border-border">
                        <button type="button" @click="showAssignModal = false" class="btn-secondary text-sm px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-sm px-4 py-2">
                            Assign category
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: SYNC YEAR LEVELS FOR A REPOSITORY CATEGORY -->
        <div x-show="showSyncModal"
             x-cloak
             role="dialog"
             aria-modal="true"
             aria-labelledby="sync-modal-title"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-foreground/50"
             @click.self="showSyncModal = false"
             @keydown.escape.window="showSyncModal = false">
            <div class="bg-surface rounded-2xl p-5 sm:p-6 max-w-md w-full max-h-[90dvh] overflow-y-auto shadow-xl border border-border text-left" x-show="syncCategory">
                <div class="flex items-center justify-between pb-3 border-b border-border">
                    <h3 id="sync-modal-title" class="font-heading font-bold text-base sm:text-lg text-primary">
                        Edit Year Levels
                    </h3>
                    <button type="button" @click="showSyncModal = false" aria-label="Close dialog" class="min-w-[44px] min-h-[44px] inline-flex items-center justify-center rounded-token-md text-muted-foreground hover:text-foreground hover:bg-muted text-xl leading-none cursor-pointer">&times;</button>
                </div>

                <form method="POST" :action="'/manage/question-bank/sync-assignments/' + (syncCategory ? syncCategory.id : '')" data-confirm="save" data-confirm-title="Save Year Level Assignments" class="space-y-4 pt-4">
                    @csrf
                    <p class="text-xs sm:text-sm text-muted-foreground">
                        Choose which year levels include <strong class="text-foreground" x-text="syncCategory ? syncCategory.name : ''"></strong>:
                    </p>

                    <div class="space-y-2">
                        @foreach($yearLevels as $yl)
                            <label class="min-h-[48px] flex items-center gap-3 p-3 rounded-token-md border border-border hover:bg-muted/40 cursor-pointer text-sm font-medium">
                                <input type="checkbox" name="year_levels[]" value="{{ $yl }}" :checked="syncAssignedLevels.includes('{{ $yl }}')" class="w-5 h-5 rounded text-primary focus:ring-primary shrink-0">
                                <span>{{ $yl }} Year</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-3 border-t border-border">
                        <button type="button" @click="showSyncModal = false" class="btn-secondary text-sm px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-sm px-4 py-2">
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-staff-layout>
