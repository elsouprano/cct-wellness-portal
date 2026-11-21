@php
    $stepAttr = ($variant ?? 'rail') === 'mobile' ? 'data-mobile-step' : 'data-rail-step';
    $gapClass = ($variant ?? 'rail') === 'mobile' ? 'gap-3' : 'gap-2.5';
@endphp

<!-- Section 1 (Internal Step 0): Consent -->
<button type="button"
        {{ $stepAttr }}="0"
        @click="goToStep(0)"
        :aria-current="step === 0 ? 'step' : null"
        class="w-full min-h-[44px] flex items-center justify-between {{ $gapClass }} px-3 py-2.5 rounded-token-md text-left transition-colors cursor-pointer"
        :class="step === 0
            ? 'bg-primary text-on-primary border-l-4 border-on-primary shadow-token-xs'
            : (maxStep > 0 ? 'bg-success-subtle text-foreground hover:bg-success-subtle/80 border border-success-border' : 'bg-surface-subtle text-foreground hover:bg-muted border border-border')">
    <div class="flex items-start gap-2.5 min-w-0">
        <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mt-0.5"
              :class="step === 0 ? 'bg-on-primary/20 text-on-primary' : (maxStep > 0 ? 'bg-success-subtle text-success-foreground border border-success-border' : 'bg-surface text-muted-foreground border border-border')">
            <span x-show="maxStep > 0 && step !== 0" x-cloak>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
            </span>
            <span x-show="!(maxStep > 0 && step !== 0)">1</span>
        </span>
        <div class="min-w-0">
            <span class="block text-sm font-semibold leading-snug break-words">Consent</span>
            <span class="block text-xs opacity-80 mt-0.5">Data Privacy (RA 10173)</span>
        </div>
    </div>
    <span class="text-xs font-semibold shrink-0 px-2 py-0.5 rounded-full"
          :class="step === 0 ? 'bg-on-primary/20 text-on-primary' : (maxStep > 0 ? 'bg-success-subtle text-success-foreground border border-success-border' : 'bg-muted text-muted-foreground')"
          x-text="step === 0 ? 'Current' : (maxStep > 0 ? 'Completed' : 'Upcoming')">Current</span>
</button>

@php $stepIndex = 1; @endphp
@foreach($inventoryConfig as $data)
    @php
        $catTitle = $data->label();
        $displayNum = min($totalDisplaySections, $stepIndex + 1);
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
            class="w-full min-h-[44px] flex items-center justify-between {{ $gapClass }} px-3 py-2.5 rounded-token-md text-left transition-colors"
            :class="step === {{ $stepIndex }}
                ? 'bg-primary text-on-primary border-l-4 border-on-primary shadow-token-xs cursor-pointer'
                : (maxStep > {{ $stepIndex }}
                    ? 'bg-success-subtle text-foreground hover:bg-success-subtle/80 border border-success-border cursor-pointer'
                    : (maxStep === {{ $stepIndex }}
                        ? 'bg-surface-subtle text-foreground hover:bg-muted border border-border cursor-pointer'
                        : 'bg-surface-subtle/50 text-muted-foreground border border-transparent cursor-not-allowed'))">
        <div class="flex items-start gap-2.5 min-w-0">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mt-0.5"
                  :class="step === {{ $stepIndex }}
                      ? 'bg-on-primary/20 text-on-primary'
                      : (maxStep > {{ $stepIndex }}
                          ? 'bg-success-subtle text-success-foreground border border-success-border'
                          : (maxStep === {{ $stepIndex }}
                              ? 'bg-surface text-foreground border border-border'
                              : 'bg-muted text-muted-foreground border border-border'))">
                <span x-show="maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }}" x-cloak>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span x-show="!(maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }})">{{ $displayNum }}</span>
            </span>
            <div class="min-w-0">
                <span class="block text-sm font-semibold leading-snug break-words">{{ $catPrimaryName }}</span>
                @if($catShortCode)
                    <span class="block text-xs italic opacity-80 mt-0.5">{{ $catShortCode }}</span>
                @endif
            </div>
        </div>
        <div class="shrink-0 flex items-center gap-1">
            <template x-if="maxStep < {{ $stepIndex }} && step !== {{ $stepIndex }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-muted-foreground" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                </svg>
            </template>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                  :class="step === {{ $stepIndex }}
                      ? 'bg-on-primary/20 text-on-primary'
                      : (maxStep > {{ $stepIndex }}
                          ? 'bg-success-subtle text-success-foreground border border-success-border'
                          : (maxStep === {{ $stepIndex }} ? 'bg-muted text-foreground' : 'text-muted-foreground'))"
                  x-text="step === {{ $stepIndex }} ? 'Current' : (maxStep > {{ $stepIndex }} ? 'Completed' : (maxStep === {{ $stepIndex }} ? 'Upcoming' : 'Locked'))">Upcoming</span>
        </div>
    </button>
    @php $stepIndex++; @endphp
@endforeach
