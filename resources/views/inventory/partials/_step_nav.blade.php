@php
    $stepAttr = ($variant ?? 'rail') === 'mobile' ? 'data-mobile-step' : 'data-rail-step';
@endphp

<!-- Section 1 (Internal Step 0): Consent -->
<button type="button"
        {{ $stepAttr }}="0"
        @click="goToStep(0)"
        :aria-current="step === 0 ? 'step' : null"
        class="w-full min-h-[44px] flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-token-md text-left transition-colors cursor-pointer"
        :class="step === 0
            ? 'bg-primary text-on-primary shadow-token-xs font-semibold'
            : (maxStep > 0 ? 'text-foreground hover:bg-success-subtle/70' : 'text-foreground hover:bg-muted')">
    <div class="flex items-center gap-2.5 min-w-0">
        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 tabular-nums"
              :class="step === 0
                  ? 'bg-on-primary/20 text-on-primary'
                  : (maxStep > 0 ? 'bg-success-subtle text-success-foreground border border-success-border' : 'bg-surface-subtle text-muted-foreground border border-border')">
            <span x-show="maxStep > 0 && step !== 0" x-cloak>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
                <span class="sr-only">Completed</span>
            </span>
            <span x-show="!(maxStep > 0 && step !== 0)">1</span>
        </span>
        <span class="text-xs sm:text-sm font-medium truncate">Consent</span>
    </div>
</button>

@php $stepIndex = 1; @endphp
@foreach($inventoryConfig as $data)
    @php
        $catTitle = $data->label();
        $displayNum = $stepIndex + 1;
        $catPrimaryName = $catTitle;
        $catShortCode = null;
        if (preg_match('/^(.+?)\s*\(([^)]+)\)$/', $catTitle, $matches)) {
            $catPrimaryName = trim($matches[1]);
            $catShortCode = trim($matches[2]);
        }
    @endphp
    <button type="button"
            {{ $stepAttr }}="{{ $stepIndex }}"
            @click="goToStep({{ $stepIndex }})"
            :disabled="maxStep < {{ $stepIndex }}"
            :aria-current="step === {{ $stepIndex }} ? 'step' : null"
            title="{{ $catTitle }}"
            class="w-full min-h-[44px] flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-token-md text-left transition-colors"
            :class="step === {{ $stepIndex }}
                ? 'bg-primary text-on-primary shadow-token-xs font-semibold cursor-pointer'
                : (maxStep > {{ $stepIndex }}
                    ? 'text-foreground hover:bg-success-subtle/70 cursor-pointer'
                    : (maxStep === {{ $stepIndex }}
                        ? 'text-foreground hover:bg-muted cursor-pointer'
                        : 'text-muted-foreground/65 opacity-70 cursor-not-allowed'))">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 tabular-nums"
                  :class="step === {{ $stepIndex }}
                      ? 'bg-on-primary/20 text-on-primary'
                      : (maxStep > {{ $stepIndex }}
                          ? 'bg-success-subtle text-success-foreground border border-success-border'
                          : (maxStep === {{ $stepIndex }}
                              ? 'bg-surface text-foreground border border-border'
                              : 'bg-muted text-muted-foreground border border-border'))">
                <span x-show="maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }}" x-cloak>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    <span class="sr-only">Completed</span>
                </span>
                <span x-show="!(maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }})">{{ $displayNum }}</span>
            </span>
            <span class="text-xs sm:text-sm font-medium leading-snug line-clamp-2 break-words text-left">{{ $catPrimaryName }}@if($catShortCode)<span class="sr-only"> ({{ $catShortCode }})</span>@endif</span>
        </div>
        <span x-show="maxStep < {{ $stepIndex }} && step !== {{ $stepIndex }}" x-cloak class="shrink-0 text-muted-foreground/70">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
            </svg>
            <span class="sr-only">Locked</span>
        </span>
    </button>
    @php $stepIndex++; @endphp
@endforeach
