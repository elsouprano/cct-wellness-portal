@section('title')Assessment Details ({{ $submission->academic_year }})@endsection
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <h1 class="font-heading font-semibold text-lg sm:text-xl text-foreground leading-tight">
                    Assessment History
                </h1>
                <p class="text-muted-foreground text-xs mt-0.5 break-words">
                    Submitted on {{ $submission->submitted_at->format('F j, Y \a\t g:i A') }} &middot; A.Y. {{ $submission->academic_year }}
                </p>
            </div>
            <a href="{{ route('inventory.history') }}" class="btn-secondary min-h-[38px] text-xs sm:text-sm px-3.5 py-1.5 inline-flex items-center gap-2 self-start sm:self-auto shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 shrink-0" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Back to History
            </a>
        </div>
    </x-slot>

    @php
        $responsesByCategory = $submission->responses->groupBy('category');

        // Build ordered section keys based on QuestionCategory display_order, appending any extra response keys
        $orderedCategoryKeys = [];
        $configByName = [];
        foreach ($inventoryConfig as $cfg) {
            if (! isset($configByName[$cfg->name])) {
                $configByName[$cfg->name] = $cfg;
            }
            if ($responsesByCategory->has($cfg->name) && ! in_array($cfg->name, $orderedCategoryKeys, true)) {
                $orderedCategoryKeys[] = $cfg->name;
            }
        }
        foreach ($responsesByCategory->keys() as $respKey) {
            if (! in_array($respKey, $orderedCategoryKeys, true)) {
                $orderedCategoryKeys[] = $respKey;
            }
        }

        $totalSections = count($orderedCategoryKeys);
        $firstCategory = $orderedCategoryKeys[0] ?? null;

        $categoryScaleFallback = function (?string $name, ?\App\Models\QuestionCategory $catConfig): array {
            if (! $catConfig || $catConfig->scale_min === null || $catConfig->scale_max === null) {
                return [];
            }
            $labels = is_array($catConfig->scale_labels) ? $catConfig->scale_labels : [];
            if (! empty($labels)) {
                return $labels;
            }
            return match (strtolower((string) $name)) {
                'dass21', 'dass-21' => [0 => 'Did not apply', 1 => 'Some degree', 2 => 'Considerable', 3 => 'Very much'],
                'erq' => [1 => 'Strongly disagree', 4 => 'Neutral', 7 => 'Strongly agree'],
                'ars30' => [1 => 'Strongly agree', 2 => 'Agree', 3 => 'Neither agree nor disagree', 4 => 'Disagree', 5 => 'Strongly disagree'],
                'cat' => [1 => 'not at all', 4 => 'somewhat', 7 => 'a great deal'],
                'ffmq' => [1 => 'Never / rarely true', 2 => 'Rarely true', 3 => 'Sometimes true', 4 => 'Often true', 5 => 'Very often / always'],
                default => [],
            };
        };

        // Precompute per-section display mode (Matrix Table vs Custom Choice Cards) and zero-scroll page sizes
        $sectionMetaList = [];
        $sectionConfigs = [];

        foreach ($orderedCategoryKeys as $idx => $catKey) {
            $cfg = $configByName[$catKey] ?? null;
            $catResponses = $responsesByCategory[$catKey]->sortBy('item_number')->values();
            $itemCount = $catResponses->count();
            $scaleLabels = $categoryScaleFallback($catKey, $cfg);

            $hasNumericScale = $cfg
                && $cfg->scale_min !== null
                && $cfg->scale_max !== null
                && ! in_array($cfg->scale_type, ['multiple_choice_unscored', 'multiple_choice'], true);

            $categoryDefaultOpts = is_array($cfg?->default_options) ? array_values($cfg->default_options) : [];

            // Check whether every item in a multiple-choice section shares uniform short options suitable for a clean matrix header
            $hasUniformShortMc = false;
            if (! $hasNumericScale && ! empty($categoryDefaultOpts) && count($categoryDefaultOpts) >= 2 && count($categoryDefaultOpts) <= 5) {
                $maxDefLen = max(array_map(static fn ($o) => mb_strlen((string) $o), $categoryDefaultOpts));
                $allShareDefault = true;
                if ($maxDefLen <= 18) {
                    foreach ($catResponses as $rItem) {
                        $qItem = $cfg?->questionItems->firstWhere('item_number', $rItem->item_number);
                        $qOpts = ! empty($qItem?->options) ? array_values($qItem->options) : $categoryDefaultOpts;
                        $allowsMulti = $qItem ? $qItem->allowsMultiple($cfg) : false;
                        if ($allowsMulti || $qOpts !== $categoryDefaultOpts) {
                            $allShareDefault = false;
                            break;
                        }
                    }
                    $hasUniformShortMc = $allShareDefault;
                }
            }

            $isMatrix = $hasNumericScale || $hasUniformShortMc;
            $matrixCols = [];

            if ($hasNumericScale) {
                for ($sVal = (int) $cfg->scale_min; $sVal <= (int) $cfg->scale_max; $sVal++) {
                    $matrixCols[] = [
                        'value' => (string) $sVal,
                        'badge' => (string) $sVal,
                        'label' => $scaleLabels[$sVal] ?? $scaleLabels[(string) $sVal] ?? null,
                    ];
                }
            } elseif ($hasUniformShortMc) {
                foreach ($categoryDefaultOpts as $oIdx => $oText) {
                    $matrixCols[] = [
                        'value' => (string) $oText,
                        'badge' => (string) ($oIdx + 1),
                        'label' => (string) $oText,
                    ];
                }
            }

            // Compute zero-scroll page size based on prompt lengths & row density
            $maxPromptLen = 0;
            $maxCustomOpts = 0;
            $maxCustomOptLen = 0;
            foreach ($catResponses as $rItem) {
                $qItem = $cfg?->questionItems->firstWhere('item_number', $rItem->item_number);
                $maxPromptLen = max($maxPromptLen, mb_strlen((string) ($qItem?->prompt ?? '')));
                $opts = ! empty($qItem?->options) ? $qItem->options : $categoryDefaultOpts;
                if (is_array($opts) && ! empty($opts)) {
                    $maxCustomOpts = max($maxCustomOpts, count($opts));
                    foreach ($opts as $o) {
                        $maxCustomOptLen = max($maxCustomOptLen, mb_strlen((string) $o));
                    }
                }
            }

            if ($isMatrix) {
                $perPage = match (true) {
                    $maxPromptLen > 200 => 4,
                    $maxPromptLen > 115 => 7,
                    $itemCount <= 10 => 10,
                    default => 8,
                };
            } else {
                $perPage = ($maxCustomOptLen >= 60 || $maxCustomOpts >= 10) ? 3 : 4;
            }

            $sectionMetaList[] = [
                'key' => $catKey,
                'num' => $idx + 1,
                'label' => \App\Support\InstrumentLabel::for($catKey, $cfg),
                'count' => $itemCount,
                'perPage' => $perPage,
                'totalPages' => max(1, (int) ceil($itemCount / $perPage)),
            ];

            $sectionConfigs[$catKey] = [
                'cfg' => $cfg,
                'responses' => $catResponses,
                'scaleLabels' => $scaleLabels,
                'hasNumericScale' => $hasNumericScale,
                'hasUniformShortMc' => $hasUniformShortMc,
                'isMatrix' => $isMatrix,
                'matrixCols' => $matrixCols,
                'categoryDefaultOpts' => $categoryDefaultOpts,
                'perPage' => $perPage,
            ];
        }
    @endphp

    @if($totalSections === 0)
        <div class="bg-surface rounded-2xl border border-border shadow-xs p-6 sm:p-8 text-center">
            <p class="text-muted-foreground text-sm">No responses found for this submission.</p>
        </div>
    @else
        <div x-data="{
                sections: @js($sectionMetaList),
                activeCategory: @js($firstCategory),
                mobileSectionsOpen: false,
                viewAll: false,
                pageByCategory: {},
                init() {
                    this.sections.forEach(s => {
                        this.pageByCategory[s.key] = 1;
                    });
                },
                get activeIndex() {
                    const idx = this.sections.findIndex(s => s.key === this.activeCategory);
                    return idx >= 0 ? idx : 0;
                },
                get activeSection() {
                    return this.sections[this.activeIndex] || this.sections[0];
                },
                currentPage(key = null) {
                    const target = key || this.activeCategory;
                    return this.pageByCategory[target] || 1;
                },
                setPage(page, key = null) {
                    const target = key || this.activeCategory;
                    const sec = this.sections.find(s => s.key === target);
                    if (!sec) return;
                    const clamped = Math.max(1, Math.min(page, sec.totalPages));
                    this.pageByCategory[target] = clamped;
                    this.resetScroll();
                },
                prevPage() {
                    this.setPage(this.currentPage() - 1);
                },
                nextPage() {
                    this.setPage(this.currentPage() + 1);
                },
                isItemVisible(key, itemIdx) {
                    if (this.viewAll) return true;
                    const sec = this.sections.find(s => s.key === key);
                    if (!sec || sec.totalPages <= 1) return true;
                    const p = this.currentPage(key);
                    const start = (p - 1) * sec.perPage;
                    return itemIdx >= start && itemIdx < start + sec.perPage;
                },
                rangeLabel() {
                    const sec = this.activeSection;
                    if (!sec || sec.count === 0) return '0 items';
                    if (this.viewAll || sec.totalPages <= 1) {
                        return 'Showing all ' + sec.count + ' items';
                    }
                    const p = this.currentPage(sec.key);
                    const start = ((p - 1) * sec.perPage) + 1;
                    const end = Math.min(sec.count, p * sec.perPage);
                    return 'Showing ' + start + '–' + end + ' of ' + sec.count;
                },
                selectCategory(key, fromMobile = false) {
                    if (!key) return;
                    this.activeCategory = key;
                    if (fromMobile) {
                        this.mobileSectionsOpen = false;
                    }
                    this.resetScroll();
                },
                revealItem(key, itemIdx) {
                    const sec = this.sections.find(s => s.key === key);
                    if (sec && sec.perPage > 0) {
                        this.pageByCategory[key] = Math.floor(itemIdx / sec.perPage) + 1;
                    }
                    this.selectCategory(key);
                },
                resetScroll() {
                    this.$nextTick(() => {
                        if (this.$refs.answersScrollArea) {
                            this.$refs.answersScrollArea.scrollTop = 0;
                        }
                    });
                },
                prevSection() {
                    if (this.activeIndex > 0) {
                        this.selectCategory(this.sections[this.activeIndex - 1].key);
                    }
                },
                nextSection() {
                    if (this.activeIndex < this.sections.length - 1) {
                        this.selectCategory(this.sections[this.activeIndex + 1].key);
                    }
                },
                handleTabKeydown(e, idx) {
                    let targetIdx = null;
                    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                        targetIdx = (idx + 1) % this.sections.length;
                    } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                        targetIdx = (idx - 1 + this.sections.length) % this.sections.length;
                    } else if (e.key === 'Home') {
                        targetIdx = 0;
                    } else if (e.key === 'End') {
                        targetIdx = this.sections.length - 1;
                    }
                    if (targetIdx !== null) {
                        e.preventDefault();
                        const target = this.sections[targetIdx];
                        if (target) {
                            this.selectCategory(target.key);
                            this.$nextTick(() => {
                                const btn = document.getElementById('history-tab-' + targetIdx);
                                if (btn) btn.focus();
                            });
                        }
                    }
                }
             }"
             class="space-y-3 lg:space-y-0">

            <!-- Mobile & Tablet Section Bar (< lg): No horizontal scrollbars -->
            <div class="lg:hidden relative">
                <div class="bg-surface rounded-2xl border border-border shadow-xs p-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-primary">
                                Section <span x-text="activeSection.num">1</span> of {{ $totalSections }}
                                <span class="text-muted-foreground font-semibold">&middot; <span x-text="activeSection.count"></span> items</span>
                            </p>
                            <p class="text-sm font-bold text-foreground truncate mt-0.5" x-text="activeSection.label">
                                {{ $sectionMetaList[0]['label'] ?? '' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button"
                                    @click="prevSection()"
                                    :disabled="activeIndex === 0"
                                    aria-label="Previous section"
                                    class="min-w-[40px] min-h-[40px] inline-flex items-center justify-center rounded-token-md border border-border bg-surface-subtle hover:bg-muted text-foreground disabled:opacity-40 cursor-pointer transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                            </button>
                            <button type="button"
                                    @click="nextSection()"
                                    :disabled="activeIndex >= sections.length - 1"
                                    aria-label="Next section"
                                    class="min-w-[40px] min-h-[40px] inline-flex items-center justify-center rounded-token-md border border-border bg-surface-subtle hover:bg-muted text-foreground disabled:opacity-40 cursor-pointer transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                            <button type="button"
                                    @click="mobileSectionsOpen = !mobileSectionsOpen"
                                    :aria-expanded="mobileSectionsOpen.toString()"
                                    aria-controls="mobile-history-sections"
                                    class="min-h-[40px] px-3 py-1.5 rounded-token-md border border-border bg-primary-subtle text-xs font-semibold text-primary inline-flex items-center gap-1.5 cursor-pointer transition-colors">
                                <span>Sections</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-150" :class="mobileSectionsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Expandable Vertical List of Sections on Mobile -->
                <div id="mobile-history-sections"
                     x-show="mobileSectionsOpen"
                     x-cloak
                     x-transition.opacity.duration.150ms
                     @click.outside="mobileSectionsOpen = false"
                     class="mt-2 bg-surface rounded-2xl border border-border shadow-lg overflow-hidden">
                    <div class="px-4 py-2 border-b border-border bg-surface-subtle flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">All Sections ({{ $totalSections }})</span>
                        <button type="button" @click="mobileSectionsOpen = false" class="text-xs font-semibold text-primary hover:underline px-2 py-1 cursor-pointer">Close</button>
                    </div>
                    <div class="max-h-72 overflow-y-auto p-2 space-y-1">
                        @foreach($sectionMetaList as $sec)
                            <button type="button"
                                    @click="selectCategory(@js($sec['key']), true)"
                                    :class="activeCategory === @js($sec['key']) ? 'bg-primary text-on-primary shadow-2xs' : 'bg-surface hover:bg-muted text-foreground'"
                                    class="w-full min-h-[40px] flex items-center justify-between gap-2.5 px-3 py-2 rounded-token-md text-left transition-colors cursor-pointer">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span :class="activeCategory === @js($sec['key']) ? 'bg-white/20 text-white' : 'bg-muted text-muted-foreground'"
                                          class="w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-bold tabular-nums shrink-0">
                                        {{ $sec['num'] }}
                                    </span>
                                    <span class="text-xs sm:text-sm font-semibold leading-snug break-words">{{ $sec['label'] }}</span>
                                </div>
                                <span :class="activeCategory === @js($sec['key']) ? 'text-white/85' : 'text-muted-foreground'" class="text-[11px] font-medium shrink-0 tabular-nums">
                                    {{ $sec['count'] }} {{ \Illuminate\Support\Str::plural('item', $sec['count']) }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Unified Connected Card: Compact Left Sections Rail (all 16 visible) + Zero-Scroll Right Matrix/Choices Panel -->
            <div class="bg-surface rounded-2xl border border-border shadow-xs overflow-hidden flex flex-col lg:flex-row lg:h-[calc(100vh-10.25rem)] lg:min-h-[540px]">

                <!-- Desktop Left Vertical Sections Rail (compact rows so all 16 sections fit without scrolling) -->
                <aside aria-label="Assessment Sections" class="hidden lg:flex lg:flex-col lg:w-[240px] xl:w-[260px] shrink-0 border-r border-border bg-white h-full overflow-hidden">
                    <div class="px-3.5 py-2.5 border-b border-border bg-surface shrink-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Sections</span>
                            <span class="text-xs font-bold text-primary tabular-nums">
                                <span x-text="activeSection.num">1</span> / {{ $totalSections }}
                            </span>
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-0.5 tabular-nums">
                            {{ $submission->responses->count() }} total responses
                        </p>
                    </div>

                    <div role="tablist"
                         aria-orientation="vertical"
                         aria-label="Assessment sections"
                         class="flex-1 min-h-0 overflow-y-auto p-1.5 space-y-0.5 focus:outline-none">
                        @foreach($sectionMetaList as $idx => $sec)
                            <button type="button"
                                    id="history-tab-{{ $idx }}"
                                    role="tab"
                                    aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                                    tabindex="{{ $idx === 0 ? '0' : '-1' }}"
                                    title="{{ $sec['label'] }} ({{ $sec['count'] }} items)"
                                    :aria-selected="(activeCategory === @js($sec['key'])).toString()"
                                    :tabindex="activeCategory === @js($sec['key']) ? 0 : -1"
                                    @click="selectCategory(@js($sec['key']))"
                                    @keydown="handleTabKeydown($event, {{ $idx }})"
                                    :class="activeCategory === @js($sec['key'])
                                        ? 'bg-primary text-on-primary shadow-2xs border-primary'
                                        : 'bg-surface hover:bg-muted/70 text-foreground border-transparent'"
                                    class="w-full min-h-[34px] flex items-center justify-between gap-2 px-2 py-1.5 rounded-token-md border text-left transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <span class="flex items-center gap-2 min-w-0 flex-1">
                                    <span :class="activeCategory === @js($sec['key']) ? 'bg-white/20 text-white' : 'bg-muted text-muted-foreground'"
                                          class="w-5 h-5 rounded-full inline-flex items-center justify-center text-[11px] font-bold tabular-nums shrink-0 transition-colors">
                                        {{ $sec['num'] }}
                                    </span>
                                    <span class="text-xs font-semibold truncate leading-tight">{{ $sec['label'] }}</span>
                                </span>
                                <span :class="activeCategory === @js($sec['key']) ? 'bg-white/15 text-white' : 'bg-surface-subtle text-muted-foreground border border-border/70'"
                                      class="text-[10px] font-bold px-1.5 py-0.5 rounded-full tabular-nums shrink-0">
                                    {{ $sec['count'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </aside>

                <!-- Right Panel: Active Category Matrix / Compact Cards with Zero-Scroll Pagination -->
                <div class="flex-1 min-w-0 min-h-0 flex flex-col bg-surface h-full">
                    @foreach($sectionMetaList as $idx => $sec)
                        @php
                            $category = $sec['key'];
                            $conf = $sectionConfigs[$category];
                            $categoryConfig = $conf['cfg'];
                            $responses = $conf['responses'];
                            $scaleLabels = $conf['scaleLabels'];
                            $hasNumericScale = $conf['hasNumericScale'];
                            $hasUniformShortMc = $conf['hasUniformShortMc'];
                            $isMatrix = $conf['isMatrix'];
                            $matrixCols = $conf['matrixCols'];
                            $categoryDefaultOpts = $conf['categoryDefaultOpts'];
                            $colCount = count($matrixCols);

                            $matrixWidthClass = match ($colCount) {
                                2 => 'md:grid-cols-2 md:w-[176px] xl:w-[196px]',
                                3 => 'md:grid-cols-3 md:w-[216px] xl:w-[252px]',
                                4 => 'md:grid-cols-4 md:w-[272px] xl:w-[316px]',
                                5 => 'md:grid-cols-5 md:w-[300px] xl:w-[350px]',
                                6 => 'md:grid-cols-6 md:w-[312px] xl:w-[360px]',
                                7 => 'md:grid-cols-7 md:w-[322px] xl:w-[378px]',
                                default => 'md:grid-cols-5 md:w-[320px]',
                            };
                        @endphp

                        <div role="tabpanel"
                             aria-labelledby="history-tab-{{ $idx }}"
                             @if($idx !== 0) hidden="until-found" @endif
                             :hidden="activeCategory !== @js($category) ? 'until-found' : false"
                             @beforematch="selectCategory(@js($category))"
                             :class="activeCategory === @js($category) ? 'flex-1 min-h-0 flex flex-col' : ''">
                            <!-- Child wrapper inside panel (tabs guide: keep layout/padding inside child wrapper) -->
                            <div class="flex-1 min-h-0 flex flex-col bg-white">
                                <!-- Compact Section Banner Header -->
                                <div class="shrink-0 px-4 sm:px-5 py-2.5 border-b border-border bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-token-sm bg-primary-subtle text-primary border border-primary/20 text-[11px] font-bold tabular-nums">
                                                Section {{ $sec['num'] }} of {{ $totalSections }}
                                            </span>
                                            <span class="text-xs font-medium text-muted-foreground tabular-nums">
                                                {{ $sec['count'] }} {{ \Illuminate\Support\Str::plural('question', $sec['count']) }}
                                            </span>
                                            @if($hasNumericScale)
                                                <span class="text-xs font-medium text-muted-foreground">
                                                    &middot; Scale {{ $categoryConfig->scale_min }}&ndash;{{ $categoryConfig->scale_max }}
                                                </span>
                                            @elseif($hasUniformShortMc)
                                                <span class="text-xs font-medium text-muted-foreground">
                                                    &middot; {{ $colCount }} choices
                                                </span>
                                            @endif
                                        </div>
                                        <h2 class="text-base sm:text-[17px] font-heading font-bold text-foreground mt-0.5 truncate" title="{{ $sec['label'] }}">
                                            {{ $sec['label'] }}
                                        </h2>
                                    </div>

                                    <!-- Desktop Prev/Next Section Controls -->
                                    <div class="hidden lg:flex items-center gap-1.5 shrink-0">
                                        <button type="button"
                                                @click="prevSection()"
                                                :disabled="activeIndex === 0"
                                                class="btn-secondary min-h-[34px] px-2.5 py-1 text-xs inline-flex items-center gap-1 disabled:opacity-40 cursor-pointer">
                                            &larr; Prev
                                        </button>
                                        <button type="button"
                                                @click="nextSection()"
                                                :disabled="activeIndex >= sections.length - 1"
                                                class="btn-secondary min-h-[34px] px-2.5 py-1 text-xs inline-flex items-center gap-1 disabled:opacity-40 cursor-pointer">
                                            Next &rarr;
                                        </button>
                                    </div>
                                </div>

                                @if($isMatrix)
                                    <!-- Pinned Matrix Column Scale Header (Desktop aligned directly above each column) -->
                                    <div class="shrink-0 bg-surface-subtle/90 border-b border-border px-4 sm:px-5 py-2">
                                        <div class="hidden md:flex items-center gap-4">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                                    Question &amp; Prompt
                                                </span>
                                            </div>
                                            <div class="grid {{ $matrixWidthClass }} shrink-0 gap-1 text-center">
                                                @foreach($matrixCols as $mCol)
                                                    <div class="flex flex-col items-center justify-start px-0.5" title="{{ $mCol['badge'] }}{{ $mCol['label'] ? ': ' . $mCol['label'] : '' }}">
                                                        @if($hasNumericScale)
                                                            <span class="w-6 h-6 rounded-full bg-white border border-primary/30 text-primary font-bold text-xs inline-flex items-center justify-center tabular-nums shadow-2xs">
                                                                {{ $mCol['badge'] }}
                                                            </span>
                                                            @if($mCol['label'])
                                                                <span class="text-[10px] leading-[1.15] text-foreground/85 font-semibold mt-1 line-clamp-2 break-words">
                                                                    {{ $mCol['label'] }}
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="text-[10px] leading-[1.2] text-foreground font-bold line-clamp-2 break-words">
                                                                {{ $mCol['label'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Compact Mobile Legend Strip -->
                                        <div class="md:hidden">
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">
                                                {{ $hasNumericScale ? "Scale ({$categoryConfig->scale_min}–{$categoryConfig->scale_max})" : 'Choices' }}
                                            </p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($matrixCols as $mCol)
                                                    @if(! $hasNumericScale || $mCol['label'])
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-token-sm bg-white border border-border text-[11px]">
                                                            <span class="font-bold text-primary tabular-nums">{{ $mCol['badge'] }}</span>
                                                            @if($mCol['label'])
                                                                <span class="text-foreground/85">{{ $mCol['label'] }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Questions List Area (Paged to fit cleanly without scrolling; fallback scroll only if View All is toggled or tiny screen) -->
                                <div x-ref="answersScrollArea"
                                     :class="viewAll ? 'overflow-y-auto' : 'overflow-y-auto lg:overflow-y-auto'"
                                     class="flex-1 min-h-0 {{ $isMatrix ? 'divide-y divide-border/70 bg-white' : 'p-3 sm:p-4 space-y-2.5 bg-surface-subtle/35' }}">
                                    @foreach($responses as $itemIdx => $response)
                                        @php
                                            $questionItem = $categoryConfig?->questionItems->firstWhere('item_number', $response->item_number);
                                            $questionPrompt = $questionItem?->prompt ?? 'Question prompt unavailable';
                                            $rawSubcatName = $questionItem?->subcategory?->name;
                                            $subcatName = $rawSubcatName
                                                ? (str_contains($rawSubcatName, '_') ? \Illuminate\Support\Str::headline($rawSubcatName) : $rawSubcatName)
                                                : null;

                                            $itemOptions = ! empty($questionItem?->options) ? $questionItem->options : $categoryDefaultOpts;
                                            $itemOptions = is_array($itemOptions) ? array_values($itemOptions) : [];
                                            $isMultiSelect = $questionItem ? $questionItem->allowsMultiple($categoryConfig) : false;
                                            $isMc = $isMultiSelect
                                                || in_array($categoryConfig?->scale_type, ['multiple_choice_unscored', 'multiple_choice'], true)
                                                || (! $hasNumericScale && ! empty($itemOptions));

                                            $rawAnswer = trim((string) ($response->response_value ?? ''));

                                            $selectedMultiTokens = [];
                                            $otherSpecs = [];
                                            $answerSummaryLabel = $rawAnswer !== '' ? $rawAnswer : 'No response';

                                            if ($isMc && ! empty($itemOptions)) {
                                                $otherOpts = array_values(array_filter(
                                                    array_map('strval', $itemOptions),
                                                    static fn (string $opt): bool => preg_match('/^others?\b/i', trim($opt)) === 1
                                                ));

                                                $rawTokens = [];
                                                if ($isMultiSelect && $rawAnswer !== '') {
                                                    if (str_starts_with($rawAnswer, '[')) {
                                                        $decoded = json_decode($rawAnswer, true);
                                                        $rawTokens = is_array($decoded) ? array_map('strval', $decoded) : [$rawAnswer];
                                                    } else {
                                                        $rawTokens = array_map('trim', explode(',', $rawAnswer));
                                                    }
                                                } elseif ($rawAnswer !== '') {
                                                    $rawTokens = [$rawAnswer];
                                                }

                                                foreach ($rawTokens as $tok) {
                                                    $tokStr = trim((string) $tok);
                                                    if ($tokStr === '') {
                                                        continue;
                                                    }
                                                    $matchedOther = null;
                                                    foreach ($otherOpts as $oOpt) {
                                                        $prefix = $oOpt . ':';
                                                        if (str_starts_with($tokStr, $prefix)) {
                                                            $matchedOther = $oOpt;
                                                            $otherSpecs[$oOpt] = trim(substr($tokStr, strlen($prefix)));
                                                            break;
                                                        }
                                                    }
                                                    $selectedMultiTokens[] = $matchedOther ?? $tokStr;
                                                }
                                                $selectedMultiTokens = array_values(array_unique($selectedMultiTokens));
                                                if ($isMultiSelect && count($selectedMultiTokens) > 1) {
                                                    $answerSummaryLabel = count($selectedMultiTokens) . ' selected';
                                                } elseif (! empty($otherSpecs)) {
                                                    $firstOtherKey = array_key_first($otherSpecs);
                                                    $specTxt = $otherSpecs[$firstOtherKey];
                                                    $answerSummaryLabel = $specTxt !== '' ? "{$firstOtherKey}: {$specTxt}" : $firstOtherKey;
                                                } elseif (! empty($selectedMultiTokens)) {
                                                    $answerSummaryLabel = $selectedMultiTokens[0];
                                                }
                                            } elseif ($hasNumericScale && $rawAnswer !== '') {
                                                $numKey = is_numeric($rawAnswer) ? (int) $rawAnswer : $rawAnswer;
                                                $matchedScaleLabel = $scaleLabels[$numKey] ?? $scaleLabels[(string) $rawAnswer] ?? null;
                                                $answerSummaryLabel = $matchedScaleLabel
                                                    ? "{$rawAnswer} — {$matchedScaleLabel}"
                                                    : "{$rawAnswer} (out of {$categoryConfig->scale_max})";
                                            }
                                        @endphp

                                        @if($isMatrix)
                                            <!-- Compact Single-Row Matrix Item -->
                                            <div x-show="isItemVisible(@js($category), {{ $itemIdx }})"
                                                 @beforematch="revealItem(@js($category), {{ $itemIdx }})"
                                                 class="px-4 sm:px-5 py-2.5 flex flex-col md:flex-row md:items-center gap-2 md:gap-4 transition-colors {{ $itemIdx % 2 === 1 ? 'bg-surface-subtle/35' : 'bg-white' }}">
                                                <!-- Question Text -->
                                                <div class="flex-1 min-w-0 flex items-start md:items-center justify-between gap-2">
                                                    <div class="flex items-start gap-2.5 min-w-0 flex-1">
                                                        <span class="shrink-0 w-6 h-6 rounded-full bg-primary-subtle text-primary border border-primary/20 inline-flex items-center justify-center text-[11px] font-bold tabular-nums mt-0.5 md:mt-0">
                                                            {{ $response->item_number }}
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="text-[13px] font-medium text-foreground leading-snug break-words">
                                                                {{ $questionPrompt }}
                                                            </p>
                                                            @if($subcatName)
                                                                <span class="inline-block mt-0.5 text-[10px] font-semibold text-muted-foreground">
                                                                    {{ $subcatName }}
                                                                </span>
                                                            @endif
                                                            <span class="sr-only">Your answer: {{ $answerSummaryLabel }}</span>
                                                        </div>
                                                    </div>

                                                    <!-- Mobile Chosen Summary Pill -->
                                                    <span class="md:hidden shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-token-sm bg-primary-subtle text-primary border border-primary/25 text-[11px] font-bold max-w-[130px]">
                                                        <span class="truncate">{{ $answerSummaryLabel }}</span>
                                                    </span>
                                                </div>

                                                <!-- Desktop Matrix Columns (aligned 1:1 with header) -->
                                                <div class="hidden md:grid {{ $matrixWidthClass }} shrink-0 gap-1 items-center" role="list" aria-label="Question {{ $response->item_number }} options">
                                                    @foreach($matrixCols as $mCol)
                                                        @php
                                                            $isChosen = ($rawAnswer !== '' && (string) $rawAnswer === (string) $mCol['value']);
                                                            $colTitle = $mCol['label'] ? "{$mCol['badge']} — {$mCol['label']}" : $mCol['badge'];
                                                        @endphp
                                                        <div role="listitem"
                                                             title="{{ $isChosen ? 'Your answer: ' : '' }}{{ $colTitle }}"
                                                             class="flex items-center justify-center">
                                                            @if($hasNumericScale)
                                                                @if($isChosen)
                                                                    <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-xs font-bold tabular-nums inline-flex items-center justify-center shadow-xs ring-4 ring-primary/15">
                                                                        {{ $mCol['badge'] }}
                                                                    </span>
                                                                @else
                                                                    <span class="w-6 h-6 rounded-full bg-surface-subtle/80 border border-border/80 text-muted-foreground/75 text-[11px] font-medium tabular-nums inline-flex items-center justify-center">
                                                                        {{ $mCol['badge'] }}
                                                                    </span>
                                                                @endif
                                                            @else
                                                                @if($isChosen)
                                                                    <span class="w-7 h-7 rounded-full bg-primary text-on-primary inline-flex items-center justify-center shadow-xs ring-4 ring-primary/15" aria-label="Selected: {{ $mCol['label'] }}">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                @else
                                                                    <span class="w-5 h-5 rounded-full bg-surface-subtle border border-border/80 inline-flex items-center justify-center">
                                                                        <span class="w-1.5 h-1.5 rounded-full bg-border"></span>
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <!-- Mobile Compact Option Strip (< md) -->
                                                <div class="md:hidden flex flex-wrap gap-1.5 pl-8">
                                                    @foreach($matrixCols as $mCol)
                                                        @php
                                                            $isChosen = ($rawAnswer !== '' && (string) $rawAnswer === (string) $mCol['value']);
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-token-sm border text-[11px] {{ $isChosen ? 'bg-primary text-on-primary border-primary font-bold shadow-2xs' : 'bg-surface-subtle text-muted-foreground border-border' }}">
                                                            @if($hasNumericScale)
                                                                {{ $mCol['badge'] }}
                                                            @else
                                                                {{ $mCol['label'] }}
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <!-- Compact Card for Per-Item Custom Choices / Multi-Select / Open-Ended Text -->
                                            <article x-show="isItemVisible(@js($category), {{ $itemIdx }})"
                                                     @beforematch="revealItem(@js($category), {{ $itemIdx }})"
                                                     class="rounded-token-lg border border-border bg-white p-3 sm:p-3.5 shadow-2xs space-y-2">
                                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                                    <div class="flex items-start gap-2.5 min-w-0 flex-1">
                                                        <span class="shrink-0 w-6 h-6 rounded-full bg-primary-subtle text-primary border border-primary/20 inline-flex items-center justify-center text-[11px] font-bold tabular-nums mt-0.5">
                                                            {{ $response->item_number }}
                                                        </span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="text-[13px] sm:text-sm font-semibold text-foreground leading-snug break-words">
                                                                {{ $questionPrompt }}
                                                            </p>
                                                            @if($subcatName)
                                                                <span class="inline-block mt-0.5 text-[10px] font-semibold text-muted-foreground">
                                                                    {{ $subcatName }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="pl-8 sm:pl-0 shrink-0">
                                                        @if($rawAnswer !== '')
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-token-md bg-primary-subtle text-primary border border-primary/25 text-[11px] font-bold break-words max-w-full sm:max-w-[260px]">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                                </svg>
                                                                <span class="truncate" title="{{ $answerSummaryLabel }}">{{ $answerSummaryLabel }}</span>
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-token-md bg-muted text-muted-foreground border border-border text-[11px] font-medium">
                                                                No response
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="pl-0 sm:pl-8">
                                                    @if($isMc && ! empty($itemOptions))
                                                        <div class="flex flex-wrap gap-1.5" role="list" aria-label="Question {{ $response->item_number }} choices">
                                                            @foreach($itemOptions as $optIdx => $optText)
                                                                @php
                                                                    $optStr = (string) $optText;
                                                                    $isChosen = in_array($optStr, $selectedMultiTokens, true);
                                                                    $otherSpecText = $otherSpecs[$optStr] ?? null;
                                                                @endphp
                                                                <div role="listitem"
                                                                     class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-token-md border text-xs transition-colors {{ $isChosen ? 'bg-primary-subtle border-primary ring-1 ring-primary text-foreground font-semibold' : 'bg-surface-subtle/60 border-border text-muted-foreground' }}">
                                                                    <span class="w-4 h-4 rounded-full inline-flex items-center justify-center text-[10px] font-bold tabular-nums shrink-0 {{ $isChosen ? 'bg-primary text-on-primary' : 'bg-muted text-muted-foreground' }}">
                                                                        {{ $optIdx + 1 }}
                                                                    </span>
                                                                    <span class="break-words leading-snug">
                                                                        {{ $optStr }}@if($isChosen && $otherSpecText !== null && $otherSpecText !== ''): <span class="text-primary underline decoration-primary/40">{{ $otherSpecText }}</span>@endif
                                                                    </span>
                                                                    @if($isChosen)
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="rounded-token-md bg-surface-subtle/70 border border-border px-3 py-1.5 text-xs text-foreground">
                                                            <span class="font-bold text-muted-foreground mr-1">Written response:</span>
                                                            <span class="break-words">{{ $rawAnswer !== '' ? $rawAnswer : 'No response provided.' }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </article>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Zero-Scroll Pagination & View Mode Footer -->
                                <div class="shrink-0 px-4 sm:px-5 py-2 border-t border-border bg-white flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-medium text-muted-foreground tabular-nums" x-text="rangeLabel()">
                                            {{ $sec['count'] }} items
                                        </span>
                                        @if($sec['totalPages'] > 1)
                                            <button type="button"
                                                    @click="viewAll = !viewAll; resetScroll()"
                                                    class="text-[11px] font-semibold text-primary hover:underline cursor-pointer"
                                                    x-text="viewAll ? 'Fit to screen (paged)' : 'View all'">
                                            </button>
                                        @endif
                                    </div>

                                    @if($sec['totalPages'] > 1)
                                        <div x-show="!viewAll" class="flex items-center gap-1">
                                            <button type="button"
                                                    @click="prevPage()"
                                                    :disabled="currentPage(@js($category)) <= 1"
                                                    aria-label="Previous questions page"
                                                    class="min-w-[30px] h-[30px] px-2 rounded-token-sm border border-border bg-surface-subtle hover:bg-muted text-xs font-semibold text-foreground disabled:opacity-40 cursor-pointer transition-colors">
                                                &lsaquo;
                                            </button>
                                            @for($pNum = 1; $pNum <= $sec['totalPages']; $pNum++)
                                                <button type="button"
                                                        @click="setPage({{ $pNum }}, @js($category))"
                                                        :class="currentPage(@js($category)) === {{ $pNum }}
                                                            ? 'bg-primary text-on-primary border-primary font-bold shadow-2xs'
                                                            : 'bg-white hover:bg-surface-subtle text-foreground border-border font-medium'"
                                                        class="min-w-[30px] h-[30px] px-2 rounded-token-sm border text-xs tabular-nums cursor-pointer transition-colors">
                                                    {{ $pNum }}
                                                </button>
                                            @endfor
                                            <button type="button"
                                                    @click="nextPage()"
                                                    :disabled="currentPage(@js($category)) >= {{ $sec['totalPages'] }}"
                                                    aria-label="Next questions page"
                                                    class="min-w-[30px] h-[30px] px-2 rounded-token-sm border border-border bg-surface-subtle hover:bg-muted text-xs font-semibold text-foreground disabled:opacity-40 cursor-pointer transition-colors">
                                                &rsaquo;
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
