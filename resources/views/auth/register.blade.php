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
            departments: {{ isset($departments) ? $departments->toJson() : '[]' }},
            selectedDepartment: '{{ old('department', '') }}',
            selectedProgram: '{{ old('program_id', '') }}',
            showPassword: false,
            showConfirmPassword: false,
            isSubmitting: false,
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
                const inputs = stepEl.querySelectorAll('input:not([type=\'hidden\']), select, textarea');
                for (const input of inputs) {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        input.focus();
                        return false;
                    }
                }
                if (stepNum === 1) {
                    const pass = document.getElementById('password');
                    const confirm = document.getElementById('password_confirmation');
                    if (pass && confirm && pass.value && confirm.value && pass.value !== confirm.value) {
                        confirm.setCustomValidity('Passwords do not match');
                        confirm.reportValidity();
                        confirm.focus();
                        return false;
                    } else if (confirm) {
                        confirm.setCustomValidity('');
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
        <header class="shrink-0 pb-2.5 sm:pb-3 border-b border-[#E8E1D5]/70">
            <div class="flex items-center justify-between gap-2.5">
                <!-- Title & Compact Brand Emblem -->
                <div class="flex items-center gap-2 min-w-0">
                    <x-logo-badge size="sm" href="/" class="shrink-0 w-7 h-7 sm:w-8 sm:h-8" />
                    <div class="min-w-0">
                        <h1 class="font-heading text-base sm:text-lg lg:text-xl font-bold text-primary tracking-tight leading-tight truncate">
                            Student Registration
                        </h1>
                        <p class="hidden sm:block text-[11px] text-[#1F1A1A]/60 truncate font-normal leading-tight">
                            City College of Tagaytay · Guidance Services
                        </p>
                    </div>
                </div>

                <!-- Step Indicator Pill & Dots -->
                <div class="flex items-center gap-2 shrink-0">
                    <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                        Step <span x-text="step" class="mx-0.5 font-bold"></span> of 4
                        <span class="hidden md:inline text-primary/40 mx-1">·</span>
                        <span class="hidden md:inline font-medium" x-text="stepTitles[step]"></span>
                    </span>

                    <!-- Slim Stepper Dots (Clickable for quick back-navigation) -->
                    <div class="flex items-center gap-1 sm:gap-1.5" aria-label="Registration steps">
                        <template x-for="i in [1, 2, 3, 4]" :key="i">
                            <button 
                                type="button" 
                                @click="goToStep(i)"
                                :class="{
                                    'bg-primary ring-2 ring-primary/20 text-white font-bold': step === i,
                                    'bg-primary/80 text-white cursor-pointer': step > i,
                                    'bg-[#E8E1D5] text-[#1F1A1A]/40': step < i
                                }"
                                class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] transition-all focus:outline-none"
                                :aria-label="'Go to step ' + i"
                            >
                                <template x-if="step > i">
                                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </template>
                                <template x-if="step <= i">
                                    <span x-text="i"></span>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Compact 1-line Error Banner -->
            @if ($errors->any())
                <div role="alert" class="mt-2 px-2.5 py-1 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-xs font-medium flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="truncate">
                        {{ $errors->count() }} {{ Str::plural('field', $errors->count()) }} need attention — check highlighted inputs.
                    </span>
                </div>
            @endif
        </header>

        <!-- ================= ZONE 2: BODY (flex-1 min-h-0 overflow-y-auto) ================= -->
        <form 
            method="POST" 
            action="{{ route('register') }}" 
            @submit="if (step < 4) { $event.preventDefault(); nextStep(); } else { isSubmitting = true; }"
            @keydown.enter="if (step < 4 && $event.target.tagName === 'INPUT') { $event.preventDefault(); nextStep(); }"
            class="flex flex-col flex-1 min-h-0 h-full"
        >
            @csrf

            <div x-ref="stepBody" class="flex-1 min-h-0 overflow-y-auto py-2.5 sm:py-3.5 space-y-3 pr-0.5">

                <!-- ----------------- STEP 1: ACCOUNT CREDENTIALS ----------------- -->
                <div 
                    x-show="step === 1" 
                    x-ref="step1"
                    class="space-y-3"
                >
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                            {{ __('Institutional Email') }} <span class="text-destructive font-bold">*</span>
                        </label>
                        <input 
                            id="email" 
                            class="auth-input @error('email') border-destructive @enderror" 
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
                        <p class="text-[11px] text-[#1F1A1A]/60 mt-0.5">Official college domain: @citycollegeoftagaytay.edu.ph or @cct.edu.ph</p>
                        <x-input-error :messages="$errors->get('email')" id="email-error" class="mt-0.5" />
                    </div>

                    <!-- Password and Confirm Password in 2-column grid on sm+ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Password') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    id="password" 
                                    class="auth-input pr-11 @error('password') border-destructive @enderror"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
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
                                    :aria-pressed="showPassword.toString()"
                                    class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary touch-target"
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
                            <p class="text-[11px] text-[#1F1A1A]/60 mt-0.5">Min 8 chars with letters & numbers</p>
                            <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-0.5" />
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Confirm Password') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    id="password_confirmation" 
                                    class="auth-input pr-11 @error('password_confirmation') border-destructive @enderror"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    name="password_confirmation"
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
                                    :aria-pressed="showConfirmPassword.toString()"
                                    class="absolute inset-y-0 right-0 w-10 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary touch-target"
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
                            <p class="text-[11px] text-transparent mt-0.5 select-none" aria-hidden="true">Spacer</p>
                            <x-input-error :messages="$errors->get('password_confirmation')" id="password-confirm-error" class="mt-0.5" />
                        </div>
                    </div>
                </div>

                <!-- ----------------- STEP 2: PERSONAL DETAILS ----------------- -->
                <div 
                    x-show="step === 2" 
                    x-ref="step2"
                    style="display: none;"
                    class="space-y-3"
                >
                    <!-- First Name, Last Name, Middle Initial in 1 row on sm+ -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 sm:gap-3">
                        <!-- First Name -->
                        <div class="sm:col-span-5">
                            <label for="first_name" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('First Name') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="first_name" 
                                class="auth-input @error('first_name') border-destructive @enderror" 
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
                            <x-input-error :messages="$errors->get('first_name')" id="first-name-error" class="mt-0.5" />
                        </div>

                        <!-- Last Name -->
                        <div class="sm:col-span-5">
                            <label for="last_name" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Last Name') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="last_name" 
                                class="auth-input @error('last_name') border-destructive @enderror" 
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
                            <x-input-error :messages="$errors->get('last_name')" id="last-name-error" class="mt-0.5" />
                        </div>

                        <!-- Middle Initial -->
                        <div class="sm:col-span-2">
                            <label for="middle_initial" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('M.I.') }} <span class="text-[10px] text-[#1F1A1A]/40 font-normal">(Opt)</span>
                            </label>
                            <input 
                                id="middle_initial" 
                                class="auth-input text-center @error('middle_initial') border-destructive @enderror" 
                                type="text" 
                                name="middle_initial" 
                                value="{{ old('middle_initial') }}" 
                                placeholder="M" 
                                maxlength="2"
                                autocapitalize="characters"
                                @error('middle_initial') aria-invalid="true" aria-describedby="middle-initial-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('middle_initial')" id="middle-initial-error" class="mt-0.5" />
                        </div>
                    </div>

                    <!-- Birthdate & Contact Number in 2-column grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <!-- Birthdate -->
                        <div>
                            <label for="birthdate" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Birthdate') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="birthdate" 
                                class="auth-input @error('birthdate') border-destructive @enderror" 
                                type="date" 
                                name="birthdate" 
                                value="{{ old('birthdate') }}" 
                                :required="step === 2"
                                autocomplete="bday"
                                aria-required="true"
                                @error('birthdate') aria-invalid="true" aria-describedby="birthdate-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('birthdate')" id="birthdate-error" class="mt-0.5" />
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="contact_number" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Contact Number') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="contact_number" 
                                class="auth-input @error('contact_number') border-destructive @enderror" 
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
                            <p class="text-[11px] text-[#1F1A1A]/60 mt-0.5">11-digit mobile number</p>
                            <x-input-error :messages="$errors->get('contact_number')" id="contact-error" class="mt-0.5" />
                        </div>
                    </div>
                </div>

                <!-- ----------------- STEP 3: ACADEMIC INFORMATION ----------------- -->
                <div 
                    x-show="step === 3" 
                    x-ref="step3"
                    style="display: none;"
                    class="space-y-3"
                >
                    <!-- Compact 1st Year Indicator Notice -->
                    <div class="px-2.5 py-1.5 rounded-lg bg-amber-50/90 border border-amber-200/90 text-[11px] text-amber-950 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 shrink-0 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong class="font-bold">Notice:</strong> All new student accounts are automatically registered as <strong class="text-primary font-bold">1st Year</strong> students.</span>
                    </div>

                    <!-- Hidden Year Level -->
                    <input type="hidden" name="year_level" value="1st" />

                    <!-- Student ID & Section in 2-column grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <!-- Student ID -->
                        <div>
                            <label for="student_id" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Student ID') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="student_id" 
                                class="auth-input @error('student_id') border-destructive @enderror" 
                                type="text" 
                                name="student_id" 
                                value="{{ old('student_id') }}" 
                                :required="step === 3"
                                inputmode="numeric"
                                placeholder="e.g. 2023010305" 
                                aria-required="true"
                                @error('student_id') aria-invalid="true" aria-describedby="student-id-error" @enderror
                            />
                            <p class="text-[11px] text-[#1F1A1A]/60 mt-0.5">Official 10-digit Student ID</p>
                            <x-input-error :messages="$errors->get('student_id')" id="student-id-error" class="mt-0.5" />
                        </div>

                        <!-- Section -->
                        <div>
                            <label for="section" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Section') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="section" 
                                class="auth-input @error('section') border-destructive @enderror" 
                                type="text" 
                                name="section" 
                                value="{{ old('section') }}" 
                                :required="step === 3"
                                placeholder="e.g. 3-1" 
                                aria-required="true"
                                @error('section') aria-invalid="true" aria-describedby="section-error" @enderror
                            />
                            <p class="text-[11px] text-[#1F1A1A]/60 mt-0.5">Format: Year-Section (e.g. 1-1, 3-1)</p>
                            <x-input-error :messages="$errors->get('section')" id="section-error" class="mt-0.5" />
                        </div>
                    </div>

                    <!-- Department & Program in 2-column grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <!-- Department -->
                        <div>
                            <label for="department" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Department') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <select 
                                id="department" 
                                class="auth-select @error('department') border-destructive @enderror" 
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
                            <x-input-error :messages="$errors->get('department')" class="mt-0.5" />
                        </div>

                        <!-- Program -->
                        <div>
                            <label for="program_id" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Degree Program') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <select 
                                id="program_id" 
                                class="auth-select disabled:bg-gray-100 disabled:cursor-not-allowed @error('program_id') border-destructive @enderror" 
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
                            <p x-show="!selectedDepartment" class="text-[11px] text-[#1F1A1A]/50 mt-0.5">Select a department first</p>
                            <x-input-error :messages="$errors->get('program_id')" id="program-error" class="mt-0.5" />
                        </div>
                    </div>
                </div>

                <!-- ----------------- STEP 4: HOME ADDRESS ----------------- -->
                <div 
                    x-show="step === 4" 
                    x-ref="step4"
                    style="display: none;"
                    class="space-y-3"
                >
                    <!-- Address Line 1 -->
                    <div>
                        <label for="address_line1" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                            {{ __('Address Line 1 (Street, Barangay)') }} <span class="text-destructive font-bold">*</span>
                        </label>
                        <input 
                            id="address_line1" 
                            class="auth-input @error('address_line1') border-destructive @enderror" 
                            type="text" 
                            name="address_line1" 
                            value="{{ old('address_line1') }}" 
                            :required="step === 4"
                            autocomplete="address-line1" 
                            placeholder="e.g. Block 5 Lot 12, Brgy. Kaybagal" 
                            aria-required="true"
                            @error('address_line1') aria-invalid="true" aria-describedby="address-error" @enderror
                        />
                        <x-input-error :messages="$errors->get('address_line1')" id="address-error" class="mt-0.5" />
                    </div>

                    <!-- City & Province in 2-column grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <!-- City / Municipality -->
                        <div>
                            <label for="city" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('City / Municipality') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="city" 
                                class="auth-input @error('city') border-destructive @enderror" 
                                type="text" 
                                name="city" 
                                value="{{ old('city') }}" 
                                :required="step === 4"
                                autocomplete="address-level2" 
                                placeholder="e.g. Tagaytay City" 
                                aria-required="true"
                                @error('city') aria-invalid="true" aria-describedby="city-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('city')" id="city-error" class="mt-0.5" />
                        </div>

                        <!-- Province -->
                        <div>
                            <label for="province" class="block text-xs sm:text-sm font-semibold text-[#1F1A1A] mb-1">
                                {{ __('Province') }} <span class="text-destructive font-bold">*</span>
                            </label>
                            <input 
                                id="province" 
                                class="auth-input @error('province') border-destructive @enderror" 
                                type="text" 
                                name="province" 
                                value="{{ old('province') }}" 
                                :required="step === 4"
                                autocomplete="address-level1" 
                                placeholder="e.g. Cavite" 
                                aria-required="true"
                                @error('province') aria-invalid="true" aria-describedby="province-error" @enderror
                            />
                            <x-input-error :messages="$errors->get('province')" id="province-error" class="mt-0.5" />
                        </div>
                    </div>

                    <!-- Compact 1-line RA 10173 notice -->
                    <div class="px-2.5 py-1.5 rounded-lg bg-primary/5 border border-primary/15 text-[11px] text-[#1F1A1A]/80 flex items-center gap-2 mt-1" title="By completing registration, your information is protected and strictly held in confidence by the City College of Tagaytay Guidance Unit under Republic Act 10173 (Data Privacy Act of 2012).">
                        <svg class="w-3.5 h-3.5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="truncate">Protected under RA 10173 (Data Privacy Act of 2012). Information remains confidential.</span>
                    </div>
                </div>

            </div>

            <!-- ================= ZONE 3: FOOTER (shrink-0) ================= -->
            <footer class="shrink-0 pt-2 sm:pt-2.5 border-t border-[#E8E1D5]/70 flex items-center justify-between gap-2 pb-[max(0.25rem,env(safe-area-inset-bottom))]">
                <!-- Login link on the left -->
                <div class="min-w-0 text-left">
                    <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold text-primary hover:underline transition-colors flex items-center gap-1">
                        <span class="hidden sm:inline text-[#1F1A1A]/60 font-normal">Already registered?</span> Log in
                    </a>
                </div>

                <!-- Navigation buttons on the right -->
                <div class="flex items-center gap-2 shrink-0">
                    <!-- Back Button -->
                    <button 
                        type="button" 
                        x-show="step > 1" 
                        @click="prevStep()" 
                        class="btn-compact min-h-[44px] px-3 sm:px-4 rounded-xl border border-primary/30 text-primary font-semibold text-xs sm:text-sm hover:bg-primary/5 active:scale-[0.98] transition-all flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>

                    <!-- Next Step Button -->
                    <button 
                        type="button" 
                        x-show="step < 4" 
                        @click="nextStep()" 
                        class="btn-compact btn-primary min-h-[44px] px-4.5 sm:px-6 rounded-xl text-xs sm:text-sm font-semibold shadow-sm active:scale-[0.98] transition-all flex items-center gap-1.5"
                    >
                        Next Step
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Complete Registration Submit Button -->
                    <button 
                        type="submit" 
                        x-show="step === 4" 
                        :disabled="isSubmitting"
                        class="btn-compact btn-primary min-h-[44px] px-4.5 sm:px-6 rounded-xl text-xs sm:text-sm font-semibold shadow-sm active:scale-[0.98] transition-all flex items-center gap-1.5 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <svg x-show="isSubmitting" class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Registering...' : 'Complete Registration'"></span>
                    </button>
                </div>
            </footer>
        </form>

    </div>

</x-auth-layout>
