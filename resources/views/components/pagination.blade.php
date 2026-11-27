@props([
    'paginator' => null,
    'mode' => 'server', // 'server' or 'client'
    'limitParam' => 'per_page',
    'limits' => [10, 20, 50],
    'showLimit' => false,
    'formId' => null,
    'label' => 'Pagination',
    'itemLabel' => '',
])

@if($mode === 'client')
    {{-- Client-side Alpine pagination mode (must be inside x-data="clientPaginator({...})") --}}
    <nav
        aria-label="{{ $label }}"
        x-show="total > 0"
        class="px-5 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-muted-foreground"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-center sm:text-left">
            <span>
                Showing <strong class="font-semibold text-foreground" x-text="fromItem"></strong>&ndash;<strong class="font-semibold text-foreground" x-text="toItem"></strong>
                of <strong class="font-semibold text-foreground" x-text="total"></strong>
            </span>
            <span class="text-xs text-muted-foreground">
                Page <strong class="font-semibold text-foreground" x-text="page"></strong> of <strong class="font-semibold text-foreground" x-text="totalPages"></strong>
            </span>
        </div>

        <div class="flex flex-wrap items-center justify-center sm:justify-end gap-3">
            <label class="inline-flex items-center gap-2 text-xs font-medium text-muted-foreground">
                <span>Rows:</span>
                <select
                    aria-label="Rows per page"
                    :value="limit"
                    @change="setLimit($event.target.value)"
                    class="min-h-[44px] rounded-token-sm border border-border bg-surface text-foreground text-xs font-semibold py-2 pl-2.5 pr-7 focus:border-primary focus:ring-primary"
                >
                    <template x-for="opt in allowedLimits" :key="opt">
                        <option :value="opt" x-text="opt" :selected="opt === limit"></option>
                    </template>
                </select>
            </label>

            <div class="inline-flex flex-wrap items-center justify-center gap-1" role="group" aria-label="Page controls">
                <button
                    type="button"
                    @click="goTo(1)"
                    :disabled="page <= 1"
                    :aria-disabled="page <= 1 ? 'true' : 'false'"
                    aria-label="First page"
                    class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                    <x-icon name="chevron-double-left" class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    @click="goTo(page - 1)"
                    :disabled="page <= 1"
                    :aria-disabled="page <= 1 ? 'true' : 'false'"
                    aria-label="Previous page"
                    class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors gap-1 text-xs font-semibold"
                >
                    <x-icon name="chevron-left" class="w-4 h-4" />
                    <span class="hidden sm:inline">Prev</span>
                </button>

                <template x-for="(p, idx) in visiblePages" :key="idx">
                    <span class="inline-flex">
                        <span x-show="p === '...'" class="px-2 py-1 text-xs text-muted-foreground" aria-hidden="true">&hellip;</span>
                        <button
                            x-show="p !== '...'"
                            type="button"
                            @click="goTo(p)"
                            :aria-current="p === page ? 'page' : null"
                            :class="p === page
                                ? 'bg-primary text-on-primary border-primary font-semibold'
                                : 'bg-surface text-foreground border-border hover:bg-muted font-medium'"
                            class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border text-xs transition-colors"
                            x-text="p"
                        ></button>
                    </span>
                </template>

                <button
                    type="button"
                    @click="goTo(page + 1)"
                    :disabled="page >= totalPages"
                    :aria-disabled="page >= totalPages ? 'true' : 'false'"
                    aria-label="Next page"
                    class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors gap-1 text-xs font-semibold"
                >
                    <span class="hidden sm:inline">Next</span>
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    @click="goTo(totalPages)"
                    :disabled="page >= totalPages"
                    :aria-disabled="page >= totalPages ? 'true' : 'false'"
                    aria-label="Last page"
                    class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                    <x-icon name="chevron-double-right" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </nav>
@elseif($paginator && $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
    @php
        $total = $paginator->total();
        $currentPage = $paginator->currentPage();
        $lastPage = max(1, $paginator->lastPage());
        $perPage = $paginator->perPage();
        $outOfRange = $total > 0 && $currentPage > $lastPage;

        // Build compact page window
        $window = [];
        for ($i = 1; $i <= $lastPage; $i++) {
            if ($i === 1 || $i === $lastPage || ($i >= $currentPage - 1 && $i <= $currentPage + 1)) {
                $window[] = $i;
            } elseif (end($window) !== '...') {
                $window[] = '...';
            }
        }
    @endphp

    @if($outOfRange)
        <div class="px-5 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 bg-muted/40 border-t border-border text-sm">
            <p class="text-muted-foreground">
                Page <strong class="text-foreground">{{ $currentPage }}</strong> is beyond the available {{ $lastPage }} {{ \Illuminate\Support\Str::plural('page', $lastPage) }}.
            </p>
            <a href="{{ $paginator->url($lastPage) }}" class="btn-secondary text-sm py-1.5 px-3.5">
                Go to Page {{ $lastPage }}
            </a>
        </div>
    @elseif($total > 0)
        <nav
            aria-label="{{ $label }}"
            class="px-5 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-muted-foreground"
        >
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-center sm:text-left">
                <span class="font-medium text-foreground">
                    Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($total) }}{{ $itemLabel ? ' ' . $itemLabel : '' }}
                </span>
                <span class="text-xs text-muted-foreground">
                    Page <strong class="font-semibold text-foreground">{{ $currentPage }}</strong> of <strong class="font-semibold text-foreground">{{ $lastPage }}</strong>
                </span>
            </div>

            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-3">
                @if($showLimit)
                    <form method="GET" action="{{ request()->url() }}" class="inline-flex items-center gap-2 text-xs font-medium text-muted-foreground">
                        @foreach(request()->except([$limitParam, 'page']) as $key => $val)
                            @if(is_scalar($val))
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endforeach
                        <label for="limit-{{ md5($label . $limitParam) }}">Rows:</label>
                        <div class="w-[76px]">
                            <select
                                id="limit-{{ md5($label . $limitParam) }}"
                                name="{{ $limitParam }}"
                                onchange="this.form.submit()"
                                class="min-h-[44px] w-full rounded-token-sm border border-border bg-surface text-foreground text-xs font-semibold py-2 pl-2.5 pr-7 focus:border-primary focus:ring-primary"
                            >
                                @foreach($limits as $opt)
                                    <option value="{{ $opt }}" @selected((int) $perPage === (int) $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif

                @if($lastPage > 1)
                    <div class="inline-flex flex-wrap items-center justify-center gap-1" role="group" aria-label="Page controls">
                        {{-- First --}}
                        @if($paginator->onFirstPage())
                            <span aria-disabled="true" aria-label="First page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2 rounded-token-sm border border-border bg-muted/40 text-muted-foreground/50 cursor-not-allowed">
                                <x-icon name="chevron-double-left" class="w-4 h-4" />
                            </span>
                            <span aria-disabled="true" aria-label="Previous page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-muted/40 text-muted-foreground/50 cursor-not-allowed gap-1 text-xs font-semibold">
                                <x-icon name="chevron-left" class="w-4 h-4" />
                                <span class="hidden sm:inline">Prev</span>
                            </span>
                        @else
                            <a href="{{ $paginator->url(1) }}" aria-label="First page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted transition-colors">
                                <x-icon name="chevron-double-left" class="w-4 h-4" />
                            </a>
                            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted transition-colors gap-1 text-xs font-semibold">
                                <x-icon name="chevron-left" class="w-4 h-4" />
                                <span class="hidden sm:inline">Prev</span>
                            </a>
                        @endif

                        {{-- Page numbers --}}
                        @foreach($window as $pageNum)
                            @if($pageNum === '...')
                                <span class="px-2 py-1 text-xs text-muted-foreground" aria-hidden="true">&hellip;</span>
                            @elseif($pageNum === $currentPage)
                                <span aria-current="page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-primary bg-primary text-on-primary text-xs font-semibold">
                                    {{ $pageNum }}
                                </span>
                            @else
                                <a href="{{ $paginator->url($pageNum) }}" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted text-xs font-medium transition-colors">
                                    {{ $pageNum }}
                                </a>
                            @endif
                        @endforeach

                        {{-- Next & Last --}}
                        @if($paginator->hasMorePages())
                            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted transition-colors gap-1 text-xs font-semibold">
                                <span class="hidden sm:inline">Next</span>
                                <x-icon name="chevron-right" class="w-4 h-4" />
                            </a>
                            <a href="{{ $paginator->url($lastPage) }}" aria-label="Last page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2 rounded-token-sm border border-border bg-surface text-foreground hover:bg-muted transition-colors">
                                <x-icon name="chevron-double-right" class="w-4 h-4" />
                            </a>
                        @else
                            <span aria-disabled="true" aria-label="Next page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2.5 rounded-token-sm border border-border bg-muted/40 text-muted-foreground/50 cursor-not-allowed gap-1 text-xs font-semibold">
                                <span class="hidden sm:inline">Next</span>
                                <x-icon name="chevron-right" class="w-4 h-4" />
                            </span>
                            <span aria-disabled="true" aria-label="Last page" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] px-2 rounded-token-sm border border-border bg-muted/40 text-muted-foreground/50 cursor-not-allowed">
                                <x-icon name="chevron-double-right" class="w-4 h-4" />
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </nav>
    @endif
@endif
