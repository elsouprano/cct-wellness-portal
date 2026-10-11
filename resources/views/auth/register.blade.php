@props(['title' => 'Student Registration'])

@php
    $initialStep = 1;
    if ($errors->hasAny(['email', 'password', 'password_confirmation'])) {
        $initialStep = 1;
    } elseif ($errors->hasAny(['first_name', 'last_name', 'middle_initial', 'birthdate', 'contact_number'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['student_id', 'section', 'department', 'program_id', 'year_level'])) {
        $initialStep = 3;
    } elseif ($errors->hasAny(['address_line1', 'city', 'province'])) {
        $initialStep = 4;
    }
@endphp

<x-auth-layout :title="$title" :wide="true" :fit="true">
    
    <div 
        x-data="{
            step: {{ $initialStep }},
            stepTitles: {
                1: 'Account Credentials',
                2: 'Personal Details',
                3: 'Academic Information',
                4: 'Home Address'
            },
            stepSubtitles: {
                1: 'Set your institutional email and secure account password',
                2: 'Provide your basic student personal and contact details',
                3: 'Enter your official student ID, section, and enrolled program',
                4: 'Provide your current residential home address'
            },
            passwordValue: '{{ old('password', '') }}',
            departments: {{ isset($departments) ? $departments->toJson() : '[]' }},
            selectedDepartment: '{{ old('department', '') }}',
            selectedProgram: '{{ old('program_id', '') }}',
            showPassword: false,
            showConfirmPassword: false,
            isSubmitting: false,
            get passwordStrength() {
                if (!this.passwordValue) return 0;
                let score = 0;
                if (this.passwordValue.length >= 8) score++;
                if (/[A-Z]/.test(this.passwordValue)) score++;
                if (/[0-9]/.test(this.passwordValue)) score++;
                if (/[^A-Za-z0-9]/.test(this.passwordValue)) score++;
                return score;
            },
            init() {
                if (this.selectedProgram && !this.selectedDepartment) {
                    for (const dept of this.departments) {
                        if (dept.programs && dept.programs.some(p => p.id == this.selectedProgram)) {
                            this.selectedDepartment = dept.id.toString();
                            break;
                        }
                    }
                }
                @if($errors->any())
                    this.$nextTick(() => {
                        const firstError = this.$refs.stepBody?.querySelector('.border-destructive, [aria-invalid=\'true\']');
                        if (firstError) {
                            firstError.focus();
                        }
                    });
                @endif
            },
            validateStep(stepNum) {
                const stepEl = this.$refs['step' + stepNum];
                if (!stepEl) return true;

                if (stepNum === 1) {
                    const pass = document.getElementById('password');
                    const confirm = document.getElementById('password_confirmation');
                    if (confirm) {
                        confirm.setCustomValidity('');
                    }
                    if (pass && confirm && pass.value && confirm.value && pass.value !== confirm.value) {
                        confirm.setCustomValidity('Passwords do not match');
                    }
                }

                const inputs = stepEl.querySelectorAll('input:not([type=\'hidden\']), select, textarea');
                for (const input of inputs) {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        input.focus();
                        return false;
                    }
                }
                return true;
            },
            nextStep() {
                if (this.validateStep(this.step)) {
                    if (this.step < 4) {
                        this.step++;
                        this.$nextTick(() => {
                            this.$refs.stepBody?.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                    }
                }
            },
            prevStep() {
                if (this.step > 1) {
                    this.step--;
                    this.$nextTick(() => {
                        this.$refs.stepBody?.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                }
            },
            goToStep(target) {
                if (target < this.step) {
                    this.step = target;
                    this.$nextTick(() => {
                        this.$refs.stepBody?.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                } else if (target > this.step) {
                    for (let s = this.step; s < target; s++) {
                        if (!this.validateStep(s)) return;
                    }
                    this.step = target;
                    this.$nextTick(() => {
                        this.$refs.stepBody?.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                }
            }
        }"
        class="flex flex-col flex-1 min-h-0 h-full w-full"
    >
        <!-- ================= ZONE 1: HEADER (shrink-0) ================= -->
        <header class="shrink-0 pb-4 mb-5 border-b border-[#E8E1D5] flex flex-col gap-4">
            <!-- Top Row: circular logo (56px) on left, Title + subtitle on right -->
            <div class="flex items-center gap-3.5">
                <x-logo-badge size="sm" href="/" class="w-14 h-14 shrink-0" />
                <div>
                    <h1 class="font-heading text-[24px] font-bold text-[#8B1C1C] tracking-tight leading-tight">
                        Student Registration
                    </h1>
                    <p class="text-[14px] text-[#6B6360] mt-0.5 leading-tight">
                        Create your CCT Wellness Portal account
                    </p>
                </div>
            </div>

            <!-- Full-width 4-step stepper row -->
            <div class="w-full">
                @php
                    $stepsList = [
                        1 => 'Account',
                        2 => 'Personal',
                        3 => 'Academic',
                        4 => 'Address'
                    ];
                @endphp
                <div class="flex items-center justify-between w-full">
                    @foreach ($stepsList as $i => $label)
                        <div class="flex items-center {{ $i < 4 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center shrink-0">
                                <button 
                                    type="button" 
                                    @click="goToStep({{ $i }})"
                                    :class="{
                                        'bg-[#8B1C1C] text-white': step >= {{ $i }},
                                        'bg-white border-2 border-[#E8E1D5] text-[#6B6360]': step < {{ $i }}
                                    }"
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold transition-colors cursor-pointer focus:outline-none"
                                    :aria-label="'Step {{ $i }}: {{ $label }}'"
                                >
                                    <template x-if="step > {{ $i }}">
                                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                    <template x-if="step <= {{ $i }}">
                                        <span>{{ $i }}</span>
                                    </template>
                                </button>
                                <span 
                                    class="text-[12px] mt-1 font-medium select-none"
                                    :class="step >= {{ $i }} ? 'text-[#8B1C1C] font-semibold' : 'text-[#6B6360]'"
                                >
                                    {{ $label }}
                                </span>
                            </div>

                            @if ($i < 4)
                                <div 
                                    class="flex-1 h-0.5 mb-5 mx-2 sm:mx-3 transition-colors"
                                    :class="step > {{ $i }} ? 'bg-[#8B1C1C]' : 'bg-[#E8E1D5]'"
                                    aria-hidden="true"
                                ></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </header>

        <!-- Compact Error Banner if Validation Fails -->
        @if ($errors->any())
            <div role="alert" class="shrink-0 mb-4 px-3.5 py-2 rounded-[10px] bg-destructive/10 border border-destructive/20 text-destructive text-xs sm:text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="truncate">
                    {{ $errors->count() }} {{ Str::plural('field', $errors->count()) }} need attention — check highlighted inputs.
                </span>
            </div>
        @endif

        <!-- ================= ZONE 2: FORM BODY ================= -->
        <form 
            method="POST" 
            action="{{ route('register') }}" 
            @submit="if (step < 4) { $event.preventDefault(); nextStep(); } else { isSubmitting = true; }"
            @keydown.enter="if (step < 4 && $event.target.tagName === 'INPUT') { $event.preventDefault(); nextStep(); }"
            class="flex flex-col flex-1 min-h-0 h-full"
        >
            @csrf

            <div x-ref="stepBody" class="flex-1 min-h-0 overflow-y-auto pr-0.5 flex flex-col">

                <!-- Step Title Row (Compact Block, Once Only) -->
                <div class="flex items-center gap-3 mb-5 shrink-0">
                    <div class="w-9 h-9 rounded-full bg-[#8B1C1C]/10 text-[#8B1C1C] flex items-center justify-center shrink-0">
                        <!-- Step 1 Icon: Credentials/Key -->
                        <svg x-show="step === 1" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <!-- Step 2 Icon: User Profile -->
                        <svg x-show="step === 2" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <!-- Step 3 Icon: Academic Cap -->
                        <svg x-show="step === 3" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
                        </svg>
                        <!-- Step 4 Icon: Location / Home -->
                        <svg x-show="step === 4" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-heading text-[20px] font-bold text-[#8B1C1C] leading-tight" x-text="stepTitles[step]"></h2>
                        <p class="text-[14px] text-[#6B6360] mt-0.5 leading-tight" x-text="stepSubtitles[step]"></p>
                    </div>
                </div>

                <!-- ----------------- STEP 1: ACCOUNT CREDENTIALS ----------------- -->
                <div 
                    x-show="step === 1" 
                    x-ref="step1"
                    x-transition:enter="transition-opacity duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="space-y-5"
                >
                    <!-- Institutional Email -->
                    <div>
                        <label for="email" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                            {{ __('Institutional Email') }} <span class="text-destructive font-bold">*</span>
                        </label>
                        <input 
                            id="email" 
                            class="auth-input h-[52px] @error('email') border-destructive @enderror" 
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            :required="step === 1"
                            autocomplete="email" 
                            inputmode="email"
                            placeholder="name@citycollegeoftagaytay.edu.ph" 
                            aria-required="true"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        />
                        <p class="text-[13px] text-[#6B6360] mt-1">Official college domain: @citycollegeoftagaytay.edu.ph</p>
                        <x-input-error :messages="$errors->get('email')" id="email-error" class="mt-1" />
                    </div>

                    <!-- Password and Confirm Password in 2-column grid from md up -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Password') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    id="password" 
                                    class="auth-input h-[52px] pr-12 @error('password') border-destructive @enderror"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    x-model="passwordValue"
                                    @input="document.getElementById('password_confirmation')?.setCustomValidity('')"
                                    :required="step === 1"
                                    autocomplete="new-password"
                                    minlength="8"
                                    placeholder="••••••••"
                                    aria-required="true"
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                />
                                <button 
                                    type="button" 
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-[#1F1A1A]/40 hover:text-[#8B1C1C] transition-colors focus:outline-none focus:text-[#8B1C1C] touch-target cursor-pointer"
                                >
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" style="display: none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Thin 4-segment strength bar + 13px hint -->
                            <div class="mt-2" x-show="passwordValue && passwordValue.length > 0">
                                <div class="grid grid-cols-4 gap-1.5 h-1">
                                    <div class="rounded-full transition-colors" :class="passwordStrength >= 1 ? (passwordStrength === 1 ? 'bg-destructive' : passwordStrength === 2 ? 'bg-amber-500' : 'bg-emerald-600') : 'bg-[#E8E1D5]'"></div>
                                    <div class="rounded-full transition-colors" :class="passwordStrength >= 2 ? (passwordStrength === 2 ? 'bg-amber-500' : 'bg-emerald-600') : 'bg-[#E8E1D5]'"></div>
                                    <div class="rounded-full transition-colors" :class="passwordStrength >= 3 ? 'bg-emerald-600' : 'bg-[#E8E1D5]'"></div>
                                    <div class="rounded-full transition-colors" :class="passwordStrength >= 4 ? 'bg-emerald-600' : 'bg-[#E8E1D5]'"></div>
                                </div>
                                <p class="text-[13px] text-[#6B6360] mt-1">
                                    <span x-show="passwordStrength <= 1" class="text-destructive font-medium">Weak password (min. 8 characters)</span>
                                    <span x-show="passwordStrength === 2" class="text-amber-600 font-medium">Fair password (add numbers or uppercase)</span>
                                    <span x-show="passwordStrength === 3" class="text-emerald-700 font-medium">Good password</span>
                                    <span x-show="passwordStrength === 4" class="text-emerald-700 font-semibold">Strong password</span>
                                </p>
                            </div>
                            <p x-show="!passwordValue || passwordValue.length === 0" class="text-[13px] text-[#6B6360] mt-1">
                                Minimum 8 characters with letters & numbers
                            </p>
                            <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-1" />
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Confirm Password') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    id="password_confirmation" 
                                    class="auth-input h-[52px] pr-12 @error('password_confirmation') border-destructive @enderror"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    name="password_confirmation"
                                    @input="$el.setCustomValidity('')"
                                    :required="step === 1"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    aria-required="true"
                                    @error('password_confirmation') aria-invalid="true" aria-describedby="password-confirm-error" @enderror
                                />
                                <button 
                                    type="button" 
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-[#1F1A1A]/40 hover:text-[#8B1C1C] transition-colors focus:outline-none focus:text-[#8B1C1C] touch-target cursor-pointer"
                                >
                                    <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showConfirmPassword" style="display: none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-[13px] text-[#6B6360] mt-1">Re-enter password to confirm</p>
                            <x-input-error :messages="$errors->get('password_confirmation')" id="password-confirm-error" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- ----------------- STEP 2: PERSONAL DETAILS ----------------- -->
                <div 
                    x-show="step === 2" 
                    x-ref="step2"
                    style="display: none;"
                    x-transition:enter="transition-opacity duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="space-y-5"
                >
                    <!-- First Name, Last Name, Middle Initial in 1 row on md+ -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                        <!-- First Name -->
                        <div class="md:col-span-5">
                            <label for="first_name" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('First Name') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="first_name" 
                                class="auth-input h-[52px] @error('first_name') border-destructive @enderror" 
                                type="text" 
                                name="first_name" 
                                value="{{ old('first_name') }}" 
                                :required="step === 2"
                                autocomplete="given-name" 
                                autocapitalize="words"
                                placeholder="e.g. Juan" 
                                aria-required="true"
                                @error('first_name') aria-invalid="true" aria-describedby="first-name-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('first_name')" id="first-name-error" class="mt-1" />
                        </div>

                        <!-- Last Name -->
                        <div class="md:col-span-5">
                            <label for="last_name" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Last Name') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="last_name" 
                                class="auth-input h-[52px] @error('last_name') border-destructive @enderror" 
                                type="text" 
                                name="last_name" 
                                value="{{ old('last_name') }}" 
                                :required="step === 2"
                                autocomplete="family-name" 
                                autocapitalize="words"
                                placeholder="e.g. Dela Cruz" 
                                aria-required="true"
                                @error('last_name') aria-invalid="true" aria-describedby="last-name-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('last_name')" id="last-name-error" class="mt-1" />
                        </div>

                        <!-- Middle Initial -->
                        <div class="md:col-span-2">
                            <label for="middle_initial" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('M.I.') }} <span class="text-[12px] text-[#6B6360] font-normal">(Opt)</span>
                            </label>
                            <input 
                                id="middle_initial" 
                                class="auth-input h-[52px] text-center @error('middle_initial') border-destructive @enderror" 
                                type="text" 
                                name="middle_initial" 
                                value="{{ old('middle_initial') }}" 
                                placeholder="M" 
                                maxlength="2"
                                autocapitalize="characters"
                                @error('middle_initial') aria-invalid="true" aria-describedby="middle-initial-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('middle_initial')" id="middle-initial-error" class="mt-1" />
                        </div>
                    </div>

                    <!-- Birthdate & Contact Number in 2-column grid on md+ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Birthdate -->
                        <div>
                            <label for="birthdate" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Birthdate') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="birthdate" 
                                class="auth-input h-[52px] @error('birthdate') border-destructive @enderror" 
                                type="date" 
                                name="birthdate" 
                                value="{{ old('birthdate') }}" 
                                :required="step === 2"
                                autocomplete="bday"
                                aria-required="true"
                                @error('birthdate') aria-invalid="true" aria-describedby="birthdate-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('birthdate')" id="birthdate-error" class="mt-1" />
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="contact_number" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Contact Number') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="contact_number" 
                                class="auth-input h-[52px] @error('contact_number') border-destructive @enderror" 
                                type="tel" 
                                name="contact_number" 
                                value="{{ old('contact_number') }}" 
                                :required="step === 2"
                                autocomplete="tel" 
                                inputmode="tel"
                                placeholder="09XXXXXXXXX" 
                                aria-required="true"
                                @error('contact_number') aria-invalid="true" aria-describedby="contact-error" @enderror
                            />
                            <p class="text-[13px] text-[#6B6360] mt-1">11-digit mobile number</p>
                            <x-input-error :messages="$errors->get('contact_number')" id="contact-error" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- ----------------- STEP 3: ACADEMIC INFORMATION ----------------- -->
                <div 
                    x-show="step === 3" 
                    x-ref="step3"
                    style="display: none;"
                    x-transition:enter="transition-opacity duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="space-y-4"
                >
                    <!-- Notice -->
                    <div class="px-3.5 py-2 rounded-[10px] bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Notice:</strong> All new student accounts are automatically enrolled as <strong class="text-[#8B1C1C]">1st Year</strong> students.</span>
                    </div>

                    <!-- Hidden Year Level -->
                    <input type="hidden" name="year_level" value="1st" />

                    <!-- Student ID & Section in 2-column grid on md+ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Student ID -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="student_id" class="block text-[14px] font-semibold text-[#1F1A1A]">
                                    {{ __('Student ID') }} <span class="text-destructive font-bold">*</span>
                                </label>
                                <span class="text-[12px] text-[#6B6360]">Official 10-digit</span>
                            </div>
                            <input 
                                id="student_id" 
                                class="auth-input h-[50px] @error('student_id') border-destructive @enderror" 
                                type="text" 
                                name="student_id" 
                                value="{{ old('student_id') }}" 
                                :required="step === 3"
                                inputmode="numeric"
                                placeholder="e.g. 2023010305" 
                                aria-required="true"
                                @error('student_id') aria-invalid="true" aria-describedby="student-id-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('student_id')" id="student-id-error" class="mt-1" />
                        </div>

                        <!-- Section -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="section" class="block text-[14px] font-semibold text-[#1F1A1A]">
                                    {{ __('Section') }} <span class="text-destructive font-bold">*</span>
                                </label>
                                <span class="text-[12px] text-[#6B6360]">Format: 1-1, 1-2</span>
                            </div>
                            <input 
                                id="section" 
                                class="auth-input h-[50px] @error('section') border-destructive @enderror" 
                                type="text" 
                                name="section" 
                                value="{{ old('section') }}" 
                                :required="step === 3"
                                placeholder="e.g. 1-1" 
                                aria-required="true"
                                @error('section') aria-invalid="true" aria-describedby="section-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('section')" id="section-error" class="mt-1" />
                        </div>
                    </div>

                    <!-- Department & Degree Program in 2-column grid on md+ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Department -->
                        <div>
                            <label for="department" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Department') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <select 
                                id="department" 
                                class="auth-select h-[50px] @error('department') border-destructive @enderror" 
                                name="department" 
                                x-model="selectedDepartment" 
                                @change="selectedProgram = ''" 
                                :required="step === 3"
                                aria-required="true"
                            >
                                <option value="" disabled>Select Department</option>
                                <template x-for="dept in departments" :key="dept.id">
                                    <option :value="dept.id" x-text="dept.name" :selected="dept.id == selectedDepartment"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('department')" class="mt-1" />
                        </div>

                        <!-- Program -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="program_id" class="block text-[14px] font-semibold text-[#1F1A1A]">
                                    {{ __('Degree Program') }} <span class="text-destructive font-bold">*</span>
                                </label>
                                <span x-show="!selectedDepartment" class="text-[12px] text-[#6B6360]">Select department first</span>
                            </div>
                            <select 
                                id="program_id" 
                                class="auth-select h-[50px] disabled:bg-gray-100 disabled:cursor-not-allowed @error('program_id') border-destructive @enderror" 
                                name="program_id" 
                                x-model="selectedProgram" 
                                :required="step === 3"
                                :disabled="!selectedDepartment"
                                aria-required="true"
                                @error('program_id') aria-invalid="true" aria-describedby="program-error" @enderror
                            >
                                <option value="" disabled>Select Program</option>
                                <template x-for="prog in (departments.find(d => d.id == selectedDepartment)?.programs || [])" :key="prog.id">
                                    <option :value="prog.id" x-text="prog.name + (prog.code ? ' (' + prog.code + ')' : '')" :selected="prog.id == selectedProgram"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('program_id')" id="program-error" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- ----------------- STEP 4: HOME ADDRESS ----------------- -->
                <div 
                    x-show="step === 4" 
                    x-ref="step4"
                    style="display: none;"
                    x-transition:enter="transition-opacity duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="space-y-5"
                >
                    <!-- Address Line 1 -->
                    <div>
                        <label for="address_line1" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                            {{ __('Address Line 1 (Street, Barangay)') }} <span class="text-destructive font-bold">*</span>
                        </label>
                        <input 
                            id="address_line1" 
                            class="auth-input h-[52px] @error('address_line1') border-destructive @enderror" 
                            type="text" 
                            name="address_line1" 
                            value="{{ old('address_line1') }}" 
                            :required="step === 4"
                            autocomplete="address-line1" 
                            placeholder="e.g. Block 5 Lot 12, Brgy. Kaybagal" 
                            aria-required="true"
                            @error('address_line1') aria-invalid="true" aria-describedby="address-error" @enderror
                        />
                        <x-input-error :messages="$errors->get('address_line1')" id="address-error" class="mt-1" />
                    </div>

                    <!-- City & Province in 2-column grid on md+ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- City / Municipality -->
                        <div>
                            <label for="city" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('City / Municipality') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="city" 
                                class="auth-input h-[52px] @error('city') border-destructive @enderror" 
                                type="text" 
                                name="city" 
                                value="{{ old('city') }}" 
                                :required="step === 4"
                                autocomplete="address-level2" 
                                placeholder="e.g. Tagaytay City" 
                                aria-required="true"
                                @error('city') aria-invalid="true" aria-describedby="city-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('city')" id="city-error" class="mt-1" />
                        </div>

                        <!-- Province -->
                        <div>
                            <label for="province" class="block text-[14px] font-semibold text-[#1F1A1A] mb-1.5">
                                {{ __('Province') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="province" 
                                class="auth-input h-[52px] @error('province') border-destructive @enderror" 
                                type="text" 
                                name="province" 
                                value="{{ old('province') }}" 
                                :required="step === 4"
                                autocomplete="address-level1" 
                                placeholder="e.g. Cavite" 
                                aria-required="true"
                                @error('province') aria-invalid="true" aria-describedby="province-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('province')" id="province-error" class="mt-1" />
                        </div>
                    </div>

                    <!-- RA 10173 notice -->
                    <div class="px-3.5 py-2.5 rounded-[10px] bg-[#8B1C1C]/5 border border-[#8B1C1C]/15 text-[13px] text-[#1F1A1A]/80 flex items-center gap-2.5 mt-1">
                        <svg class="w-4 h-4 shrink-0 text-[#8B1C1C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Protected under RA 10173 (Data Privacy Act of 2012). Your records remain confidential.</span>
                    </div>
                </div>

            </div>

            <!-- ================= ZONE 3: FOOTER (shrink-0, mt-auto) ================= -->
            <footer class="shrink-0 pt-5 mt-auto border-t border-[#E8E1D5]">
                <div class="flex items-center justify-between gap-3">
                    <!-- Back Button (left) -->
                    <div>
                        <button 
                            type="button" 
                            x-show="step > 1" 
                            @click="prevStep()" 
                            class="min-h-[50px] h-[50px] px-6 rounded-lg border border-[#8B1C1C] text-[#8B1C1C] font-semibold text-sm hover:bg-[#8B1C1C]/5 active:scale-[0.99] transition-colors flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#8B1C1C]/20 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Back</span>
                        </button>
                    </div>

                    <!-- Next / Complete Registration Buttons (right) -->
                    <div>
                        <!-- Next Button -->
                        <button 
                            type="button" 
                            x-show="step < 4" 
                            @click="nextStep()" 
                            class="min-h-[50px] h-[50px] px-8 rounded-lg bg-[#8B1C1C] hover:bg-[#6B0C0F] text-white text-sm font-semibold shadow-md active:scale-[0.99] transition-colors flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#8B1C1C]/20 cursor-pointer"
                        >
                            <span>Next</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- Complete Registration Submit Button -->
                        <button 
                            type="submit" 
                            x-show="step === 4" 
                            :disabled="isSubmitting"
                            class="min-h-[50px] h-[50px] px-8 rounded-lg bg-[#8B1C1C] hover:bg-[#6B0C0F] text-white text-sm font-semibold shadow-md active:scale-[0.99] transition-colors flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#8B1C1C]/20 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
                        >
                            <svg x-show="isSubmitting" class="animate-spin -ml-0.5 mr-1.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Registering...' : 'Complete Registration'"></span>
                        </button>
                    </div>
                </div>

                <!-- ONE centered line: Already registered? Log in -->
                <div class="mt-4 text-center text-[14px] text-[#6B6360]">
                    <span>Already registered?</span>
                    <a href="{{ route('login') }}" class="font-bold text-[#8B1C1C] hover:text-[#6B0C0F] hover:underline ml-1 focus:outline-none focus:underline transition-colors">
                        Log in
                    </a>
                </div>
            </footer>
        </form>

    </div>

</x-auth-layout>
