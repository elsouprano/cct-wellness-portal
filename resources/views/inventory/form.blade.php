@section('title', 'Student Individual Inventory')
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Student Individual Inventory') }}
            </h2>
            <p class="text-sm text-foreground/60 mt-0.5">
                {{ __('A.Y. :ay · :year Year', ['ay' => $submission->academic_year, 'year' => auth()->user()->year_level ?: '']) }}
            </p>
        </div>
    </x-slot>

    @php
        $totalCategories = count($inventoryConfig);
        $totalDisplaySections = $totalCategories >= 16 ? 16 : $totalCategories + 1;
        $sectionTitles = [0 => 'Consent'];
        $sIdx = 1;
        foreach ($inventoryConfig as $catItem) {
            $sectionTitles[$sIdx] = $catItem->label();
            $sIdx++;
        }
    @endphp

    <div x-data="inventoryForm()" class="max-w-7xl mx-auto h-full">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
        <style>
            .font-dancing { font-family: 'Dancing Script', cursive; }
        </style>

        <!-- Below lg: Compact Sticky Top Bar + Section Sheet -->
        <div class="lg:hidden sticky top-0 z-20 mb-6">
            <div class="bg-white rounded-2xl border border-border shadow-md p-3.5 sm:p-4">
                <div class="flex items-center justify-between gap-2.5">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-primary">
                            Section <span x-text="displayStep()">1</span> of <span x-text="totalDisplaySteps()">{{ $totalDisplaySections }}</span>
                        </p>
                        <p class="text-sm font-bold text-foreground truncate mt-0.5" x-text="step === 0 ? 'Data Privacy Consent' : currentSectionTitle()">
                            Data Privacy Consent
                        </p>
                    </div>
                    <button type="button"
                            @click="toggleMobileSections()"
                            :aria-expanded="mobileSectionsOpen.toString()"
                            aria-controls="mobile-sections-sheet"
                            class="min-h-[44px] px-3 sm:px-3.5 py-2 rounded-xl border border-border bg-muted/50 hover:bg-muted text-sm font-semibold text-primary flex items-center gap-1.5 shrink-0 transition-colors cursor-pointer">
                        <span>View Sections</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-200" :class="mobileSectionsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Progress Bar -->
                <div class="mt-3 space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold text-foreground/70">
                        <span><span x-text="completedSectionsCount()">0</span> of <span x-text="totalDisplaySteps()">{{ $totalDisplaySections }}</span> sections completed</span>
                        <span x-text="progressPercent() + '%'">0%</span>
                    </div>
                    <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full transition-all duration-300" style="width: 0%;" :style="'width: ' + progressPercent() + '%'"></div>
                    </div>
                </div>
            </div>

            <!-- Mobile Dropdown / Sheet of All Sections -->
            <div id="mobile-sections-sheet"
                 x-show="mobileSectionsOpen"
                 style="display: none;"
                 x-transition.opacity
                 @click.outside="mobileSectionsOpen = false"
                 class="mt-2 bg-white rounded-2xl border border-border shadow-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-border bg-muted/30 flex items-center justify-between">
                    <span class="text-sm font-bold text-foreground uppercase tracking-wider">Assessment Sections ({{ $totalDisplaySections }})</span>
                    <button type="button" @click="mobileSectionsOpen = false" class="min-h-[44px] px-3 text-sm font-semibold text-primary hover:underline cursor-pointer">
                        Close
                    </button>
                </div>
                <div x-ref="mobileRailList" class="max-h-[60vh] overflow-y-auto p-2.5 space-y-1.5">
                    <!-- Section 1 (Internal Step 0): Consent -->
                    <button type="button"
                            data-mobile-step="0"
                            @click="goToStep(0)"
                            :aria-current="step === 0 ? 'step' : null"
                            class="w-full min-h-[44px] flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-left transition-colors cursor-pointer"
                            :class="step === 0
                                ? 'bg-primary text-white border-l-4 border-white shadow-xs'
                                : (maxStep > 0 ? 'bg-emerald-50/80 text-foreground hover:bg-emerald-100/70 border border-emerald-200/80' : 'bg-muted/40 text-foreground hover:bg-muted/70 border border-border')">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mt-0.5"
                                  :class="step === 0 ? 'bg-white/20 text-white' : (maxStep > 0 ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-white text-foreground/70 border border-border')">
                                <span x-show="maxStep > 0 && step !== 0" style="display: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span x-show="!(maxStep > 0 && step !== 0)">1</span>
                            </span>
                            <div class="min-w-0">
                                <span class="block text-sm font-semibold leading-snug break-words">Consent</span>
                                <span class="block text-xs opacity-75 mt-0.5">Data Privacy (RA 10173)</span>
                            </div>
                        </div>
                        <span class="text-xs font-semibold shrink-0 px-2 py-0.5 rounded-full"
                              :class="step === 0 ? 'bg-white/20 text-white' : (maxStep > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-muted text-foreground/60')"
                              x-text="step === 0 ? 'Current' : (maxStep > 0 ? '✓ Completed' : 'Upcoming')">Current</span>
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
                                data-mobile-step="{{ $stepIndex }}"
                                @click="goToStep({{ $stepIndex }})"
                                :disabled="maxStep < {{ $stepIndex }}"
                                :aria-current="step === {{ $stepIndex }} ? 'step' : null"
                                class="w-full min-h-[44px] flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-left transition-colors"
                                :class="step === {{ $stepIndex }}
                                    ? 'bg-primary text-white border-l-4 border-white shadow-xs cursor-pointer'
                                    : (maxStep > {{ $stepIndex }}
                                        ? 'bg-emerald-50/80 text-foreground hover:bg-emerald-100/70 border border-emerald-200/80 cursor-pointer'
                                        : (maxStep === {{ $stepIndex }}
                                            ? 'bg-muted/40 text-foreground hover:bg-muted/70 border border-border cursor-pointer'
                                            : 'bg-muted/15 text-foreground/50 border border-transparent cursor-not-allowed'))">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mt-0.5"
                                      :class="step === {{ $stepIndex }}
                                          ? 'bg-white/20 text-white'
                                          : (maxStep > {{ $stepIndex }}
                                              ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                                              : (maxStep === {{ $stepIndex }}
                                                  ? 'bg-white text-foreground/80 border border-border'
                                                  : 'bg-muted text-foreground/50 border border-border'))">
                                    <span x-show="maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }}" style="display: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <span x-show="!(maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }})">{{ $displayNum }}</span>
                                </span>
                                <div class="min-w-0">
                                    <span class="block text-sm font-semibold leading-snug break-words">{{ $catPrimaryName }}</span>
                                    @if($catShortCode)
                                        <span class="block text-xs italic opacity-75 mt-0.5">{{ $catShortCode }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-1">
                                <template x-if="maxStep < {{ $stepIndex }} && step !== {{ $stepIndex }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-foreground/40" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                      :class="step === {{ $stepIndex }}
                                          ? 'bg-white/20 text-white'
                                          : (maxStep > {{ $stepIndex }}
                                              ? 'bg-emerald-100 text-emerald-800'
                                              : (maxStep === {{ $stepIndex }} ? 'bg-muted text-foreground/70' : 'text-foreground/45'))"
                                      x-text="step === {{ $stepIndex }} ? 'Current' : (maxStep > {{ $stepIndex }} ? '✓ Completed' : (maxStep === {{ $stepIndex }} ? 'Upcoming' : 'Locked'))">Upcoming</span>
                            </div>
                        </button>
                        @php $stepIndex++; @endphp
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Unified Connected Container: Sections Rail + Content Area in ONE Card -->
        <div class="bg-white rounded-3xl border border-border shadow-xs overflow-hidden flex flex-col lg:flex-row lg:items-start min-h-[620px]">
            
            <!-- Desktop Section Rail (Sticky Header + Scrollable Sections List) -->
            <aside aria-label="Assessment Sections" class="hidden lg:flex lg:flex-col w-72 xl:w-80 shrink-0 border-r border-border bg-white h-full overflow-hidden lg:sticky lg:top-0 lg:max-h-[calc(100vh-2rem)]">
                <div class="p-5 border-b border-border bg-white shrink-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-foreground/50 uppercase tracking-wider font-sans">Sections</span>
                        <span class="text-sm font-bold text-primary" x-text="'Section ' + displayStep() + ' of ' + totalDisplaySteps()">Section 1 of {{ $totalDisplaySections }}</span>
                    </div>
                    <div class="mt-3 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-foreground/70">
                            <span><span x-text="completedSectionsCount()">0</span> of <span x-text="totalDisplaySteps()">{{ $totalDisplaySections }}</span> sections completed</span>
                            <span x-text="progressPercent() + '%'">0%</span>
                        </div>
                        <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full transition-all duration-300" style="width: 0%;" :style="'width: ' + progressPercent() + '%'"></div>
                        </div>
                    </div>
                </div>

                <div x-ref="desktopRailList" class="flex-1 overflow-y-auto p-3 space-y-1.5 focus:outline-none">
                    <!-- Section 1 (Internal Step 0): Consent -->
                    <button type="button"
                            data-rail-step="0"
                            @click="goToStep(0)"
                            :aria-current="step === 0 ? 'step' : null"
                            class="w-full min-h-[44px] flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-left transition-colors cursor-pointer"
                            :class="step === 0
                                ? 'bg-primary text-white border-l-4 border-white shadow-xs'
                                : (maxStep > 0 ? 'bg-emerald-50/80 text-foreground hover:bg-emerald-100/70 border border-emerald-200/80' : 'bg-muted/40 text-foreground hover:bg-muted/70 border border-border')">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mt-0.5"
                                  :class="step === 0 ? 'bg-white/20 text-white' : (maxStep > 0 ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-white text-foreground/70 border border-border')">
                                <span x-show="maxStep > 0 && step !== 0" style="display: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span x-show="!(maxStep > 0 && step !== 0)">1</span>
                            </span>
                            <div class="min-w-0">
                                <span class="block text-sm font-semibold leading-snug break-words">Consent</span>
                                <span class="block text-xs opacity-75 mt-0.5">Data Privacy (RA 10173)</span>
                            </div>
                        </div>
                        <span class="text-xs font-semibold shrink-0 px-2 py-0.5 rounded-full"
                              :class="step === 0 ? 'bg-white/20 text-white' : (maxStep > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-muted text-foreground/60')"
                              x-text="step === 0 ? 'Current' : (maxStep > 0 ? '✓ Completed' : 'Upcoming')">Current</span>
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
                                data-rail-step="{{ $stepIndex }}"
                                @click="goToStep({{ $stepIndex }})"
                                :disabled="maxStep < {{ $stepIndex }}"
                                :aria-current="step === {{ $stepIndex }} ? 'step' : null"
                                class="w-full min-h-[44px] flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-left transition-colors"
                                :class="step === {{ $stepIndex }}
                                    ? 'bg-primary text-white border-l-4 border-white shadow-xs cursor-pointer'
                                    : (maxStep > {{ $stepIndex }}
                                        ? 'bg-emerald-50/80 text-foreground hover:bg-emerald-100/70 border border-emerald-200/80 cursor-pointer'
                                        : (maxStep === {{ $stepIndex }}
                                            ? 'bg-muted/40 text-foreground hover:bg-muted/70 border border-border cursor-pointer'
                                            : 'bg-muted/15 text-foreground/50 border border-transparent cursor-not-allowed'))">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold shrink-0 mt-0.5"
                                      :class="step === {{ $stepIndex }}
                                          ? 'bg-white/20 text-white'
                                          : (maxStep > {{ $stepIndex }}
                                              ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                                              : (maxStep === {{ $stepIndex }}
                                                  ? 'bg-white text-foreground/80 border border-border'
                                                  : 'bg-muted text-foreground/50 border border-border'))">
                                    <span x-show="maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }}" style="display: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <span x-show="!(maxStep > {{ $stepIndex }} && step !== {{ $stepIndex }})">{{ $displayNum }}</span>
                                </span>
                                <div class="min-w-0">
                                    <span class="block text-sm font-semibold leading-snug break-words">{{ $catPrimaryName }}</span>
                                    @if($catShortCode)
                                        <span class="block text-xs italic opacity-75 mt-0.5">{{ $catShortCode }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-1">
                                <template x-if="maxStep < {{ $stepIndex }} && step !== {{ $stepIndex }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-foreground/40" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                      :class="step === {{ $stepIndex }}
                                          ? 'bg-white/20 text-white'
                                          : (maxStep > {{ $stepIndex }}
                                              ? 'bg-emerald-100 text-emerald-800'
                                              : (maxStep === {{ $stepIndex }} ? 'bg-muted text-foreground/70' : 'text-foreground/45'))"
                                      x-text="step === {{ $stepIndex }} ? 'Current' : (maxStep > {{ $stepIndex }} ? '✓ Completed' : (maxStep === {{ $stepIndex }} ? 'Upcoming' : 'Locked'))">Upcoming</span>
                            </div>
                        </button>
                        @php $stepIndex++; @endphp
                    @endforeach
                </div>
            </aside>

            <!-- RIGHT PANEL: Form Content Area (Flows naturally with page scroll — no nested vertical scroll trap) -->
            <main class="flex-1 min-w-0 flex flex-col bg-white">
                <div class="p-4 sm:p-8 lg:p-10 focus:outline-none">
                @if ($errors->any())
                    <div class="bg-destructive/10 border-l-4 border-destructive text-destructive p-4 rounded-r-xl mb-6" role="alert">
                        <p class="text-sm font-medium">Please complete all required fields before submitting.</p>
                    </div>
                @endif

                <form id="inventoryForm" method="POST" action="{{ route('inventory.store') }}">
                    @csrf
                    
                    <!-- Error Message Alert -->
                    <div x-show="errorMessage" x-transition.opacity class="mb-6 bg-destructive/10 border-l-4 border-destructive p-4 rounded-r-xl" style="display: none;" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-destructive" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-destructive font-medium" x-text="errorMessage === 'consent' ? 'Please confirm your agreement to the data privacy terms and provide your signature before continuing.' : 'Please answer all required questions in this section before continuing.'">
                                    Please answer all required questions in this section before continuing.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 1 (Internal Step 0): Consent -->
                    <div x-show="step === 0" x-transition.opacity data-category="consent" class="space-y-6">
                        <div class="bg-white rounded-2xl border border-border p-5 sm:p-8 shadow-xs">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-primary/10 text-primary border border-primary/20">
                                Section 1 of {{ $totalDisplaySections }} &middot; Data Privacy Consent
                            </span>
                            <h3 class="text-xl sm:text-2xl font-heading font-bold text-foreground mt-3">Data Privacy Consent</h3>
                            <p class="text-foreground/75 text-sm sm:text-base mt-1">Read the data privacy notice below, check the agreement box, and provide your signature to continue.</p>
                        </div>

                        <div class="bg-white rounded-2xl border border-border p-5 sm:p-8 shadow-xs space-y-6">
                            <!-- Step 1: Clearly separated document area (natural flow, no nested scroll box) -->
                            <div class="bg-muted/30 p-5 sm:p-7 rounded-2xl border border-border text-sm sm:text-base text-foreground/85 leading-relaxed space-y-4">
                                <p class="font-bold text-foreground"><strong>Data Privacy Consent (RA 10173)</strong></p>
                                <p>In accordance with the Data Privacy Act of 2012 (RA 10173), I hereby grant my explicit consent to the City College of Tagaytay Guidance Office to collect, use, process, and disclose my personal data, including demographic profile, contact details, educational background, and psychological assessment results.</p>
                                <p>I understand that this information will be used strictly for academic, guidance, and counseling purposes, aiming to support my well-being and development as a student.</p>
                                <p>I agree to keep my information updated and acknowledge that my data will be handled with strict confidentiality and security.</p>
                            </div>

                            <!-- Step 2: Prominent Consent Action -->
                            <label class="flex items-start p-4 sm:p-5 bg-primary/5 border-2 border-primary/25 rounded-2xl cursor-pointer hover:bg-primary/10 has-[:checked]:border-primary has-[:checked]:bg-primary/10 focus-within:ring-2 focus-within:ring-primary transition-all">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="checkbox" x-model="consentChecked" name="consent_checkbox" class="w-5 h-5 text-primary focus:ring-primary border-border rounded cursor-pointer">
                                </div>
                                <span class="ml-3.5 text-sm sm:text-base text-foreground font-semibold leading-snug">I have read, understood, and agree to the above data privacy terms in accordance with RA 10173.</span>
                            </label>

                            <!-- Step 3: Signature -->
                            <div class="pt-2">
                                <h4 class="text-base sm:text-lg font-heading font-semibold text-foreground mb-3">Provide Signature</h4>
                                
                                <div class="flex space-x-2 border-b border-border mb-5">
                                    <button type="button" @click="signatureType = 'drawn'; $nextTick(() => ensureSignaturePad())" :class="signatureType === 'drawn' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground font-medium'" class="min-h-[44px] pb-2 px-4 border-b-2 text-sm transition-colors cursor-pointer">Draw Signature</button>
                                    <button type="button" @click="signatureType = 'typed'" :class="signatureType === 'typed' ? 'border-primary text-primary font-bold' : 'border-transparent text-foreground/60 hover:text-foreground font-medium'" class="min-h-[44px] pb-2 px-4 border-b-2 text-sm transition-colors cursor-pointer">Type Signature</button>
                                </div>
                                
                                <input type="hidden" name="signature_type" :value="signatureType">
                                <input type="hidden" name="signature_data" :value="signatureData">
                                <input type="hidden" name="signature_font" :value="signatureFont">

                                <div x-show="signatureType === 'drawn'">
                                    <div class="border border-border bg-white rounded-xl shadow-inner relative overflow-hidden" style="touch-action: none;">
                                        <canvas id="signature-pad" class="w-full h-44 sm:h-48 cursor-crosshair"></canvas>
                                        <button type="button" @click="clearSignature()" class="absolute bottom-2.5 right-2.5 text-sm font-medium bg-muted text-foreground/80 px-3 py-1.5 rounded-lg border border-border hover:bg-muted/80 cursor-pointer">Clear</button>
                                    </div>
                                </div>
                                
                                <div x-show="signatureType === 'typed'" style="display: none;">
                                    <div class="grid gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-foreground mb-1 label-required">Type your full name</label>
                                            <input type="text" x-model="typedName" @input="updateTypedSignature()" class="w-full rounded-xl border-border focus:ring-primary focus:border-primary shadow-xs text-sm sm:text-base" placeholder="John Doe">
                                        </div>
                                        <div class="mt-1 p-5 sm:p-6 bg-white border border-border rounded-xl flex items-center justify-center min-h-[110px] shadow-inner">
                                            <span x-text="typedName" class="font-dancing text-3xl sm:text-4xl text-primary empty:hidden break-all text-center"></span>
                                            <span x-show="!typedName" class="text-foreground/40 italic text-sm">Signature Preview</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Visually associated Continue action -->
                            <div class="pt-6 border-t border-border flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                <p class="text-xs sm:text-sm text-foreground/60">
                                    <span x-show="!consentChecked">Check the agreement box above and sign to enable continuation.</span>
                                    <span x-show="consentChecked" style="display: none;" class="text-emerald-700 font-medium">&check; Consent agreement checked.</span>
                                </p>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    <template x-if="maxStep > 0">
                                        <button type="button" @click="goToStep(maxStep)" class="btn-secondary min-h-[44px] px-5 text-sm justify-center cursor-pointer">
                                            Return to Current Section &rarr;
                                        </button>
                                    </template>
                                    <button type="button" @click="nextStep('consent')" class="btn-primary min-h-[44px] px-7 text-sm justify-center disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" :disabled="!consentChecked || isSubmitting">
                                        <span x-show="!isSubmitting">Save &amp; Continue &rarr;</span>
                                        <span x-show="isSubmitting" style="display: none;">Saving...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php $stepIndex = 1; @endphp
                    @foreach($inventoryConfig as $data)
                        @php
                            $sectionTitle = $data->label();
                            $displayNum = min($totalDisplaySections, $stepIndex + 1);
                            $sectionPrimaryName = $sectionTitle;
                            if (preg_match('/^(.+?)\s*\(([^)]+)\)$/', $sectionTitle, $matches)) {
                                $sectionPrimaryName = trim($matches[1]);
                            }
                        @endphp
                        <div x-show="step === {{ $stepIndex }}" style="display: none;" data-category="{{ $data->name }}" class="space-y-6">
                            <!-- Section Header Card -->
                            <div class="bg-white rounded-2xl border border-border p-5 sm:p-8 shadow-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-primary/10 text-primary border border-primary/20">
                                        Section {{ $displayNum }} of {{ $totalDisplaySections }} &middot; {{ $sectionPrimaryName }}
                                    </span>
                                    <span class="text-sm font-medium text-foreground/60">
                                        {{ count($data->questionItems) }} {{ Str::plural('question', count($data->questionItems)) }}
                                    </span>
                                </div>
                                <h3 class="text-xl sm:text-2xl font-heading font-bold text-foreground mt-3">{{ $sectionTitle }}</h3>
                                @if($data->instructions)
                                    <p class="mt-2 text-sm sm:text-base text-foreground/80 leading-relaxed">{{ $data->instructions }}</p>
                                @else
                                    <p class="mt-2 text-sm sm:text-base text-foreground/75 leading-relaxed">Please read each item carefully and select the response that best applies to you.</p>
                                @endif
                                @if($data->name === 'cat')
                                    <p class="mt-3 text-sm font-medium text-primary bg-primary/5 p-3.5 rounded-xl border border-primary/15">Please use this scale to indicate how you generally felt during the past two weeks.</p>
                                @endif
                            </div>

                            <!-- Question Cards -->
                            <div class="space-y-4 sm:space-y-5">
                                @foreach($data->questionItems as $item)
                                    <div class="bg-white rounded-2xl p-4 sm:p-6 xl:p-8 border border-border shadow-xs transition-all focus-within:ring-2 focus-within:ring-primary focus-within:border-primary hover:border-primary/40">
                                        <div class="flex items-start mb-4 sm:mb-5">
                                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm mr-3 sm:mr-4 mt-0.5">
                                                {{ $item->item_number }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-base sm:text-lg font-medium text-foreground leading-snug pt-0.5 break-words">{{ $item->prompt }}</p>
                                                @if($item->subcategory)
                                                    <span class="inline-block mt-2 px-2.5 py-1 bg-muted text-foreground/75 text-sm font-semibold rounded-lg border border-border">
                                                        {{ $item->subcategory->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" id="timing_{{ $data->name }}_{{ $item->item_number }}" name="timings[{{ $data->name }}][{{ $item->item_number }}]" value="{{ $existingTimings[$data->name][$item->item_number] ?? 0 }}">
                                        
                                        <div class="pl-0 xl:pl-12">
                                            @php $itemOptions = $item->options ?: ($data->default_options ?? []); @endphp
                                            @if($data->scale_type === 'multiple_choice_unscored' && !empty($itemOptions))
                                                @foreach($itemOptions as $optionIndex => $optionText)
                                                    <label class="flex items-center min-h-[44px] p-3.5 sm:p-4 border border-border rounded-xl mb-2.5 sm:mb-3 cursor-pointer hover:bg-muted/50 transition-colors has-[:checked]:bg-primary/5 has-[:checked]:border-primary focus-within:ring-2 focus-within:ring-primary">
                                                        <input type="radio" 
                                                            name="responses[{{ $data->name }}][{{ $item->item_number }}]" 
                                                            value="{{ $optionText }}" 
                                                            class="w-5 h-5 text-primary border-border focus:ring-primary focus:ring-offset-0 shrink-0"
                                                            @change="recordTiming('{{ $data->name }}', {{ $item->item_number }})"
                                                            {{ (string)old('responses.'.$data->name.'.'.$item->item_number, $existingResponses[$data->name][$item->item_number] ?? '') === (string)$optionText ? 'checked' : '' }}>
                                                        <span class="ml-3 text-sm sm:text-base text-foreground break-words">{{ $optionText }}</span>
                                                    </label>
                                                @endforeach
                                            @elseif($data->scale_min !== null && $data->scale_max !== null)
                                                @php
                                                    $scaleLabels = $data->scale_labels ?? [];
                                                    if (empty($scaleLabels)) {
                                                        if (strtolower($data->name) === 'dass21') $scaleLabels = [0 => 'Did not apply to me at all', 1 => 'Applied to me to some degree, or some of the time', 2 => 'Applied to me to a considerable degree or a good part of time', 3 => 'Applied to me very much or most of the time'];
                                                        if (strtolower($data->name) === 'erq') $scaleLabels = [1 => 'strongly disagree', 4 => 'neutral', 7 => 'strongly agree'];
                                                        if (strtolower($data->name) === 'ars30') $scaleLabels = [1 => 'Does not describe me at all', 5 => 'Describes me very well'];
                                                        if (strtolower($data->name) === 'cat') $scaleLabels = [1 => 'Not at all', 4 => 'Somewhat', 7 => 'Very much'];
                                                        if (strtolower($data->name) === 'ffmq') $scaleLabels = [1 => 'Never or very rarely true', 5 => 'Very often or always true'];
                                                    }
                                                @endphp
                                                <div class="grid grid-cols-3 md:flex md:flex-nowrap lg:grid lg:grid-cols-3 xl:flex xl:flex-nowrap gap-2.5 sm:gap-4 justify-items-center md:justify-between xl:justify-between">
                                                    @for($i = $data->scale_min; $i <= $data->scale_max; $i++)
                                                        <label class="flex flex-col items-center cursor-pointer group min-w-[44px] md:flex-1 lg:flex-none xl:flex-1">
                                                            <div class="w-11 h-11 xl:w-12 xl:h-12 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full border border-border bg-white group-hover:border-primary group-hover:bg-primary/5 transition-all group-has-[:checked]:bg-primary group-has-[:checked]:text-white group-has-[:checked]:border-primary group-focus-within:ring-2 group-focus-within:ring-primary group-focus-within:ring-offset-2 shadow-xs mb-1.5">
                                                                <input type="radio" 
                                                                    name="responses[{{ $data->name }}][{{ $item->item_number }}]" 
                                                                    value="{{ $i }}" 
                                                                    class="sr-only"
                                                                    @change="recordTiming('{{ $data->name }}', {{ $item->item_number }})"
                                                                    {{ (string)old('responses.'.$data->name.'.'.$item->item_number, $existingResponses[$data->name][$item->item_number] ?? '') === (string)$i ? 'checked' : '' }}>
                                                                <span class="font-semibold text-sm sm:text-base">{{ $i }}</span>
                                                            </div>
                                                            @if(isset($scaleLabels[$i]))
                                                                <span class="text-xs sm:text-sm text-foreground/70 text-center max-w-[88px] sm:max-w-[96px] leading-tight break-words">{{ $scaleLabels[$i] }}</span>
                                                            @else
                                                                <span class="text-xs sm:text-sm text-transparent select-none" aria-hidden="true">_</span>
                                                            @endif
                                                        </label>
                                                    @endfor
                                                </div>
                                            @endif
                                        </div>
                                        @error('responses.'.$data->name.'.'.$item->item_number)
                                            <p class="text-destructive text-sm mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            <!-- Section Navigation Footer -->
                            <div class="bg-white rounded-2xl border border-border p-4 sm:p-6 shadow-xs flex flex-col-reverse sm:flex-row justify-between items-stretch sm:items-center gap-3">
                                <button type="button" @click="prevStep()" class="btn-secondary min-h-[44px] px-6 text-sm justify-center cursor-pointer">
                                    &larr; Previous
                                </button>
                                
                                @if($stepIndex < count($inventoryConfig))
                                    <button type="button" @click="nextStep('{{ $data->name }}')" class="btn-primary min-h-[44px] px-8 text-sm justify-center disabled:opacity-50 cursor-pointer" :disabled="isSubmitting">
                                        <span x-show="!isSubmitting">Save &amp; Continue &rarr;</span>
                                        <span x-show="isSubmitting" style="display: none;">Saving...</span>
                                    </button>
                                @else
                                    <button type="submit" class="btn-primary min-h-[44px] px-8 text-sm justify-center disabled:opacity-50 cursor-pointer" @click="submitForm()" :disabled="isSubmitting">
                                        Complete Inventory
                                    </button>
                                @endif
                            </div>
                        </div>
                        @php $stepIndex++; @endphp
                    @endforeach
                    </form>
                </div>
            </main>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('alpine:init', () => {
            Alpine.data('inventoryForm', () => ({
                step: {{ $startStep ?? 0 }},
                maxStep: {{ $startStep ?? 0 }},
                totalSteps: {{ count($inventoryConfig) }},
                sectionTitles: @json($sectionTitles),
                mobileSectionsOpen: false,
                sectionStartTimes: {},
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
                    this.sectionStartTimes[this.step] = Date.now();
                    
                    this.$watch('step', value => {
                        if (value > this.maxStep) {
                            this.maxStep = value;
                        }
                        this.mobileSectionsOpen = false;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        const mainEl = document.querySelector('main');
                        if (mainEl) {
                            mainEl.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                        if (!this.sectionStartTimes[value]) {
                            this.sectionStartTimes[value] = Date.now();
                        }
                        this.$nextTick(() => {
                            this.scrollActiveStepIntoView();
                            if (value === 0) {
                                this.ensureSignaturePad();
                            }
                        });
                    });

                    setTimeout(() => {
                        this.ensureSignaturePad();
                        this.scrollActiveStepIntoView();
                    }, 100);
                },

                currentSectionTitle() {
                    return this.sectionTitles[this.step] || '';
                },

                totalDisplaySteps() {
                    return {{ $totalDisplaySections }};
                },

                displayStep() {
                    if (this.step === 0) return 1;
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
                    });
                },

                clearSignature() {
                    if (this.pad) {
                        this.pad.clear();
                        this.signatureData = '';
                    }
                },

                updateTypedSignature() {
                    this.signatureData = this.typedName;
                },
                
                async nextStep(category) {
                    if (this.isSubmitting) return;
                    this.errorMessage = '';
                    this.isSubmitting = true;
                    
                    const form = document.getElementById('inventoryForm');
                    const formData = new FormData(form);
                    
                    // Client-side quick check
                    if (category === 'consent') {
                        if (!this.consentChecked || !this.signatureData) {
                            this.errorMessage = category;
                            this.isSubmitting = false;
                            return;
                        }
                    } else {
                        let allAnswered = true;
                        const sectionDiv = document.querySelector(`div[data-category="${category}"]`);
                        const radioNames = new Set();
                        sectionDiv.querySelectorAll('input[type="radio"]').forEach(radio => {
                            radioNames.add(radio.name);
                        });
                        
                        for (const name of radioNames) {
                            if (!formData.has(name)) {
                                allAnswered = false;
                                break;
                            }
                        }
                        
                        if (!allAnswered) {
                            this.errorMessage = category;
                            this.isSubmitting = false;
                            return;
                        }
                    }
                    
                    // Server-side validation
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
                            if (this.step < this.totalSteps) {
                                this.step++;
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
                    if (this.step > 0) {
                        this.step--;
                    }
                },
                
                recordTiming(category, itemNumber) {
                    let input = document.getElementById(`timing_${category}_${itemNumber}`);
                    // Only record the first time they interact to capture initial reaction time
                    if (input.value === "" || input.value === "0") {
                        let currentStepStartTime = this.sectionStartTimes[this.step] || Date.now();
                        input.value = Date.now() - currentStepStartTime;
                    }
                },

                submitForm() {
                    // Ensure any missed timings get a default 0 (if they bypassed clicking somehow, though required will catch it)
                    document.querySelectorAll('input[name^="timings"]').forEach(input => {
                        if (input.value === "") input.value = 0;
                    });
                }
            }))
        })
    </script>
    @endpush
</x-app-layout>
