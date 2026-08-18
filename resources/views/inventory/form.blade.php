<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('3rd Year Individual Inventory') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="inventoryForm()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="bg-destructive/10 border-l-4 border-destructive text-destructive p-4 rounded-r-xl mb-6" role="alert">
                    <p class="font-medium">Please complete all required fields before submitting.</p>
                </div>
            @endif
            
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap');
                .font-dancing { font-family: 'Dancing Script', cursive; }
            </style>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                
                <!-- Progress Line Background -->
                <div class="px-2 sm:px-8 py-6 sm:py-8 border-b border-border bg-white rounded-t-3xl overflow-hidden relative">
                    <div class="flex justify-between items-start relative z-10 w-full max-w-4xl mx-auto">
                        <!-- Connecting Line -->
                        <div class="absolute top-4 sm:top-5 left-8 sm:left-12 right-8 sm:right-12 h-[2px] bg-border z-0 -translate-y-1/2"></div>
                        
                        <!-- Step 0 -->
                        <div class="flex flex-col items-center gap-2 cursor-pointer relative z-10 w-16 sm:w-24 shrink-0" @click="step = 0">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white rounded-full flex items-center justify-center font-bold text-xs sm:text-sm transition-all shadow-[0_0_0_6px_white] z-10 border"
                                :class="step === 0 ? '!bg-primary text-white border-primary shadow-[0_0_0_4px_white,0_0_0_8px_rgba(139, 16, 20,0.2)]' : (step > 0 ? 'bg-primary/10 text-primary border-primary' : 'text-muted-foreground border-border')">
                                0
                            </div>
                            <span class="text-[10px] sm:text-xs font-semibold text-center leading-tight mt-1 px-1 break-words w-full" :class="step === 0 ? 'text-primary' : 'text-muted-foreground'">Consent</span>
                        </div>

                        @php $stepIndex = 1; @endphp
                        @foreach($inventoryConfig as $data)
                            <div class="flex flex-col items-center gap-2 relative z-10 w-16 sm:w-24 shrink-0" :class="step >= {{ $stepIndex }} ? 'cursor-pointer' : 'cursor-not-allowed'" @click="if(step >= {{ $stepIndex }}) step = {{ $stepIndex }}">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white rounded-full flex items-center justify-center font-bold text-xs sm:text-sm transition-all shadow-[0_0_0_6px_white] z-10 border"
                                    :class="step === {{ $stepIndex }} ? '!bg-primary text-white border-primary shadow-[0_0_0_4px_white,0_0_0_8px_rgba(139, 16, 20,0.2)]' : (step > {{ $stepIndex }} ? 'bg-primary/10 text-primary border-primary' : 'text-muted-foreground border-border')">
                                    {{ $stepIndex }}
                                </div>
                                <span class="text-[10px] sm:text-xs font-semibold text-center leading-tight mt-1 px-1 break-words w-full hidden sm:block" :class="step === {{ $stepIndex }} ? 'text-primary' : 'text-muted-foreground'">
                                    {{ Str::title(str_replace('_', ' ', $data->name)) }}
                                </span>
                            </div>
                            @php $stepIndex++; @endphp
                        @endforeach
                    </div>
                </div>

                <div class="p-lg">
                    <form id="inventoryForm" method="POST" action="{{ route('inventory.store') }}">
                        @csrf
                        
                        <!-- Error Message Alert -->
                        <div x-show="errorMessage" x-transition.opacity class="mb-6 bg-destructive/10 border-l-4 border-destructive p-4 rounded-r-lg" style="display: none;">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-destructive" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-destructive font-medium">
                                        Please answer all questions before proceeding.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 0: Consent -->
                        <div x-show="step === 0" style="display: none;" x-transition.opacity data-category="consent">
                            <div class="mb-6 border-b border-border pb-4">
                                <h3 class="text-2xl font-heading font-bold text-primary mb-2">Data Privacy Consent</h3>
                                <p class="text-foreground/80 text-lg">Please read and sign the consent form before proceeding.</p>
                            </div>
                            
                            <div class="prose max-w-none text-foreground/80 mb-6 bg-muted/30 p-6 rounded-2xl border border-border h-64 overflow-y-auto text-sm leading-relaxed">
                                <p class="mb-4"><strong>Data Privacy Consent (RA 10173)</strong></p>
                                <p class="mb-4">In accordance with the Data Privacy Act of 2012 (RA 10173), I hereby grant my explicit consent to the City College of Tagaytay Guidance Office to collect, use, process, and disclose my personal data, including demographic profile, contact details, educational background, and psychological assessment results.</p>
                                <p class="mb-4">I understand that this information will be used strictly for academic, guidance, and counseling purposes, aiming to support my well-being and development as a student.</p>
                                <p>I agree to keep my information updated and acknowledge that my data will be handled with strict confidentiality and security.</p>
                            </div>

                            <label class="flex items-start p-4 bg-primary/5 border border-primary/20 rounded-xl cursor-pointer hover:bg-primary/10 transition-colors mb-8">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="checkbox" x-model="consentChecked" name="consent_checkbox" class="w-5 h-5 text-primary focus:ring-primary border-border rounded">
                                </div>
                                <span class="ml-3 text-foreground font-medium leading-tight">I have read, understood, and agree to the above data privacy terms in accordance with RA 10173.</span>
                            </label>

                            <div class="mb-6">
                                <h4 class="text-lg font-heading font-semibold text-foreground mb-4">Provide Signature</h4>
                                
                                <div class="flex space-x-2 border-b border-border mb-6">
                                    <button type="button" @click="signatureType = 'drawn'" :class="signatureType === 'drawn' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground'" class="pb-2 px-4 border-b-2 font-medium transition-colors">Draw Signature</button>
                                    <button type="button" @click="signatureType = 'typed'" :class="signatureType === 'typed' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground'" class="pb-2 px-4 border-b-2 font-medium transition-colors">Type Signature</button>
                                </div>
                                
                                <input type="hidden" name="signature_type" :value="signatureType">
                                <input type="hidden" name="signature_data" :value="signatureData">
                                <input type="hidden" name="signature_font" :value="signatureFont">

                                <div x-show="signatureType === 'drawn'">
                                    <div class="border border-border bg-white rounded-xl shadow-inner relative overflow-hidden" style="touch-action: none;">
                                        <canvas id="signature-pad" class="w-full h-48 cursor-crosshair"></canvas>
                                        <button type="button" @click="clearSignature()" class="absolute bottom-2 right-2 text-xs bg-muted text-foreground/70 px-2 py-1 rounded hover:bg-muted/80">Clear</button>
                                    </div>
                                </div>
                                
                                <div x-show="signatureType === 'typed'" style="display: none;">
                                    <div class="grid gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-foreground mb-1">Type your full name</label>
                                            <input type="text" x-model="typedName" @input="updateTypedSignature()" class="w-full rounded-xl border-border focus:ring-primary focus:border-primary shadow-sm" placeholder="John Doe">
                                        </div>
                                        <div class="mt-4 p-6 bg-white border border-border rounded-xl flex items-center justify-center min-h-[120px] shadow-inner">
                                            <span x-text="typedName" class="font-dancing text-4xl text-primary empty:hidden"></span>
                                            <span x-show="!typedName" class="text-foreground/30 italic text-sm">Signature Preview</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 pt-6 border-t border-border flex justify-end items-center">
                                <button type="button" @click="nextStep('consent')" class="btn-primary px-8 disabled:opacity-50" :disabled="!consentChecked">
                                    Continue to Assessment &rarr;
                                </button>
                            </div>
                        </div>

                        @php $stepIndex = 1; @endphp
                        @foreach($inventoryConfig as $data)
                            <div x-show="step === {{ $stepIndex }}" style="display: none;" data-category="{{ $data->name }}">
                                <div class="mb-8 border-b border-border pb-6">
                                    <h3 class="text-2xl font-bold text-foreground font-display">{{ Str::title(str_replace('_', ' ', $data->name)) }}</h3>
                                    <p class="mt-2 text-foreground/80">{{ $data->instructions }}</p>
                                    @if($data->name === 'cat')
                                        <p class="mt-2 text-sm font-medium text-primary bg-primary/5 p-3 rounded-lg border border-primary/10">Please use this scale to indicate how you generally felt during the past two weeks.</p>
                                    @endif
                                </div>

                                <div class="space-y-8">
                                    @foreach($data->questionItems as $item)
                                        <div class="bg-white rounded-xl p-6 sm:p-8 border border-border shadow-sm hover:shadow-md transition-shadow">
                                            <div class="flex items-start mb-6">
                                                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm mr-4 mt-0.5">
                                                    {{ $item->item_number }}
                                                </span>
                                                <div>
                                                    <p class="text-lg font-medium text-foreground leading-snug pt-0.5">{{ $item->prompt }}</p>
                                                    @if($item->subcategory)
                                                        <span class="inline-block mt-2 px-2.5 py-1 bg-muted/40 text-foreground/70 text-xs font-semibold rounded-md border border-border">
                                                            {{ $item->subcategory->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <input type="hidden" id="timing_{{ $data->name }}_{{ $item->item_number }}" name="timings[{{ $data->name }}][{{ $item->item_number }}]" value="{{ $existingTimings[$data->name][$item->item_number] ?? 0 }}">
                                            
                                            <div class="pl-0 sm:pl-12">
                                                @php $itemOptions = $item->options ?: ($data->default_options ?? []); @endphp
                                                @if($data->scale_type === 'multiple_choice_unscored' && !empty($itemOptions))
                                                    @foreach($itemOptions as $optionIndex => $optionText)
                                                        <label class="flex items-center p-4 border border-border rounded-xl mb-3 cursor-pointer hover:bg-muted/50 transition-colors has-[:checked]:bg-primary/5 has-[:checked]:border-primary">
                                                            <input type="radio" 
                                                                name="responses[{{ $data->name }}][{{ $item->item_number }}]" 
                                                                value="{{ $optionText }}" 
                                                                class="w-5 h-5 text-primary border-border focus:ring-primary focus:ring-offset-0"
                                                                @change="recordTiming('{{ $data->name }}', {{ $item->item_number }})"
                                                                {{ (string)old('responses.'.$data->name.'.'.$item->item_number, $existingResponses[$data->name][$item->item_number] ?? '') === (string)$optionText ? 'checked' : '' }}>
                                                            <span class="ml-3 text-foreground">{{ $optionText }}</span>
                                                        </label>
                                                    @endforeach
                                                @elseif($data->scale_min !== null && $data->scale_max !== null)
                                                    @php
                                                        $scaleLabels = [];
                                                        if ($data->name === 'dass21') $scaleLabels = [0 => 'Did not apply to me at all', 1 => 'Applied to me to some degree, or some of the time', 2 => 'Applied to me to a considerable degree or a good part of time', 3 => 'Applied to me very much or most of the time'];
                                                        if ($data->name === 'erq') $scaleLabels = [1 => 'strongly disagree', 4 => 'neutral', 7 => 'strongly agree'];
                                                        if ($data->name === 'ars30') $scaleLabels = [1 => 'Does not describe me at all', 5 => 'Describes me very well'];
                                                        if ($data->name === 'cat') $scaleLabels = [1 => 'Not at all', 4 => 'Somewhat', 7 => 'Very much'];
                                                        if ($data->name === 'ffmq') $scaleLabels = [1 => 'Never or very rarely true', 5 => 'Very often or always true'];
                                                    @endphp
                                                    <div class="flex flex-wrap gap-2 sm:gap-4 justify-between sm:justify-start">
                                                        @for($i = $data->scale_min; $i <= $data->scale_max; $i++)
                                                            <label class="flex flex-col items-center cursor-pointer group">
                                                                <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-full border border-border bg-white group-hover:border-primary group-hover:bg-primary/5 transition-all group-has-[:checked]:bg-primary group-has-[:checked]:text-white group-has-[:checked]:border-primary shadow-sm mb-1">
                                                                    <input type="radio" 
                                                                        name="responses[{{ $data->name }}][{{ $item->item_number }}]" 
                                                                        value="{{ $i }}" 
                                                                        class="sr-only"
                                                                        @change="recordTiming('{{ $data->name }}', {{ $item->item_number }})"
                                                                        {{ (string)old('responses.'.$data->name.'.'.$item->item_number, $existingResponses[$data->name][$item->item_number] ?? '') === (string)$i ? 'checked' : '' }}>
                                                                    <span class="font-semibold">{{ $i }}</span>
                                                                </div>
                                                                @if(isset($scaleLabels[$i]))
                                                                    <span class="text-xs text-foreground/60 text-center max-w-[60px] leading-tight">{{ $scaleLabels[$i] }}</span>
                                                                @else
                                                                    <span class="text-xs text-transparent">_</span>
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

                                <div class="mt-8 pt-6 border-t border-border flex justify-between items-center">
                                    <button type="button" @click="prevStep()" class="btn-secondary px-6">
                                        &larr; Previous
                                    </button>
                                    
                                    @if($stepIndex < count($inventoryConfig))
                                        <button type="button" @click="nextStep('{{ $data->name }}')" class="btn-primary px-8 disabled:opacity-50" :disabled="isSubmitting">
                                            <span x-show="!isSubmitting">Next Section &rarr;</span>
                                            <span x-show="isSubmitting">Validating...</span>
                                        </button>
                                    @else
                                        <button type="submit" class="btn-primary px-8 bg-accent border-accent hover:bg-accent/90 disabled:opacity-50" @click="submitForm()" :disabled="isSubmitting">
                                            Submit Final Responses
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @php $stepIndex++; @endphp
                    @endforeach
                </form>
            </div>
        </div>
    </div>
</div>

    @push('scripts')
    <script nonce="{{ $cspNonce }}">
        document.addEventListener('alpine:init', () => {
            Alpine.data('inventoryForm', () => ({
                step: {{ $startStep ?? 0 }},
                totalSteps: {{ count($inventoryConfig) }},
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
                    this.sectionStartTimes[this.step] = Date.now();
                    
                    this.$watch('step', value => {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        if (!this.sectionStartTimes[value]) {
                            this.sectionStartTimes[value] = Date.now();
                        }
                    });

                    setTimeout(() => {
                        const canvas = document.getElementById('signature-pad');
                        if (canvas) {
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
                        }
                    }, 100);
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
                    if (this.step > 1) {
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
