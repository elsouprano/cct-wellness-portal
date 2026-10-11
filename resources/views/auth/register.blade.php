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

<x-auth-layout :title="$title" :wide="true">
    
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
                        const firstError = document.querySelector('.border-destructive, [aria-invalid=\'true\']');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
                            this.$refs.formTop?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    }
                }
            },
            prevStep() {
                if (this.step > 1) {
                    this.step--;
                    this.$nextTick(() => {
                        this.$refs.formTop?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
            },
            goToStep(target) {
                if (target < this.step) {
                    this.step = target;
                    this.$nextTick(() => {
                        this.$refs.formTop?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                } else if (target > this.step) {
                    for (let s = this.step; s < target; s++) {
                        if (!this.validateStep(s)) return;
                    }
                    this.step = target;
                    this.$nextTick(() => {
                        this.$refs.formTop?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
            }
        }"
        class="w-full"
    >
        <!-- Top Anchor for smooth scroll -->
        <div x-ref="formTop"></div>

        <!-- Page Header -->
        <div class="text-center mb-6">
            <h2 class="font-heading text-2xl sm:text-3xl font-bold text-primary tracking-tight">Student Registration</h2>
            <p class="text-sm text-[#1F1A1A]/70 mt-1">Create your CCT Wellness Portal account</p>
        </div>

        <!-- Desktop Multi-Step Progress Indicator (≥768px) -->
        <nav aria-label="Registration Progress" class="hidden md:block mb-8">
            <ol class="flex items-center justify-between w-full relative">
                <!-- Connecting Line Track -->
                <div class="absolute top-4 left-6 right-6 h-0.5 bg-[#E8E1D5] -z-0"></div>
                <!-- Active Line Fill -->
                <div 
                    class="absolute top-4 left-6 h-0.5 bg-primary -z-0 transition-all duration-300"
                    :style="'width: calc(' + ((step - 1) / 3 * 100) + '% * 0.88)'"
                ></div>

                <!-- Step 1 Button -->
                <li class="relative z-10 flex flex-col items-center">
                    <button 
                        type="button" 
                        @click="goToStep(1)"
                        :class="step > 1 ? 'bg-primary text-white border-primary cursor-pointer' : (step === 1 ? 'bg-white text-primary border-primary ring-4 ring-primary/10 font-bold' : 'bg-white text-[#1F1A1A]/40 border-[#E8E1D5]')"
                        class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-sm transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                        :aria-current="step === 1 ? 'step' : false"
                    >
                        <template x-if="step > 1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="step <= 1">
                            <span>1</span>
                        </template>
                    </button>
                    <span 
                        @click="goToStep(1)"
                        :class="step === 1 ? 'text-primary font-bold' : (step > 1 ? 'text-[#1F1A1A]/80 font-medium cursor-pointer' : 'text-[#1F1A1A]/40 font-normal')"
                        class="text-xs mt-2 transition-colors select-none"
                    >Account</span>
                </li>

                <!-- Step 2 Button -->
                <li class="relative z-10 flex flex-col items-center">
                    <button 
                        type="button" 
                        @click="goToStep(2)"
                        :class="step > 2 ? 'bg-primary text-white border-primary cursor-pointer' : (step === 2 ? 'bg-white text-primary border-primary ring-4 ring-primary/10 font-bold' : 'bg-white text-[#1F1A1A]/40 border-[#E8E1D5]')"
                        class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-sm transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                        :aria-current="step === 2 ? 'step' : false"
                    >
                        <template x-if="step > 2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="step <= 2">
                            <span>2</span>
                        </template>
                    </button>
                    <span 
                        @click="goToStep(2)"
                        :class="step === 2 ? 'text-primary font-bold' : (step > 2 ? 'text-[#1F1A1A]/80 font-medium cursor-pointer' : 'text-[#1F1A1A]/40 font-normal')"
                        class="text-xs mt-2 transition-colors select-none"
                    >Personal</span>
                </li>

                <!-- Step 3 Button -->
                <li class="relative z-10 flex flex-col items-center">
                    <button 
                        type="button" 
                        @click="goToStep(3)"
                        :class="step > 3 ? 'bg-primary text-white border-primary cursor-pointer' : (step === 3 ? 'bg-white text-primary border-primary ring-4 ring-primary/10 font-bold' : 'bg-white text-[#1F1A1A]/40 border-[#E8E1D5]')"
                        class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-sm transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                        :aria-current="step === 3 ? 'step' : false"
                    >
                        <template x-if="step > 3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="step <= 3">
                            <span>3</span>
                        </template>
                    </button>
                    <span 
                        @click="goToStep(3)"
                        :class="step === 3 ? 'text-primary font-bold' : (step > 3 ? 'text-[#1F1A1A]/80 font-medium cursor-pointer' : 'text-[#1F1A1A]/40 font-normal')"
                        class="text-xs mt-2 transition-colors select-none"
                    >Academic</span>
                </li>

                <!-- Step 4 Button -->
                <li class="relative z-10 flex flex-col items-center">
                    <button 
                        type="button" 
                        @click="goToStep(4)"
                        :class="step === 4 ? 'bg-white text-primary border-primary ring-4 ring-primary/10 font-bold' : 'bg-white text-[#1F1A1A]/40 border-[#E8E1D5]'"
                        class="w-9 h-9 rounded-full border-2 flex items-center justify-center text-sm transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                        :aria-current="step === 4 ? 'step' : false"
                    >
                        <span>4</span>
                    </button>
                    <span 
                        @click="goToStep(4)"
                        :class="step === 4 ? 'text-primary font-bold' : 'text-[#1F1A1A]/40 font-normal'"
                        class="text-xs mt-2 transition-colors select-none"
                    >Address</span>
                </li>
            </ol>
        </nav>

        <!-- Mobile Compact Progress Indicator (<768px) -->
        <div class="md:hidden mb-6 bg-[#FDFBF7] p-3 rounded-xl border border-[#E8E1D5]/70">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="font-semibold text-primary" x-text="'Step ' + step + ' of 4'"></span>
                <span class="font-medium text-[#1F1A1A]/70" x-text="stepTitles[step]"></span>
            </div>
            <div class="w-full bg-[#E8E1D5] h-2 rounded-full overflow-hidden">
                <div 
                    class="bg-primary h-full rounded-full transition-all duration-300" 
                    :style="'width: ' + ((step / 4) * 100) + '%'"
                ></div>
            </div>
        </div>

        <!-- Global Server Error Alert if any error occurred -->
        @if ($errors->any())
            <div role="alert" class="mb-6 p-4 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-sm flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1">
                    <span class="font-semibold block mb-0.5">Please correct the errors in the form:</span>
                    <ul class="list-disc list-inside space-y-0.5 text-xs text-destructive/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Single Form for all steps -->
        <form 
            method="POST" 
            action="{{ route('register') }}" 
            @submit="if (step < 4) { $event.preventDefault(); nextStep(); } else { isSubmitting = true; }"
            @keydown.enter="if (step < 4 && $event.target.tagName === 'INPUT') { $event.preventDefault(); nextStep(); }"
            class="space-y-6"
        >
            @csrf

            <!-- ================= STEP 1: ACCOUNT CREDENTIALS ================= -->
            <div 
                x-show="step === 1" 
                x-ref="step1"
                class="space-y-5"
            >
                <div class="pb-2 border-b border-[#E8E1D5]/60">
                    <h3 class="font-heading text-lg font-bold text-primary flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        Account Credentials
                    </h3>
                    <p class="text-xs text-[#1F1A1A]/60 mt-0.5">Use your official college email for portal access</p>
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                    <p class="text-xs text-[#1F1A1A]/60 mt-1">Must be an institutional address (@citycollegeoftagaytay.edu.ph or @cct.edu.ph)</p>
                    <x-input-error :messages="$errors->get('email')" id="email-error" class="mt-1" />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                        {{ __('Password') }} <span class="text-destructive font-bold">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            class="auth-input pr-12 @error('password') border-destructive @enderror"
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
                            class="absolute inset-y-0 right-0 w-11 h-12 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary touch-target"
                        >
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showPassword" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-[#1F1A1A]/60 mt-1">Must be at least 8 characters with letters and numbers</p>
                    <x-input-error :messages="$errors->get('password')" id="password-error" class="mt-1" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                        {{ __('Confirm Password') }} <span class="text-destructive font-bold">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            id="password_confirmation" 
                            class="auth-input pr-12 @error('password_confirmation') border-destructive @enderror"
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
                            class="absolute inset-y-0 right-0 w-11 h-12 flex items-center justify-center text-[#1F1A1A]/40 hover:text-primary transition-colors focus:outline-none focus:text-primary touch-target"
                        >
                            <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="showConfirmPassword" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" id="password-confirm-error" class="mt-1" />
                </div>
            </div>

            <!-- ================= STEP 2: PERSONAL INFORMATION ================= -->
            <div 
                x-show="step === 2" 
                x-ref="step2"
                style="display: none;"
                class="space-y-5"
            >
                <div class="pb-2 border-b border-[#E8E1D5]/60">
                    <h3 class="font-heading text-lg font-bold text-primary flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Personal Details
                    </h3>
                    <p class="text-xs text-[#1F1A1A]/60 mt-0.5">Your official name and student contact information</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <x-input-error :messages="$errors->get('first_name')" id="first-name-error" class="mt-1" />
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <x-input-error :messages="$errors->get('last_name')" id="last-name-error" class="mt-1" />
                    </div>

                    <!-- Middle Initial (Optional) -->
                    <div>
                        <label for="middle_initial" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
                            {{ __('Middle Initial') }} <span class="text-xs font-normal text-[#1F1A1A]/50">(Optional)</span>
                        </label>
                        <input 
                            id="middle_initial" 
                            class="auth-input @error('middle_initial') border-destructive @enderror" 
                            type="text" 
                            name="middle_initial" 
                            value="{{ old('middle_initial') }}" 
                            placeholder="e.g. M" 
                            maxlength="2"
                            autocapitalize="characters"
                            @error('middle_initial') aria-invalid="true" aria-describedby="middle-initial-error" @enderror
                        />
                        <x-input-error :messages="$errors->get('middle_initial')" id="middle-initial-error" class="mt-1" />
                    </div>

                    <!-- Birthdate -->
                    <div>
                        <label for="birthdate" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <x-input-error :messages="$errors->get('birthdate')" id="birthdate-error" class="mt-1" />
                    </div>

                    <!-- Contact Number -->
                    <div class="sm:col-span-2">
                        <label for="contact_number" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <p class="text-xs text-[#1F1A1A]/60 mt-1">11-digit active Philippine mobile number for guidance notifications</p>
                        <x-input-error :messages="$errors->get('contact_number')" id="contact-error" class="mt-1" />
                    </div>
                </div>
            </div>

            <!-- ================= STEP 3: ACADEMIC INFORMATION ================= -->
            <div 
                x-show="step === 3" 
                x-ref="step3"
                style="display: none;"
                class="space-y-5"
            >
                <div class="pb-2 border-b border-[#E8E1D5]/60">
                    <h3 class="font-heading text-lg font-bold text-primary flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                        Academic Information
                    </h3>
                    <p class="text-xs text-[#1F1A1A]/60 mt-0.5">Your official CCT student profile and department enrollment</p>
                </div>

                <!-- 1st Year Note Banner -->
                <div class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200/80 text-xs text-amber-900 flex items-start gap-2.5">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-bold text-amber-950">Notice:</span> All newly registering student accounts are automatically enrolled as <strong class="font-bold text-primary">1st Year</strong> students.
                    </div>
                </div>

                <!-- Hidden Year Level -->
                <input type="hidden" name="year_level" value="1st" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Student ID -->
                    <div>
                        <label for="student_id" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <p class="text-xs text-[#1F1A1A]/60 mt-1">Official 10-digit CCT Student ID number</p>
                        <x-input-error :messages="$errors->get('student_id')" id="student-id-error" class="mt-1" />
                    </div>

                    <!-- Section -->
                    <div>
                        <label for="section" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <p class="text-xs text-[#1F1A1A]/60 mt-1">Format: Year-Section (e.g. 1-1, 3-1)</p>
                        <x-input-error :messages="$errors->get('section')" id="section-error" class="mt-1" />
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="department" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <x-input-error :messages="$errors->get('department')" class="mt-1" />
                    </div>

                    <!-- Program -->
                    <div>
                        <label for="program_id" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <p x-show="!selectedDepartment" class="text-xs text-[#1F1A1A]/50 mt-1">Please select a department first</p>
                        <x-input-error :messages="$errors->get('program_id')" id="program-error" class="mt-1" />
                    </div>
                </div>
            </div>

            <!-- ================= STEP 4: HOME ADDRESS ================= -->
            <div 
                x-show="step === 4" 
                x-ref="step4"
                style="display: none;"
                class="space-y-5"
            >
                <div class="pb-2 border-b border-[#E8E1D5]/60">
                    <h3 class="font-heading text-lg font-bold text-primary flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                        Home Address
                    </h3>
                    <p class="text-xs text-[#1F1A1A]/60 mt-0.5">Your official residential address for guidance counselor records</p>
                </div>

                <!-- Address Line 1 -->
                <div>
                    <label for="address_line1" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                    <x-input-error :messages="$errors->get('address_line1')" id="address-error" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- City / Municipality -->
                    <div>
                        <label for="city" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <x-input-error :messages="$errors->get('city')" id="city-error" class="mt-1" />
                    </div>

                    <!-- Province -->
                    <div>
                        <label for="province" class="block text-sm font-semibold text-[#1F1A1A] mb-1.5">
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
                        <x-input-error :messages="$errors->get('province')" id="province-error" class="mt-1" />
                    </div>
                </div>

                <!-- RA 10173 Reassurance Notice -->
                <div class="p-3.5 rounded-xl bg-primary/5 border border-primary/15 text-xs text-[#1F1A1A]/80 flex items-start gap-2.5 mt-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <p class="leading-relaxed">
                        By completing registration, your information is protected and strictly held in confidence by the City College of Tagaytay Guidance Unit under Republic Act 10173 (Data Privacy Act of 2012).
                    </p>
                </div>
            </div>

            <!-- ================= DESKTOP/TABLET NAVIGATION (≥768px) ================= -->
            <div class="hidden md:flex items-center justify-between pt-5 border-t border-[#E8E1D5]/70 mt-6">
                <div>
                    <button 
                        type="button" 
                        x-show="step > 1" 
                        @click="prevStep()" 
                        class="min-h-[48px] px-6 rounded-xl border border-primary/30 text-primary font-semibold text-sm hover:bg-primary/5 active:scale-[0.98] transition-all flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        type="button" 
                        x-show="step < 4" 
                        @click="nextStep()" 
                        class="btn-primary min-h-[48px] px-8 rounded-xl text-base font-semibold shadow-md active:scale-[0.98] transition-all flex items-center gap-2"
                    >
                        Next Step
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <button 
                        type="submit" 
                        x-show="step === 4" 
                        :disabled="isSubmitting"
                        class="btn-primary min-h-[48px] px-8 rounded-xl text-base font-semibold shadow-md active:scale-[0.98] transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Registering...' : 'Complete Registration'"></span>
                    </button>
                </div>
            </div>

            <!-- ================= MOBILE STICKY NAVIGATION (<768px) ================= -->
            <div class="md:hidden sticky -bottom-4 sm:-bottom-7 -mx-4 sm:-mx-7 p-3.5 bg-white/95 backdrop-blur-md border-t border-[#E8E1D5]/80 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] flex items-center gap-2.5 z-20 pb-[max(0.875rem,env(safe-area-inset-bottom))] mt-6">
                <button 
                    type="button" 
                    x-show="step > 1" 
                    @click="prevStep()" 
                    class="w-24 min-h-[48px] px-3 rounded-xl border border-primary/30 text-primary font-semibold text-sm hover:bg-primary/5 active:scale-[0.98] transition-all flex items-center justify-center gap-1 shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </button>
                
                <button 
                    type="button" 
                    x-show="step < 4" 
                    @click="nextStep()" 
                    class="flex-1 min-h-[48px] px-4 rounded-xl bg-primary text-white font-semibold text-base shadow-md hover:bg-primary/95 active:scale-[0.98] transition-all flex items-center justify-center gap-1.5"
                >
                    Next Step
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <button 
                    type="submit" 
                    x-show="step === 4" 
                    :disabled="isSubmitting"
                    class="flex-1 min-h-[48px] px-4 rounded-xl bg-primary text-white font-semibold text-base shadow-md hover:bg-primary/95 active:scale-[0.98] transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Registering...' : 'Complete Registration'"></span>
                </button>
            </div>
        </form>

        <!-- Back to Login Link -->
        <div class="text-center mt-6 pt-4 border-t border-[#E8E1D5]/40">
            <p class="text-sm text-[#1F1A1A]/70">
                Already registered? 
                <a href="{{ route('login') }}" class="font-bold text-primary hover:underline transition-all">
                    Log in instead
                </a>
            </p>
        </div>
    </div>

</x-auth-layout>
