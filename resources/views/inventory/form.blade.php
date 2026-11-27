@section('title', 'Student Individual Inventory')
<x-app-layout :fit-viewport="true">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="font-heading font-semibold text-base text-foreground leading-tight truncate">
                {{ __('Student Individual Inventory') }}
            </h1>
            <p class="text-xs font-medium text-muted-foreground shrink-0">
                {{ __('A.Y. :ay · :year Year', ['ay' => $submission->academic_year, 'year' => auth()->user()->year_level ?: '']) }}
            </p>
        </div>
    </x-slot>

    @php
        $totalCategories = count($inventoryConfig);
        $totalDisplaySections = $totalCategories + 1;
        $sectionTitles = [0 => 'Consent'];
        $sectionMeta = [];
        $initialAnswers = [];
        $sectionInstructions = [];
        $pageSize = 3;
        $sIdx = 1;
        foreach ($inventoryConfig as $catItem) {
            $sectionTitles[$sIdx] = $catItem->label();
            $catResponses = $existingResponses[$catItem->name] ?? [];
            $itemNums = [];
            $firstUnansweredIdx = 0;
            $foundUnanswered = false;
            foreach ($catItem->questionItems->values() as $qPos => $qObj) {
                $itemNums[] = (int) $qObj->item_number;
                $savedVal = (string) old('responses.'.$catItem->name.'.'.$qObj->item_number, $catResponses[$qObj->item_number] ?? '');
                $initialAnswers[$catItem->name.'|'.$qObj->item_number] = $savedVal;
                $hasAns = $savedVal !== '';
                if (! $foundUnanswered && ! $hasAns) {
                    $firstUnansweredIdx = $qPos;
                    $foundUnanswered = true;
                }
            }
            $instText = $catItem->instructions ?: 'Read each item carefully and choose the answer that best applies to you.';
            if ($catItem->name === 'cat') {
                $instText .= ' Indicate how you generally felt during the past two weeks.';
            }
            $sectionInstructions[$catItem->name] = $instText;
            $snappedInitialPage = $foundUnanswered ? (int) (floor($firstUnansweredIdx / $pageSize) * $pageSize) : 0;
            $sectionMeta[$sIdx] = [
                'code' => $catItem->name,
                'title' => $catItem->label(),
                'count' => count($itemNums),
                'items' => $itemNums,
                'initialQ' => $snappedInitialPage,
                'isNumericScale' => ($catItem->scale_min !== null && $catItem->scale_max !== null && $catItem->scale_type !== 'multiple_choice_unscored' && $catItem->scale_type !== 'multiple_choice'),
            ];
            $sIdx++;
        }
    @endphp

    <div x-data="inventoryForm()"
         @keydown.window="handleKeydown($event)"
         class="flex-1 min-h-0 flex flex-col w-full h-full">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
        <style>
            .font-dancing { font-family: 'Dancing Script', cursive; }
        </style>

        <!-- Below lg: Slim Top Bar + Bottom Sheet Trigger -->
        <div class="lg:hidden shrink-0 mb-2">
            <div class="bg-surface rounded-token-md border border-border px-3 py-1.5 shadow-2xs">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-primary">
                            <span>Section <span x-text="displayStep()">1</span> of <span x-text="totalDisplaySteps()">{{ $totalDisplaySections }}</span></span>
                            <span class="text-muted-foreground">&middot;</span>
                            <span class="tabular-nums text-muted-foreground" x-text="progressPercent() + '%'">0%</span>
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-foreground truncate" x-text="step === 0 ? 'Data Privacy Consent' : currentSectionTitle()">
                            Data Privacy Consent
                        </p>
                    </div>
                    <button type="button"
                            @click="toggleMobileSections()"
                            :aria-expanded="mobileSectionsOpen.toString()"
                            aria-controls="mobile-sections-sheet"
                            class="min-h-[44px] px-3 py-1.5 rounded-token-md border border-border bg-surface-subtle hover:bg-muted text-xs font-semibold text-primary inline-flex items-center gap-1.5 shrink-0 transition-colors cursor-pointer">
                        <span>Sections</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-200" :class="mobileSectionsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
                <div class="mt-1.5 w-full h-1.5 bg-muted rounded-full overflow-hidden" role="progressbar" :aria-valuenow="progressPercent()" aria-valuemin="0" aria-valuemax="100" aria-label="Overall section progress">
                    <div class="h-full bg-primary rounded-full transition-all duration-300" :style="'width: ' + progressPercent() + '%'"></div>
                </div>
            </div>

            <!-- Mobile Bottom Sheet -->
            <div x-show="mobileSectionsOpen"
                 x-cloak
                 class="fixed inset-0 z-50 lg:hidden"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Assessment sections">
                <div class="fixed inset-0 bg-foreground/50"
                     x-transition.opacity
                     @click="mobileSectionsOpen = false"></div>
                <div id="mobile-sections-sheet"
                     class="fixed inset-x-0 bottom-0 z-50 bg-surface rounded-t-2xl border-t border-border shadow-xl max-h-[78dvh] flex flex-col safe-bottom">
                    <div class="pt-2 pb-1 flex justify-center shrink-0" aria-hidden="true">
                        <span class="w-10 h-1 rounded-full bg-border-strong"></span>
                    </div>
                    <div class="px-4 py-2 border-b border-border flex items-center justify-between gap-2 shrink-0">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-foreground">Assessment Sections</p>
                            <p class="text-xs text-muted-foreground"><span x-text="completedSectionsCount()">0</span> of <span x-text="totalDisplaySteps()">{{ $totalDisplaySections }}</span> sections completed (<span x-text="progressPercent() + '%'">0%</span>)</p>
                        </div>
                        <button type="button" @click="mobileSectionsOpen = false" class="min-h-[44px] min-w-[44px] px-3 text-sm font-semibold text-primary hover:bg-muted rounded-token-md cursor-pointer">
                            Close
                        </button>
                    </div>
                    <div x-ref="mobileRailList" class="flex-1 min-h-0 overflow-y-auto p-2.5 space-y-1">
                        @include('inventory.partials._step_nav', [
                            'variant' => 'mobile',
                            'inventoryConfig' => $inventoryConfig,
                            'totalDisplaySections' => $totalDisplaySections,
                        ])
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified No-Scroll Container: 240px Compact Rail (lg+) + Active Question Stage -->
        <div class="flex-1 min-h-0 bg-surface rounded-2xl border border-border shadow-xs overflow-hidden flex flex-col lg:flex-row">

            <!-- Desktop Compact Section Rail (240px = lg:w-60, no text pills) -->
            <aside aria-label="Assessment Sections" class="hidden lg:flex lg:flex-col lg:w-[260px] shrink-0 border-r border-border bg-white h-full overflow-hidden">
                <div class="px-3.5 py-3 border-b border-border bg-surface shrink-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Sections</span>
                        <span class="text-xs font-bold text-primary tabular-nums" x-text="'Section ' + displayStep() + ' of ' + totalDisplaySteps()">Section 1 of {{ $totalDisplaySections }}</span>
                    </div>
                    <div class="mt-2 space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-medium text-muted-foreground">
                            <span><span x-text="completedSectionsCount()">0</span> of <span x-text="totalDisplaySteps()">{{ $totalDisplaySections }}</span> sections completed</span>
                            <span class="tabular-nums font-semibold" x-text="progressPercent() + '%'">0%</span>
                        </div>
                        <div class="w-full h-1.5 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all duration-300" :style="'width: ' + progressPercent() + '%'"></div>
                        </div>
                    </div>
                </div>

                <div x-ref="desktopRailList" class="flex-1 min-h-0 overflow-y-auto p-2 space-y-0.5 focus:outline-none">
                    @include('inventory.partials._step_nav', [
                        'variant' => 'rail',
                        'inventoryConfig' => $inventoryConfig,
                        'totalDisplaySections' => $totalDisplaySections,
                    ])
                </div>
            </aside>

            <!-- RIGHT STAGE: Active Section / Question -->
            <div class="flex-1 min-w-0 min-h-0 flex flex-col bg-surface">
                <form id="inventoryForm" method="POST" action="{{ route('inventory.store') }}" @change="handleFormChange($event)" class="flex-1 min-h-0 flex flex-col">
                    @csrf

                    <!-- Server Error Alert (if redirected back on final submit) -->
                    @if ($errors->any())
                        <div class="mx-3 sm:mx-5 mt-2 bg-destructive-subtle border-l-4 border-destructive text-destructive-foreground px-3 py-2 rounded-r-token-md shrink-0" role="alert">
                            <p class="text-xs sm:text-sm font-medium">Please complete all required fields before submitting.</p>
                        </div>
                    @endif

                    <!-- Dynamic Validation Error Banner -->
                    <div x-ref="errorAlert"
                         tabindex="-1"
                         aria-live="assertive"
                         x-show="errorMessage && errorMessage !== 'question'"
                         x-cloak
                         class="mx-3 sm:mx-5 mt-2 bg-destructive-subtle border-l-4 border-destructive text-destructive-foreground px-3 py-2 rounded-r-token-md shrink-0 focus:outline-none"
                         role="alert">
                        <p class="text-xs sm:text-sm font-medium" x-text="errorMessageText()"></p>
                    </div>

                    <!-- ==================== SECTION 1 (Internal Step 0): CONSENT ==================== -->
                    <div x-show="step === 0" data-category="consent" class="flex-1 min-h-0 flex flex-col">
                        <!-- Thin Consent Header -->
                        <div class="px-3.5 sm:px-5 py-2.5 border-b border-border flex items-center justify-between gap-2 shrink-0 bg-surface">
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-semibold text-primary truncate">
                                    Section 1 of {{ $totalDisplaySections }} &middot; Data Privacy Consent
                                </p>
                                <p class="text-[11px] sm:text-xs text-muted-foreground truncate">
                                    Review the RA 10173 privacy notice, check the agreement box, and sign to begin.
                                </p>
                            </div>
                            <!-- Sub-step indicator on narrow mobile (< md) -->
                            <div class="md:hidden shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-primary bg-primary-subtle px-2.5 py-1 rounded-full border border-primary/20">
                                <span x-text="consentSubStep === 'agree' ? '1 / 2 · Agree' : '2 / 2 · Sign'">1 / 2 · Agree</span>
                            </div>
                        </div>

                        <!-- Consent Body -->
                        <div class="flex-1 min-h-0 flex flex-col justify-between px-3.5 sm:px-6 py-2.5 sm:py-3.5 gap-2.5 sm:gap-3 overflow-hidden">
                            <!-- Part 1: Bounded Legal Text Scroll Box + Agreement Checkbox -->
                            <div x-show="isDesktopConsent || consentSubStep === 'agree'"
                                 class="flex-1 md:flex-initial min-h-0 flex flex-col gap-2.5">
                                <div tabindex="0"
                                     role="region"
                                     aria-label="Data Privacy Act of 2012 notice"
                                     class="flex-1 md:flex-initial md:max-h-[135px] xl:max-h-[175px] min-h-[76px] overflow-y-auto bg-surface-subtle px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-token-md border border-border text-xs sm:text-sm text-foreground/90 leading-relaxed space-y-2">
                                    <p class="font-bold text-foreground">Data Privacy Consent (RA 10173)</p>
                                    <p>In accordance with the Data Privacy Act of 2012 (RA 10173), I hereby grant my explicit consent to the City College of Tagaytay Guidance Office to collect, use, process, and disclose my personal data, including demographic profile, contact details, educational background, and psychological assessment results.</p>
                                    <p>I understand that this information will be used strictly for academic, guidance, and counseling purposes, aiming to support my well-being and development as a student.</p>
                                    <p>I agree to keep my information updated and acknowledge that my data will be handled with strict confidentiality and security.</p>
                                </div>

                                <label class="shrink-0 min-h-[44px] flex items-center gap-3 px-3.5 py-2 bg-primary-subtle/60 border-2 border-primary/25 rounded-token-md cursor-pointer hover:bg-primary-subtle has-[:checked]:border-primary has-[:checked]:bg-primary-subtle focus-within:ring-2 focus-within:ring-primary transition-colors">
                                    <input type="checkbox"
                                           x-model="consentChecked"
                                           @change="if (errorMessage === 'consent') errorMessage = ''"
                                           name="consent_checkbox"
                                           class="w-5 h-5 text-primary focus:ring-primary border-border-strong rounded shrink-0 cursor-pointer">
                                    <span class="text-xs sm:text-sm text-foreground font-semibold leading-snug">
                                        I have read, understood, and agree to the above data privacy terms in accordance with RA 10173.
                                    </span>
                                </label>
                            </div>

                            <!-- Part 2: Signature Tabs + Pad / Typed Input -->
                            <div x-show="isDesktopConsent || consentSubStep === 'sign'"
                                 x-cloak
                                 class="flex-1 md:flex-initial min-h-0 flex flex-col justify-center">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2 shrink-0">
                                    <div role="tablist" aria-label="Signature method" class="inline-flex items-center rounded-token-md bg-muted p-0.5 border border-border">
                                        <button type="button"
                                                role="tab"
                                                :aria-selected="(signatureType === 'drawn').toString()"
                                                @click="signatureType = 'drawn'; $nextTick(() => ensureSignaturePad())"
                                                :class="signatureType === 'drawn' ? 'bg-surface text-primary font-bold shadow-2xs' : 'text-muted-foreground hover:text-foreground font-medium'"
                                                class="min-h-[44px] px-3 py-1.5 rounded-token-sm text-xs sm:text-sm transition-colors cursor-pointer">
                                            Draw Signature
                                        </button>
                                        <button type="button"
                                                role="tab"
                                                :aria-selected="(signatureType === 'typed').toString()"
                                                @click="signatureType = 'typed'; updateTypedSignature()"
                                                :class="signatureType === 'typed' ? 'bg-surface text-primary font-bold shadow-2xs' : 'text-muted-foreground hover:text-foreground font-medium'"
                                                class="min-h-[44px] px-3 py-1.5 rounded-token-sm text-xs sm:text-sm transition-colors cursor-pointer">
                                            Type Signature
                                        </button>
                                    </div>

                                    <button type="button"
                                            x-show="signatureType === 'drawn'"
                                            @click="clearSignature()"
                                            class="min-h-[44px] px-3 py-1.5 text-xs sm:text-sm font-semibold bg-surface-subtle text-foreground rounded-token-md border border-border hover:bg-muted cursor-pointer">
                                        Clear Signature
                                    </button>
                                </div>

                                <input type="hidden" name="signature_type" :value="signatureType">
                                <input type="hidden" name="signature_data" :value="signatureData">
                                <input type="hidden" name="signature_font" :value="signatureFont">

                                <div x-show="signatureType === 'drawn'">
                                    <div class="border border-border-strong bg-white rounded-token-md shadow-inner relative overflow-hidden" style="touch-action: none;">
                                        <canvas id="signature-pad" class="w-full h-[112px] sm:h-[124px] cursor-crosshair block"></canvas>
                                    </div>
                                </div>

                                <div x-show="signatureType === 'typed'" x-cloak>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 items-center">
                                        <div>
                                            <label for="typed_signature_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Type your full name</label>
                                            <input id="typed_signature_name"
                                                   type="text"
                                                   x-model="typedName"
                                                   @input="updateTypedSignature()"
                                                   class="w-full min-h-[44px] rounded-token-md border-border-strong bg-surface focus:ring-primary focus:border-primary text-sm"
                                                   placeholder="Juan Dela Cruz">
                                        </div>
                                        <div class="px-4 py-2 bg-surface-subtle border border-border rounded-token-md flex items-center justify-center min-h-[72px] shadow-inner">
                                            <span x-text="typedName" class="font-dancing text-2xl sm:text-3xl text-primary empty:hidden break-all text-center"></span>
                                            <span x-show="!typedName" class="text-muted-foreground italic text-xs">Signature Preview</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consent Bottom Action Bar -->
                        <div class="px-3.5 sm:px-6 py-2.5 border-t border-border bg-surface shrink-0 flex items-center justify-between gap-2">
                            <!-- Mobile (< md): sub-screen navigation -->
                            <div class="flex md:hidden items-center justify-between gap-2 w-full">
                                <template x-if="consentSubStep === 'agree'">
                                    <div class="flex items-center justify-end gap-2 w-full">
                                        <template x-if="maxStep > 0">
                                            <button type="button" @click="goToStep(maxStep)" class="btn-secondary min-h-[44px] px-3.5 text-xs cursor-pointer">
                                                Resume &rarr;
                                            </button>
                                        </template>
                                        <button type="button"
                                                @click="openMobileSignStep()"
                                                :disabled="!consentChecked"
                                                class="btn-primary min-h-[44px] px-5 text-xs sm:text-sm flex-1 justify-center disabled:opacity-50 cursor-pointer">
                                            Continue to Sign &rarr;
                                        </button>
                                    </div>
                                </template>
                                <template x-if="consentSubStep === 'sign'">
                                    <div class="flex items-center justify-between gap-2 w-full">
                                        <button type="button" @click="consentSubStep = 'agree'" class="btn-secondary min-h-[44px] px-4 text-xs sm:text-sm cursor-pointer">
                                            &larr; Back
                                        </button>
                                        <button type="button"
                                                @click="nextStep('consent')"
                                                :disabled="!consentChecked || isSubmitting"
                                                class="btn-primary min-h-[44px] px-5 text-xs sm:text-sm flex-1 justify-center disabled:opacity-50 cursor-pointer">
                                            <span x-show="!isSubmitting">Save &amp; Continue &rarr;</span>
                                            <span x-show="isSubmitting" x-cloak>Validating&hellip;</span>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- Tablet / Desktop (md+): single-screen footer -->
                            <div class="hidden md:flex items-center justify-between gap-3 w-full">
                                <p class="text-xs text-muted-foreground truncate">
                                    <span x-show="!consentChecked">Check the agreement box and sign above to continue.</span>
                                    <span x-show="consentChecked" x-cloak class="text-success-foreground font-semibold">&#10003; Privacy agreement checked</span>
                                </p>
                                <div class="flex items-center gap-2.5 shrink-0">
                                    <template x-if="maxStep > 0">
                                        <button type="button" @click="goToStep(maxStep)" class="btn-secondary min-h-[44px] px-4 text-sm cursor-pointer">
                                            Return to Current Section &rarr;
                                        </button>
                                    </template>
                                    <button type="button"
                                            @click="nextStep('consent')"
                                            :disabled="!consentChecked || isSubmitting"
                                            class="btn-primary min-h-[44px] px-6 text-sm disabled:opacity-50 cursor-pointer">
                                        <span x-show="!isSubmitting">Save &amp; Continue &rarr;</span>
                                        <span x-show="isSubmitting" x-cloak>Validating&hellip;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== SECTIONS 2..N: ONE QUESTION AT A TIME ==================== -->
                    @php $stepIndex = 1; @endphp
                    @foreach($inventoryConfig as $data)
                        @php
                            $sectionTitle = $data->label();
                            $displayNum = $stepIndex + 1;
                            $sectionPrimaryName = $sectionTitle;
                            $sectionShortCode = null;
                            if (preg_match('/^(.+?)\s*\(([^)]+)\)$/', $sectionTitle, $matches)) {
                                $sectionPrimaryName = trim($matches[1]);
                                $sectionShortCode = trim($matches[2]);
                            }
                            $qItems = $data->questionItems->values();
                            $qTotal = $qItems->count();
                            $instructionText = $sectionInstructions[$data->name] ?? 'Read each item carefully and choose the answer that best applies to you.';
                        @endphp
                        <div x-show="step === {{ $stepIndex }}"
                             x-cloak
                             data-category="{{ $data->name }}"
                             class="flex-1 min-h-0 flex flex-col">

                            <!-- Clean 1-line Section + Question Header -->
                            <div class="px-4 sm:px-6 py-2.5 sm:py-3 border-b border-border bg-surface shrink-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="hidden lg:block text-xs sm:text-sm font-semibold text-primary truncate" title="{{ $sectionTitle }}">
                                            Section {{ $displayNum }} of {{ $totalDisplaySections }} &middot; {{ $sectionTitle }}
                                            @if($sectionShortCode)
                                                <span class="sr-only">{{ $sectionShortCode }}</span>
                                            @endif
                                        </p>
                                        <p class="lg:hidden text-xs font-semibold text-primary truncate" title="{{ $sectionPrimaryName }}">
                                            {{ $sectionPrimaryName }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 flex items-center gap-2 sm:gap-3">
                                        <button type="button"
                                                @click="toggleInstructions('{{ $data->name }}')"
                                                :aria-expanded="isInstructionsVisible('{{ $data->name }}').toString()"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-token-sm border border-border bg-surface-subtle hover:bg-muted text-[11px] sm:text-xs font-medium text-foreground transition-colors cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.75.75 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.75.75 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                            </svg>
                                            <span>Instructions</span>
                                        </button>
                                        <span class="text-xs sm:text-sm font-bold text-foreground tabular-nums"
                                              x-text="questionRangeLabel({{ $qTotal }})">
                                            @if($qTotal <= 1)
                                                Question 1 of {{ $qTotal }}
                                            @else
                                                Questions 1&ndash;{{ min($pageSize, $qTotal) }} of {{ $qTotal }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2 w-full h-1.5 bg-muted rounded-full overflow-hidden" role="progressbar" :aria-valuenow="Math.min({{ max(1, $qTotal) }}, qIndex + pageSize)" aria-valuemin="1" aria-valuemax="{{ max(1, $qTotal) }}" aria-label="Section question progress">
                                    <div class="h-full bg-primary rounded-full transition-all duration-200" :style="'width: ' + questionProgressPercent({{ max(1, $qTotal) }}) + '%'"></div>
                                </div>
                            </div>

                            <!-- 3-Questions-Per-Page Stage (only element that scrolls internally if content exceeds viewport) -->
                            <div x-ref="stage_{{ $stepIndex }}"
                                 class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 flex flex-col">
                                <div class="w-full max-w-3xl mx-auto my-auto flex flex-col gap-4 sm:gap-5">
                                    <!-- Dismissible Section Instructions Note -->
                                    <div x-show="isInstructionsVisible('{{ $data->name }}')"
                                         x-cloak
                                         class="shrink-0 bg-primary-subtle/70 border border-primary/20 rounded-token-md px-4 py-3.5 flex items-start justify-between gap-4">
                                        <div class="min-w-0 text-xs sm:text-sm text-foreground leading-relaxed">
                                            <span class="font-semibold text-primary">Instructions:</span>
                                            <span>{{ $instructionText }}</span>
                                        </div>
                                        <button type="button"
                                                @click="dismissInstructions('{{ $data->name }}')"
                                                aria-label="Dismiss instructions"
                                                class="shrink-0 text-xs font-medium text-primary hover:underline px-1.5 py-0.5 cursor-pointer">
                                            Dismiss
                                        </button>
                                    </div>

                                    @foreach($qItems as $qIdx => $item)
                                        @php
                                            $itemOptions = $item->options ?: ($data->default_options ?? []);
                                            $isMc = ($data->scale_type === 'multiple_choice_unscored' || $data->scale_type === 'multiple_choice' || ($data->scale_min === null && !empty($itemOptions)));
                                            $savedValue = (string) old('responses.'.$data->name.'.'.$item->item_number, $existingResponses[$data->name][$item->item_number] ?? '');
                                        @endphp
                                        <div x-show="qIndex <= {{ $qIdx }} && {{ $qIdx }} < (qIndex + pageSize)"
                                             @if($qIdx >= $pageSize) x-cloak @endif
                                             :class="hasQuestionError('{{ $data->name }}', {{ $item->item_number }}) ? 'border-border border-l-4 border-l-destructive bg-destructive-subtle/15' : 'border-border/80 bg-surface'"
                                             class="rounded-token-lg border p-5 sm:p-6 transition-colors">
                                            <fieldset data-q-index="{{ $qIdx }}"
                                                      data-item-number="{{ $item->item_number }}"
                                                      tabindex="-1"
                                                      :aria-invalid="hasQuestionError('{{ $data->name }}', {{ $item->item_number }}).toString()"
                                                      :aria-describedby="hasQuestionError('{{ $data->name }}', {{ $item->item_number }}) ? 'question-inline-error-{{ $data->name }}-{{ $item->item_number }}' : null"
                                                      class="border-0 p-0 m-0 min-w-0 block focus:outline-none">
                                                <legend class="w-full p-0 m-0 mb-3.5 sm:mb-4">
                                                    <div class="flex items-start gap-3 text-sm sm:text-base lg:text-lg font-semibold text-foreground leading-snug break-words">
                                                        <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-primary-subtle text-primary border border-primary/20 font-bold text-xs tabular-nums mt-0.5">{{ $item->item_number }}</span>
                                                        <div class="min-w-0 flex-1">
                                                            <span>{{ $item->prompt }}</span>
                                                            @if($item->subcategory)
                                                                <span class="inline-block ml-1.5 px-2 py-0.5 bg-surface-subtle text-muted-foreground text-[11px] font-semibold rounded-token-sm border border-border align-middle">
                                                                    {{ $item->subcategory->name }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </legend>

                                            <input type="hidden"
                                                   id="timing_{{ $data->name }}_{{ $item->item_number }}"
                                                   name="timings[{{ $data->name }}][{{ $item->item_number }}]"
                                                   value="{{ $existingTimings[$data->name][$item->item_number] ?? 0 }}">

                                            <!-- Answer Options -->
                                            <div class="mt-3.5 sm:mt-4">
                                                @if($isMc && !empty($itemOptions))
                                                    @php
                                                        $optCount = count($itemOptions);
                                                        $mcGridCols = $optCount === 2 ? 'grid-cols-2' : ($optCount > 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1');
                                                    @endphp
                                                    <div class="grid {{ $mcGridCols }} gap-3">
                                                        @foreach($itemOptions as $optionIndex => $optionText)
                                                            <label :class="isSelected('{{ $data->name }}', {{ $item->item_number }}, @js((string) $optionText)) ? 'bg-primary-subtle border-primary ring-1 ring-primary' : 'border-border bg-surface hover:bg-surface-subtle hover:border-border-strong'"
                                                                   class="group flex items-center justify-between gap-3 min-h-[48px] px-4 py-2.5 sm:py-3 border rounded-token-md cursor-pointer transition-colors focus-within:ring-2 focus-within:ring-primary">
                                                                <div class="flex items-center gap-2.5 min-w-0">
                                                                    <input type="radio"
                                                                           name="responses[{{ $data->name }}][{{ $item->item_number }}]"
                                                                           value="{{ $optionText }}"
                                                                           data-key-index="{{ $optionIndex + 1 }}"
                                                                           data-category="{{ $data->name }}"
                                                                           data-item-number="{{ $item->item_number }}"
                                                                           class="w-4 h-4 text-primary border-border-strong focus:ring-primary focus:ring-offset-0 shrink-0 cursor-pointer"
                                                                           {{ $savedValue === (string) $optionText ? 'checked' : '' }}>
                                                                    @if($optionIndex < 9)
                                                                        <span :class="isSelected('{{ $data->name }}', {{ $item->item_number }}, @js((string) $optionText)) ? 'bg-primary text-on-primary' : 'bg-muted text-muted-foreground'"
                                                                              class="hidden sm:inline-flex items-center justify-center w-5 h-5 rounded text-[11px] font-bold shrink-0 tabular-nums transition-colors"
                                                                              aria-hidden="true">{{ $optionIndex + 1 }}</span>
                                                                    @endif
                                                                    <span class="text-xs sm:text-sm text-foreground font-medium break-words leading-snug">{{ $optionText }}</span>
                                                                </div>
                                                                <span :class="isSelected('{{ $data->name }}', {{ $item->item_number }}, @js((string) $optionText)) ? 'inline-flex' : 'hidden'"
                                                                      class="items-center justify-center w-5 h-5 rounded-full bg-primary text-on-primary shrink-0"
                                                                      aria-hidden="true">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @elseif($data->scale_min !== null && $data->scale_max !== null)
                                                    @php
                                                        $scaleLabels = $data->scale_labels ?? [];
                                                        if (empty($scaleLabels)) {
                                                            $lowerName = strtolower($data->name);
                                                            if ($lowerName === 'dass21') $scaleLabels = [0 => 'Did not apply', 1 => 'Some degree', 2 => 'Considerable', 3 => 'Very much'];
                                                            elseif ($lowerName === 'erq') $scaleLabels = [1 => 'Strongly disagree', 4 => 'Neutral', 7 => 'Strongly agree'];
                                                            elseif ($lowerName === 'ars30') $scaleLabels = [1 => 'Not like me', 5 => 'Very much like me'];
                                                            elseif ($lowerName === 'cat') $scaleLabels = [1 => 'Not at all', 4 => 'Somewhat', 7 => 'Very much'];
                                                            elseif ($lowerName === 'ffmq') $scaleLabels = [1 => 'Never / rarely true', 5 => 'Very often / always'];
                                                        }
                                                        $scaleCount = ($data->scale_max - $data->scale_min) + 1;
                                                        $mobileCols = $scaleCount <= 4 ? 'grid-cols-2' : 'grid-cols-3';
                                                        $desktopCols = match ($scaleCount) {
                                                            2 => 'sm:grid-cols-2',
                                                            3 => 'sm:grid-cols-3',
                                                            4 => 'sm:grid-cols-4',
                                                            5 => 'sm:grid-cols-5',
                                                            6 => 'sm:grid-cols-6',
                                                            7 => 'sm:grid-cols-7',
                                                            default => 'sm:grid-cols-4',
                                                        };
                                                    @endphp
                                                    <div class="grid {{ $mobileCols }} {{ $desktopCols }} gap-2">
                                                        @for($i = $data->scale_min; $i <= $data->scale_max; $i++)
                                                            @php
                                                                $rawScaleLabel = isset($scaleLabels[$i]) ? (string) $scaleLabels[$i] : '';
                                                                $shortScaleLabel = \Illuminate\Support\Str::limit($rawScaleLabel, 34);
                                                            @endphp
                                                            <label title="{{ $rawScaleLabel ?: $i }}"
                                                                   :class="isSelected('{{ $data->name }}', {{ $item->item_number }}, '{{ $i }}') ? 'bg-primary-subtle border-primary ring-2 ring-primary' : 'border-border bg-surface hover:bg-surface-subtle hover:border-border-strong'"
                                                                   class="group relative flex flex-col items-center justify-center text-center gap-1 min-h-[54px] sm:min-h-[68px] px-1.5 py-2 sm:px-2 sm:py-2.5 border rounded-token-md cursor-pointer transition-colors focus-within:ring-2 focus-within:ring-primary">
                                                                <input type="radio"
                                                                       name="responses[{{ $data->name }}][{{ $item->item_number }}]"
                                                                       value="{{ $i }}"
                                                                       data-scale-value="{{ $i }}"
                                                                       data-category="{{ $data->name }}"
                                                                       data-item-number="{{ $item->item_number }}"
                                                                       class="sr-only"
                                                                       {{ $savedValue === (string) $i ? 'checked' : '' }}>
                                                                <span :class="isSelected('{{ $data->name }}', {{ $item->item_number }}, '{{ $i }}') ? 'bg-primary text-on-primary border-primary' : 'border-border bg-surface-subtle text-foreground'"
                                                                      class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-full text-xs sm:text-sm font-bold tabular-nums border transition-colors">
                                                                    {{ $i }}
                                                                </span>
                                                                @if($shortScaleLabel !== '')
                                                                    <span :class="isSelected('{{ $data->name }}', {{ $item->item_number }}, '{{ $i }}') ? 'text-foreground font-semibold' : 'text-muted-foreground'"
                                                                          class="text-[11px] leading-tight line-clamp-2 break-words">
                                                                        {{ $shortScaleLabel }}
                                                                    </span>
                                                                @endif
                                                            </label>
                                                        @endfor
                                                    </div>
                                                @else
                                                    <div>
                                                        <label for="resp_{{ $data->name }}_{{ $item->item_number }}" class="sr-only">Your response</label>
                                                        <input id="resp_{{ $data->name }}_{{ $item->item_number }}"
                                                               type="text"
                                                               name="responses[{{ $data->name }}][{{ $item->item_number }}]"
                                                               value="{{ $savedValue }}"
                                                               data-category="{{ $data->name }}"
                                                               data-item-number="{{ $item->item_number }}"
                                                               @input="onTextInput('{{ $data->name }}', {{ $item->item_number }}, $event.target.value)"
                                                               class="input-field w-full min-h-[48px] text-sm sm:text-base"
                                                               placeholder="Type your answer...">
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Per-question inline required message directly under options -->
                                            <p id="question-inline-error-{{ $data->name }}-{{ $item->item_number }}"
                                               x-show="hasQuestionError('{{ $data->name }}', {{ $item->item_number }})"
                                               x-cloak
                                               class="mt-3 flex items-center gap-1.5 text-xs sm:text-sm font-medium text-destructive">
                                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" role="img" aria-label="Error">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Please choose an answer to continue.</span>
                                            </p>

                                            @error('responses.'.$data->name.'.'.$item->item_number)
                                                <p class="text-destructive text-xs sm:text-sm shrink-0">{{ $message }}</p>
                                            @enderror
                                            </fieldset>
                                        </div>
                                    @endforeach

                                    @if($data->scale_min !== null && $data->scale_max !== null)
                                        <p class="hidden sm:block text-[11px] text-muted-foreground text-center">
                                            Tip: Select an answer for each question above, then press <kbd class="px-1.5 py-0.5 rounded bg-muted border border-border font-mono text-[10px]">Next</kbd> or <kbd class="px-1.5 py-0.5 rounded bg-muted border border-border font-mono text-[10px]">Enter</kbd> to continue &middot; <kbd class="px-1.5 py-0.5 rounded bg-muted border border-border font-mono text-[10px]">&larr;</kbd> / <kbd class="px-1.5 py-0.5 rounded bg-muted border border-border font-mono text-[10px]">&rarr;</kbd> to navigate
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Bottom Action Bar (Always visible without page scroll) -->
                            <div class="px-4 sm:px-6 py-3 border-t border-border bg-surface shrink-0 flex items-center justify-between gap-2.5">
                                <button type="button"
                                        @click="prevQuestion()"
                                        :disabled="isSubmitting"
                                        class="btn-secondary min-h-[44px] px-4 sm:px-5 text-xs sm:text-sm justify-center cursor-pointer">
                                    &larr; Back
                                </button>

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            x-show="(qIndex + pageSize) < {{ $qTotal }}"
                                            @click="nextQuestion()"
                                            :disabled="isSubmitting"
                                            :class="isCurrentPageAnswered() ? 'btn-primary shadow-sm' : 'btn-secondary'"
                                            class="min-h-[44px] px-5 sm:px-7 text-xs sm:text-sm justify-center disabled:opacity-50 cursor-pointer">
                                        Next &rarr;
                                    </button>

                                    @if($stepIndex < count($inventoryConfig))
                                        <button type="button"
                                                x-show="(qIndex + pageSize) >= {{ $qTotal }}"
                                                x-cloak
                                                @click="nextStep('{{ $data->name }}')"
                                                :disabled="isSubmitting"
                                                :class="isCurrentPageAnswered() ? 'btn-primary shadow-sm' : 'btn-secondary'"
                                                class="min-h-[44px] px-5 sm:px-7 text-xs sm:text-sm justify-center disabled:opacity-50 cursor-pointer">
                                            <span x-show="!isSubmitting">Next section &rarr;</span>
                                            <span x-show="isSubmitting" x-cloak>Validating&hellip;</span>
                                        </button>
                                    @else
                                        <button type="submit"
                                                x-show="(qIndex + pageSize) >= {{ $qTotal }}"
                                                x-cloak
                                                @click="handleFinalSubmit($event, '{{ $data->name }}')"
                                                :disabled="isSubmitting"
                                                :class="isCurrentPageAnswered() ? 'btn-primary shadow-sm' : 'btn-secondary'"
                                                class="min-h-[44px] px-5 sm:px-7 text-xs sm:text-sm justify-center disabled:opacity-50 cursor-pointer">
                                            <span x-show="!isSubmitting">Finish</span>
                                            <span x-show="isSubmitting" x-cloak>Submitting&hellip;</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @php $stepIndex++; @endphp
                    @endforeach
                    <div aria-live="polite" class="sr-only" x-text="errorMessage === 'question' ? 'Error: Please choose an answer to continue.' : ''"></div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce }}">
        (function () {
            const registerInventoryForm = () => {
                if (!window.Alpine) return;
                Alpine.data('inventoryForm', () => ({
                    step: {{ $startStep ?? 0 }},
                    maxStep: {{ $startStep ?? 0 }},
                    totalSteps: {{ count($inventoryConfig) }},
                    pageSize: {{ $pageSize }},
                    sectionTitles: @json($sectionTitles),
                    sectionMeta: @json($sectionMeta),
                    answers: @json($initialAnswers),
                    invalidKeys: {},
                    instructionsDismissed: {},
                    instructionsToggled: {},
                    qIndex: 0,
                    sectionQuestionCursor: {},
                    questionShownAt: {},
                    lastPageInteractionAt: null,
                    mobileSectionsOpen: false,
                    consentSubStep: @json($submission->consent_given_at ? 'sign' : 'agree'),
                    isDesktopConsent: window.innerWidth >= 768,
                    errorMessage: '',
                    isSubmitting: false,

                    consentChecked: @json(!!$submission->consent_given_at),
                    signatureType: @json($submission->signature_type ?? 'drawn'),
                    signatureData: @json($submission->signature_data ?? ''),
                    signatureFont: @json($submission->signature_font ?? 'Dancing Script'),
                    typedName: @json($submission->signature_type === 'typed' ? ($submission->signature_data ?? '') : ''),
                    pad: null,

                    init() {
                        if (this.step > this.maxStep) {
                            this.maxStep = this.step;
                        }

                        Object.entries(this.sectionMeta).forEach(([idx, meta]) => {
                            this.sectionQuestionCursor[idx] = meta.initialQ || 0;
                        });

                        if (this.step > 0 && this.sectionMeta[this.step]) {
                            this.qIndex = this.sectionQuestionCursor[this.step] || 0;
                        }

                        const mq = window.matchMedia('(min-width: 768px)');
                        const updateMq = (e) => {
                            this.isDesktopConsent = e.matches;
                            if (this.step === 0 && (this.isDesktopConsent || this.consentSubStep === 'sign')) {
                                this.$nextTick(() => this.ensureSignaturePad());
                            }
                        };
                        if (mq.addEventListener) {
                            mq.addEventListener('change', updateMq);
                        }

                        this.$watch('errorMessage', value => {
                            if (value && value !== 'question') {
                                this.$nextTick(() => {
                                    this.$refs.errorAlert?.focus();
                                });
                            }
                        });

                        this.$watch('qIndex', value => {
                            if (this.step > 0) {
                                this.sectionQuestionCursor[this.step] = value;
                                this.markPageQuestionsShown();
                                this.$nextTick(() => this.scrollStageToTop());
                            }
                        });

                        this.$watch('step', value => {
                            this.invalidKeys = {};
                            if (value > this.maxStep) {
                                this.maxStep = value;
                            }
                            this.mobileSectionsOpen = false;
                            if (value > 0 && this.sectionMeta[value]) {
                                const count = this.sectionMeta[value].count || 1;
                                const maxStart = Math.max(0, Math.floor((count - 1) / this.pageSize) * this.pageSize);
                                const savedCursor = this.sectionQuestionCursor[value] ?? 0;
                                this.qIndex = Math.min(maxStart, Math.max(0, Math.floor(savedCursor / this.pageSize) * this.pageSize));
                                this.instructionsToggled[this.sectionMeta[value].code] = false;
                                this.markPageQuestionsShown();
                            }
                            this.$nextTick(() => {
                                this.scrollActiveStepIntoView();
                                this.scrollStageToTop();
                                if (value === 0 && (this.isDesktopConsent || this.consentSubStep === 'sign')) {
                                    this.ensureSignaturePad();
                                }
                            });
                        });

                        this.markPageQuestionsShown();
                        setTimeout(() => {
                            if (this.step === 0 && (this.isDesktopConsent || this.consentSubStep === 'sign')) {
                                this.ensureSignaturePad();
                            }
                            this.scrollActiveStepIntoView();
                        }, 80);
                    },

                    scrollStageToTop() {
                        const stage = this.$refs[`stage_${this.step}`];
                        if (stage) {
                            stage.scrollTop = 0;
                        }
                    },

                    isInstructionsVisible(category) {
                        if (this.instructionsToggled[category]) {
                            return true;
                        }
                        return this.qIndex === 0 && !this.instructionsDismissed[category];
                    },

                    toggleInstructions(category) {
                        if (this.isInstructionsVisible(category)) {
                            this.instructionsToggled[category] = false;
                            if (this.qIndex === 0) {
                                this.instructionsDismissed[category] = true;
                            }
                        } else {
                            this.instructionsDismissed[category] = false;
                            this.instructionsToggled[category] = true;
                        }
                    },

                    dismissInstructions(category) {
                        this.instructionsDismissed[category] = true;
                        this.instructionsToggled[category] = false;
                    },

                    hasQuestionError(category, itemNumber) {
                        return Boolean(this.invalidKeys[`${category}|${itemNumber}`]);
                    },

                    clearQuestionError(category, itemNumber) {
                        const key = `${category}|${itemNumber}`;
                        if (this.invalidKeys[key]) {
                            delete this.invalidKeys[key];
                        }
                        if (Object.keys(this.invalidKeys).length === 0 && this.errorMessage === 'question') {
                            this.errorMessage = '';
                        }
                    },

                    isSelected(category, itemNumber, optionValue) {
                        const key = `${category}|${itemNumber}`;
                        return (this.answers[key] ?? '') === String(optionValue);
                    },

                    onTextInput(category, itemNumber, value) {
                        this.clearQuestionError(category, itemNumber);
                        this.answers[`${category}|${itemNumber}`] = String(value ?? '');
                        this.recordTiming(category, itemNumber);
                    },

                    handleFormChange(event) {
                        const el = event?.target;
                        if (!el || el.tagName !== 'INPUT') return;
                        const category = el.dataset.category;
                        const itemNumber = Number(el.dataset.itemNumber);
                        if (!category || !itemNumber) return;

                        const key = `${category}|${itemNumber}`;
                        if (el.type === 'radio') {
                            if (!el.checked) return;
                            this.clearQuestionError(category, itemNumber);
                            this.answers[key] = String(el.value);
                            this.recordTiming(category, itemNumber);
                        } else if (el.type !== 'checkbox' && el.type !== 'hidden') {
                            this.clearQuestionError(category, itemNumber);
                            this.answers[key] = String(el.value ?? '');
                            this.recordTiming(category, itemNumber);
                        }
                    },

                    openMobileSignStep() {
                        if (!this.consentChecked) {
                            this.errorMessage = 'consent';
                            return;
                        }
                        this.errorMessage = '';
                        this.consentSubStep = 'sign';
                        this.$nextTick(() => {
                            setTimeout(() => this.ensureSignaturePad(), 40);
                        });
                    },

                    errorMessageText() {
                        if (this.errorMessage === 'consent') {
                            return 'Please confirm your agreement to the data privacy terms and provide your signature before continuing.';
                        }
                        if (this.errorMessage === 'question') {
                            return 'Please choose an answer to continue.';
                        }
                        return 'Please answer all required questions in this section before continuing.';
                    },

                    currentSectionTitle() {
                        return this.sectionTitles[this.step] || '';
                    },

                    totalDisplaySteps() {
                        return {{ $totalDisplaySections }};
                    },

                    displayStep() {
                        return Math.min(this.totalDisplaySteps(), this.step + 1);
                    },

                    completedSectionsCount() {
                        return Math.min(this.totalDisplaySteps(), Math.max(this.step, this.maxStep));
                    },

                    progressPercent() {
                        const total = this.totalDisplaySteps();
                        if (!total) return 0;
                        return Math.min(100, Math.round((this.completedSectionsCount() / total) * 100));
                    },

                    questionRangeLabel(totalQuestions) {
                        if (!totalQuestions) return 'Question 0 of 0';
                        const start = Math.min(totalQuestions, this.qIndex + 1);
                        const end = Math.min(totalQuestions, this.qIndex + this.pageSize);
                        return start === end
                            ? `Question ${start} of ${totalQuestions}`
                            : `Questions ${start}\u2013${end} of ${totalQuestions}`;
                    },

                    questionProgressPercent(totalQuestions) {
                        if (!totalQuestions) return 100;
                        const covered = Math.min(totalQuestions, this.qIndex + this.pageSize);
                        return Math.min(100, Math.round((covered / totalQuestions) * 100));
                    },

                    markPageQuestionsShown() {
                        if (this.step <= 0 || !this.sectionMeta[this.step]) return;
                        const meta = this.sectionMeta[this.step];
                        const now = Date.now();
                        this.lastPageInteractionAt = now;
                        const end = Math.min(meta.count, this.qIndex + this.pageSize);
                        for (let idx = this.qIndex; idx < end; idx++) {
                            const itemNum = meta.items[idx];
                            if (itemNum) {
                                this.questionShownAt[`${meta.code}|${itemNum}`] = now;
                            }
                        }
                    },

                    isCurrentPageAnswered() {
                        if (this.step <= 0 || !this.sectionMeta[this.step]) return false;
                        const meta = this.sectionMeta[this.step];
                        const end = Math.min(meta.count, this.qIndex + this.pageSize);
                        for (let idx = this.qIndex; idx < end; idx++) {
                            if (!this.isQuestionAnswered(this.step, idx)) {
                                return false;
                            }
                        }
                        return true;
                    },

                    isQuestionAnswered(stepNum, questionIdx) {
                        const meta = this.sectionMeta[stepNum];
                        if (!meta) return false;
                        const itemNum = meta.items[questionIdx];
                        if (!itemNum) return false;
                        const cached = this.answers[`${meta.code}|${itemNum}`];
                        if (cached !== undefined && String(cached).trim() !== '') {
                            return true;
                        }
                        const form = document.getElementById('inventoryForm');
                        if (!form) return false;
                        const fieldName = `responses[${meta.code}][${itemNum}]`;
                        const controls = form.querySelectorAll(`[name="${CSS.escape(fieldName)}"]`);
                        if (!controls.length) return true;
                        const first = controls[0];
                        if (first.type === 'radio' || first.type === 'checkbox') {
                            return Array.from(controls).some(el => el.checked);
                        }
                        return String(first.value || '').trim() !== '';
                    },

                    validateCurrentPageOrFlag() {
                        const meta = this.sectionMeta[this.step];
                        if (!meta) return true;
                        const end = Math.min(meta.count, this.qIndex + this.pageSize);
                        let firstMissingIdx = null;
                        for (let idx = this.qIndex; idx < end; idx++) {
                            const itemNum = meta.items[idx];
                            if (!this.isQuestionAnswered(this.step, idx)) {
                                if (firstMissingIdx === null) {
                                    firstMissingIdx = idx;
                                }
                                this.invalidKeys[`${meta.code}|${itemNum}`] = true;
                            } else if (itemNum) {
                                delete this.invalidKeys[`${meta.code}|${itemNum}`];
                            }
                        }
                        if (firstMissingIdx !== null) {
                            this.errorMessage = 'question';
                            this.$nextTick(() => {
                                const sectionEl = document.querySelector(`div[data-category="${meta.code}"]`);
                                sectionEl?.querySelector(`fieldset[data-q-index="${firstMissingIdx}"]`)?.focus();
                            });
                            return false;
                        }
                        return true;
                    },

                    nextQuestion() {
                        if (this.step === 0) {
                            if (!this.isDesktopConsent && this.consentSubStep === 'agree') {
                                this.openMobileSignStep();
                            } else {
                                this.nextStep('consent');
                            }
                            return;
                        }

                        const meta = this.sectionMeta[this.step];
                        if (!meta) return;

                        if (!this.validateCurrentPageOrFlag()) {
                            return;
                        }

                        this.errorMessage = '';
                        if ((this.qIndex + this.pageSize) < meta.count) {
                            this.qIndex += this.pageSize;
                        } else if (this.step < this.totalSteps) {
                            this.nextStep(meta.code);
                        }
                    },

                    prevQuestion() {
                        this.errorMessage = '';
                        this.invalidKeys = {};
                        if (this.step === 0) {
                            if (!this.isDesktopConsent && this.consentSubStep === 'sign') {
                                this.consentSubStep = 'agree';
                            }
                            return;
                        }

                        if (this.qIndex >= this.pageSize) {
                            this.qIndex = Math.max(0, this.qIndex - this.pageSize);
                            return;
                        }
                        if (this.qIndex > 0) {
                            this.qIndex = 0;
                            return;
                        }

                        if (this.step > 0) {
                            const prev = this.step - 1;
                            if (prev > 0 && this.sectionMeta[prev]) {
                                const prevCount = this.sectionMeta[prev].count || 1;
                                this.sectionQuestionCursor[prev] = Math.max(0, Math.floor((prevCount - 1) / this.pageSize) * this.pageSize);
                            } else if (prev === 0 && !this.isDesktopConsent) {
                                this.consentSubStep = 'sign';
                            }
                            this.step = prev;
                        }
                    },

                    handleKeydown(event) {
                        if (this.mobileSectionsOpen) {
                            if (event.key === 'Escape') {
                                this.mobileSectionsOpen = false;
                            }
                            return;
                        }
                        if (this.isSubmitting || event.ctrlKey || event.altKey || event.metaKey || event.isComposing) {
                            return;
                        }

                        const target = event.target;
                        const tag = target ? target.tagName : '';
                        const isTypingField = (tag === 'TEXTAREA' || tag === 'SELECT' || target?.isContentEditable || (tag === 'INPUT' && !['radio', 'checkbox'].includes(target.type)));
                        if (isTypingField) {
                            return;
                        }

                        if (event.key === 'ArrowLeft') {
                            event.preventDefault();
                            this.prevQuestion();
                            return;
                        }

                        if (event.key === 'ArrowRight' || event.key === 'Enter') {
                            event.preventDefault();
                            const meta = this.sectionMeta[this.step];
                            if (this.step === this.totalSteps && meta && (this.qIndex + this.pageSize) >= meta.count) {
                                if (!this.validateCurrentPageOrFlag()) {
                                    return;
                                }
                                for (let idx = 0; idx < meta.count; idx++) {
                                    if (!this.isQuestionAnswered(this.step, idx)) {
                                        this.qIndex = Math.floor(idx / this.pageSize) * this.pageSize;
                                        this.invalidKeys[`${meta.code}|${meta.items[idx]}`] = true;
                                        this.errorMessage = 'question';
                                        return;
                                    }
                                }
                                const form = document.getElementById('inventoryForm');
                                if (form) {
                                    this.submitForm();
                                    form.requestSubmit ? form.requestSubmit() : form.submit();
                                }
                                return;
                            }
                            this.nextQuestion();
                            return;
                        }

                        if (this.step >= 1 && /^[0-9]$/.test(event.key)) {
                            const meta = this.sectionMeta[this.step];
                            if (!meta) return;
                            const sectionEl = document.querySelector(`div[data-category="${meta.code}"]`);
                            let fieldset = target?.closest?.('fieldset[data-q-index]');
                            if (!fieldset || !sectionEl?.contains(fieldset)) {
                                const end = Math.min(meta.count, this.qIndex + this.pageSize);
                                for (let idx = this.qIndex; idx < end; idx++) {
                                    if (!this.isQuestionAnswered(this.step, idx)) {
                                        fieldset = sectionEl?.querySelector(`fieldset[data-q-index="${idx}"]`);
                                        break;
                                    }
                                }
                                if (!fieldset) {
                                    fieldset = sectionEl?.querySelector(`fieldset[data-q-index="${this.qIndex}"]`);
                                }
                            }
                            if (!fieldset) return;

                            let matchedRadio = null;
                            if (meta.isNumericScale) {
                                matchedRadio = fieldset.querySelector(`input[type="radio"][data-scale-value="${event.key}"]`);
                            } else if (event.key !== '0') {
                                matchedRadio = fieldset.querySelector(`input[type="radio"][data-key-index="${event.key}"]`);
                            }

                            if (matchedRadio) {
                                event.preventDefault();
                                this.clearQuestionError(meta.code, Number(matchedRadio.dataset.itemNumber));
                                if (!matchedRadio.checked) {
                                    matchedRadio.checked = true;
                                    matchedRadio.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }
                        }
                    },

                    toggleMobileSections() {
                        this.mobileSectionsOpen = !this.mobileSectionsOpen;
                        if (this.mobileSectionsOpen) {
                            this.$nextTick(() => {
                                this.scrollActiveStepIntoView();
                            });
                        }
                    },

                    goToStep(targetStep) {
                        if (targetStep <= Math.max(this.step, this.maxStep)) {
                            this.errorMessage = '';
                            this.invalidKeys = {};
                            this.step = targetStep;
                            this.mobileSectionsOpen = false;
                        }
                    },

                    scrollActiveStepIntoView() {
                        const centerInContainer = (container, item) => {
                            if (!container || !item) return;
                            const cRect = container.getBoundingClientRect();
                            const iRect = item.getBoundingClientRect();
                            const offset = (iRect.top - cRect.top) - (cRect.height / 2) + (iRect.height / 2);
                            container.scrollTop += offset;
                        };
                        const railList = this.$refs.desktopRailList;
                        if (railList) {
                            const activeRailBtn = railList.querySelector(`[data-rail-step="${this.step}"]`);
                            centerInContainer(railList, activeRailBtn);
                        }
                        const mobileList = this.$refs.mobileRailList;
                        if (mobileList) {
                            const activeMobileBtn = mobileList.querySelector(`[data-mobile-step="${this.step}"]`);
                            centerInContainer(mobileList, activeMobileBtn);
                        }
                    },

                    ensureSignaturePad() {
                        const canvas = document.getElementById('signature-pad');
                        if (!canvas || canvas.offsetWidth === 0) return;
                        if (this.pad && canvas.width > 0) return;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext("2d").scale(ratio, ratio);

                        this.pad = new window.SignaturePad(canvas, {
                            backgroundColor: 'rgb(255, 255, 255)'
                        });

                        if (this.signatureType === 'drawn' && this.signatureData) {
                            this.pad.fromDataURL(this.signatureData);
                        }

                        this.pad.addEventListener("endStroke", () => {
                            this.signatureData = this.pad.toDataURL('image/png');
                            if (this.errorMessage === 'consent') {
                                this.errorMessage = '';
                            }
                        });
                    },

                    clearSignature() {
                        if (this.pad) {
                            this.pad.clear();
                            this.signatureData = '';
                        }
                    },

                    updateTypedSignature() {
                        this.signatureData = (this.typedName || '').trim();
                        if (this.signatureData && this.errorMessage === 'consent') {
                            this.errorMessage = '';
                        }
                    },

                    async nextStep(category) {
                        if (this.isSubmitting) return;
                        this.errorMessage = '';
                        this.isSubmitting = true;

                        const form = document.getElementById('inventoryForm');
                        const formData = new FormData(form);

                        if (category === 'consent') {
                            if (!this.consentChecked || !this.signatureData) {
                                this.errorMessage = 'consent';
                                this.isSubmitting = false;
                                return;
                            }
                        } else {
                            if (!this.validateCurrentPageOrFlag()) {
                                this.isSubmitting = false;
                                return;
                            }
                            const meta = this.sectionMeta[this.step];
                            if (meta) {
                                for (let idx = 0; idx < meta.count; idx++) {
                                    if (!this.isQuestionAnswered(this.step, idx)) {
                                        this.qIndex = Math.floor(idx / this.pageSize) * this.pageSize;
                                        this.invalidKeys[`${meta.code}|${meta.items[idx]}`] = true;
                                        this.errorMessage = 'question';
                                        this.isSubmitting = false;
                                        return;
                                    }
                                }
                            }
                        }

                        formData.append('category', category);
                        try {
                            const response = await fetch('{{ route("inventory.validate-section") }}', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: formData
                            });

                            if (response.ok) {
                                this.invalidKeys = {};
                                if (this.step < this.totalSteps) {
                                    const nextSection = this.step + 1;
                                    if (this.sectionMeta[nextSection] && this.sectionQuestionCursor[nextSection] === undefined) {
                                        this.sectionQuestionCursor[nextSection] = 0;
                                    }
                                    this.step = nextSection;
                                }
                            } else {
                                this.errorMessage = category;
                            }
                        } catch (error) {
                            console.error('Validation error', error);
                            this.errorMessage = category;
                        } finally {
                            this.isSubmitting = false;
                        }
                    },

                    prevStep() {
                        this.prevQuestion();
                    },

                    recordTiming(category, itemNumber) {
                        const input = document.getElementById(`timing_${category}_${itemNumber}`);
                        if (!input) return;
                        if (input.value === "" || input.value === "0") {
                            const now = Date.now();
                            const key = `${category}|${itemNumber}`;
                            const shownAt = this.questionShownAt[key] || now;
                            const baseAt = Math.max(shownAt, this.lastPageInteractionAt || shownAt);
                            input.value = Math.max(1, now - baseAt);
                            this.lastPageInteractionAt = now;
                        }
                    },

                    handleFinalSubmit(event, category) {
                        if (!this.validateCurrentPageOrFlag()) {
                            event.preventDefault();
                            return;
                        }
                        const meta = this.sectionMeta[this.step];
                        if (meta) {
                            for (let idx = 0; idx < meta.count; idx++) {
                                if (!this.isQuestionAnswered(this.step, idx)) {
                                    event.preventDefault();
                                    this.qIndex = Math.floor(idx / this.pageSize) * this.pageSize;
                                    this.invalidKeys[`${meta.code}|${meta.items[idx]}`] = true;
                                    this.errorMessage = 'question';
                                    return;
                                }
                            }
                        }
                        this.submitForm();
                    },

                    submitForm() {
                        document.querySelectorAll('input[name^="timings"]').forEach(input => {
                            if (input.value === "") input.value = 0;
                        });
                    }
                }));
            };

            document.addEventListener('alpine:init', registerInventoryForm);
            if (window.Alpine) {
                registerInventoryForm();
            }
        })();
    </script>
    @endpush
</x-app-layout>
