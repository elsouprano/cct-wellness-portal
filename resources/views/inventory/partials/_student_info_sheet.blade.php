@php
    $sisPartsMeta = [
        1 => ['title' => 'Personal Data', 'subtitle' => 'Verify your personal profile, student record, and employment status.'],
        2 => ['title' => 'Educational & Health Background', 'subtitle' => 'Previous schools attended, special status, and family health history.'],
        3 => ['title' => 'Residence, Family & Emergency Contact', 'subtitle' => 'Home arrangement, parents/guardians, siblings, and emergency details.'],
        4 => ['title' => 'Interests, Personality & Guidance Support', 'subtitle' => 'Goals, personal traits, hobbies, and areas where Counseling can support you.'],
    ];
@endphp

<div x-show="step === 0 && !sisReady"
     x-cloak
     data-category="student_information_sheet"
     @input="if ($event.target?.setAttribute) $event.target.setAttribute('aria-invalid', 'false')"
     @change="if ($event.target?.setAttribute) $event.target.setAttribute('aria-invalid', 'false')"
     class="sis-form flex-1 min-h-0 flex flex-col">
    <style>
        .sis-form input[aria-invalid="true"],
        .sis-form select[aria-invalid="true"],
        .sis-form textarea[aria-invalid="true"] {
            border-color: rgb(220 38 38);
            background-color: rgb(254 242 242 / 0.5);
        }
    </style>

    <!-- Header -->
    <div class="px-4 sm:px-6 py-3 border-b border-border bg-surface shrink-0">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="text-xs sm:text-sm font-bold text-primary truncate">
                    Before Inventory Questions &middot; Student Information Sheet
                </p>
                <p class="text-[11px] sm:text-xs text-muted-foreground truncate" x-text="'Part ' + sisPart + ' of 4: ' + (sisPartsMeta[sisPart]?.title || '')">
                    Part 1 of 4: Personal Data
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($sisCompleted ?? false)
                    <button type="button"
                            @click="sisReady = true"
                            class="btn-secondary min-h-[36px] px-3 text-xs cursor-pointer">
                        Back to Consent &rarr;
                    </button>
                @endif
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-primary-subtle text-primary border border-primary/20 tabular-nums"
                      x-text="'Part ' + sisPart + ' of 4'">Part 1 of 4</span>
            </div>
        </div>
        <div class="mt-2 grid grid-cols-4 gap-1.5" role="progressbar" :aria-valuenow="sisPart" aria-valuemin="1" aria-valuemax="4" aria-label="Student Information Sheet progress">
            @for($p = 1; $p <= 4; $p++)
                <button type="button"
                        @click="goToSisPart({{ $p }})"
                        class="h-1.5 rounded-full transition-colors cursor-pointer"
                        :class="sisPart >= {{ $p }} ? 'bg-primary' : 'bg-muted'"
                        aria-label="Go to Part {{ $p }}"></button>
            @endfor
        </div>
    </div>

    <!-- Scrollable Form Body -->
    <div x-ref="sisStage" class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-4 sm:py-5 space-y-5">
        <div class="bg-primary-subtle/60 border border-primary/20 rounded-token-md px-3.5 py-2.5 text-xs text-foreground/90 leading-relaxed">
            <span class="font-semibold text-primary">Instructions:</span>
            Fill out all required (<span class="text-destructive font-bold">*</span>) fields of the City College of Tagaytay Student Information Sheet accurately before proceeding to Data Privacy Consent and the Inventory Questions. Write <strong>N/A</strong> if not applicable.
        </div>

        <!-- Inline Validation Summary -->
        <div x-show="sisBannerError"
             x-cloak
             role="alert"
             class="bg-destructive-subtle border-l-4 border-destructive text-destructive-foreground px-3.5 py-2.5 rounded-r-token-md text-xs sm:text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" role="img" aria-label="Error">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            <span x-text="sisBannerError"></span>
        </div>

        <!-- ==================== PART 1: PERSONAL DATA ==================== -->
        <div x-show="sisPart === 1" data-sis-part="1" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label for="sis_full_name" class="block text-xs font-semibold text-foreground mb-1">Full Name</label>
                    <input id="sis_full_name" type="text" x-model="sis.full_name" readonly class="input-field w-full min-h-[44px] text-sm bg-muted/40 text-muted-foreground cursor-not-allowed">
                </div>
                <div>
                    <label for="sis_nickname" class="block text-xs font-semibold text-foreground mb-1 label-required">Nickname <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_nickname" name="sis[nickname]" type="text" x-model="sis.nickname" autocomplete="nickname" required :aria-invalid="hasSisError('nickname')" class="input-field w-full min-h-[44px] text-sm" placeholder="Your nickname">
                    <p x-show="hasSisError('nickname')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('nickname')"></p>
                </div>

                <div>
                    <label for="sis_sex" class="block text-xs font-semibold text-foreground mb-1">Sex / Gender</label>
                    <select id="sis_sex" name="sis[sex]" x-model="sis.sex" class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select sex / gender</option>
                        @foreach(\App\Models\User::GENDERS as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_birthdate" class="block text-xs font-semibold text-foreground mb-1 label-required">Birthdate <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_birthdate" name="sis[birthdate]" type="date" x-model="sis.birthdate" autocomplete="bday" required :aria-invalid="hasSisError('birthdate')" class="input-field w-full min-h-[44px] text-sm">
                    <p x-show="hasSisError('birthdate')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('birthdate')"></p>
                </div>
                <div>
                    <label for="sis_place_of_birth" class="block text-xs font-semibold text-foreground mb-1 label-required">Place of Birth <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_place_of_birth" name="sis[place_of_birth]" type="text" x-model="sis.place_of_birth" required :aria-invalid="hasSisError('place_of_birth')" class="input-field w-full min-h-[44px] text-sm" placeholder="City / Municipality, Province">
                    <p x-show="hasSisError('place_of_birth')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('place_of_birth')"></p>
                </div>

                <div>
                    <label for="sis_civil_status" class="block text-xs font-semibold text-foreground mb-1">Civil Status</label>
                    <input id="sis_civil_status" name="sis[civil_status]" type="text" x-model="sis.civil_status" class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. Single">
                </div>
                <div>
                    <label for="sis_citizenship" class="block text-xs font-semibold text-foreground mb-1 label-required">Citizenship <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_citizenship" name="sis[citizenship]" type="text" x-model="sis.citizenship" required :aria-invalid="hasSisError('citizenship')" class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. Filipino">
                    <p x-show="hasSisError('citizenship')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('citizenship')"></p>
                </div>
                <div>
                    <label for="sis_religion" class="block text-xs font-semibold text-foreground mb-1 label-required">Religion <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_religion" name="sis[religion]" type="text" x-model="sis.religion" required :aria-invalid="hasSisError('religion')" class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. Roman Catholic (or N/A)">
                    <p x-show="hasSisError('religion')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('religion')"></p>
                </div>

                <div class="sm:col-span-2 lg:col-span-2">
                    <label for="sis_address_line1" class="block text-xs font-semibold text-foreground mb-1 label-required">Home Address (House No., Street, Barangay) <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_address_line1" name="sis[address_line1]" type="text" x-model="sis.address_line1" autocomplete="street-address" required :aria-invalid="hasSisError('address_line1')" class="input-field w-full min-h-[44px] text-sm">
                    <p x-show="hasSisError('address_line1')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('address_line1')"></p>
                </div>
                <div>
                    <label for="sis_city" class="block text-xs font-semibold text-foreground mb-1 label-required">City / Municipality <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_city" name="sis[city]" type="text" x-model="sis.city" autocomplete="address-level2" required :aria-invalid="hasSisError('city')" class="input-field w-full min-h-[44px] text-sm">
                    <p x-show="hasSisError('city')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('city')"></p>
                </div>
                <div>
                    <label for="sis_province" class="block text-xs font-semibold text-foreground mb-1 label-required">Province <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_province" name="sis[province]" type="text" x-model="sis.province" autocomplete="address-level1" required :aria-invalid="hasSisError('province')" class="input-field w-full min-h-[44px] text-sm">
                    <p x-show="hasSisError('province')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('province')"></p>
                </div>

                <div>
                    <label for="sis_course" class="block text-xs font-semibold text-foreground mb-1 label-required">Course / Program <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_course" name="sis[course]" type="text" x-model="sis.course" required :aria-invalid="hasSisError('course')" class="input-field w-full min-h-[44px] text-sm">
                    <p x-show="hasSisError('course')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('course')"></p>
                </div>
                <div>
                    <label for="sis_student_number" class="block text-xs font-semibold text-foreground mb-1 label-required">Student Number <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_student_number" name="sis[student_number]" type="text" x-model="sis.student_number" required :aria-invalid="hasSisError('student_number')" class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. 2026-0001">
                    <p x-show="hasSisError('student_number')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('student_number')"></p>
                </div>
                <div>
                    <label for="sis_section" class="block text-xs font-semibold text-foreground mb-1 label-required">Section <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_section" name="sis[section]" type="text" x-model="sis.section" required :aria-invalid="hasSisError('section')" class="input-field w-full min-h-[44px] text-sm">
                    <p x-show="hasSisError('section')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('section')"></p>
                </div>

                <div>
                    <label for="sis_type_of_student" class="block text-xs font-semibold text-foreground mb-1 label-required">Type of Student <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_type_of_student" name="sis[type_of_student]" x-model="sis.type_of_student" required :aria-invalid="hasSisError('type_of_student')" class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select type</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::STUDENT_TYPES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                    <p x-show="hasSisError('type_of_student')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('type_of_student')"></p>
                </div>
                <div>
                    <label for="sis_school_year_admitted" class="block text-xs font-semibold text-foreground mb-1 label-required">School Year Admitted <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_school_year_admitted" name="sis[school_year_admitted]" type="text" x-model="sis.school_year_admitted" required :aria-invalid="hasSisError('school_year_admitted')" class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. 2025-2026">
                    <p x-show="hasSisError('school_year_admitted')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('school_year_admitted')"></p>
                </div>
                <div>
                    <label for="sis_phone" class="block text-xs font-semibold text-foreground mb-1 label-required">Contact Number (11 digits) <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_phone" name="sis[phone]" type="text" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" x-model="sis.phone" @input="sis.phone = sis.phone.replace(/[^0-9]/g, '').slice(0, 11)" autocomplete="tel" required :aria-invalid="hasSisError('phone')" class="input-field w-full min-h-[44px] text-sm" placeholder="09XXXXXXXXX">
                    <p x-show="hasSisError('phone')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('phone')"></p>
                </div>

                <div>
                    <label for="sis_personal_email" class="block text-xs font-semibold text-foreground mb-1 label-required">Personal Email Address <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_personal_email" name="sis[personal_email]" type="email" x-model="sis.personal_email" autocomplete="email" required :aria-invalid="hasSisError('personal_email')" class="input-field w-full min-h-[44px] text-sm" placeholder="you@gmail.com">
                    <p x-show="hasSisError('personal_email')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('personal_email')"></p>
                </div>
                <div>
                    <label for="sis_school_email" class="block text-xs font-semibold text-foreground mb-1 label-required">School Email Address <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_school_email" name="sis[school_email]" type="email" x-model="sis.school_email" autocomplete="email" required :aria-invalid="hasSisError('school_email')" class="input-field w-full min-h-[44px] text-sm" placeholder="student@cct.edu.ph">
                    <p x-show="hasSisError('school_email')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('school_email')"></p>
                </div>
                <div>
                    <label for="sis_is_working_student" class="block text-xs font-semibold text-foreground mb-1 label-required">Are you a Working Student? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_is_working_student" name="sis[is_working_student]" x-model="sis.is_working_student" @change="if (sis.is_working_student !== 'Yes') { sis.employment_status = 'N/A'; sis.company_name = 'N/A'; sis.company_address = 'N/A'; } else if (sis.employment_status === 'N/A') { sis.employment_status = ''; sis.company_name = ''; sis.company_address = ''; }" required :aria-invalid="hasSisError('is_working_student')" class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                    <p x-show="hasSisError('is_working_student')" x-cloak class="mt-1 text-xs text-destructive" x-text="sisError('is_working_student')"></p>
                </div>

                <div x-show="sis.is_working_student === 'Yes'" x-cloak>
                    <label for="sis_employment_status" class="block text-xs font-semibold text-foreground mb-1 label-required">Status of Employment <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_employment_status" name="sis[employment_status]" x-model="sis.employment_status" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select status</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::EMPLOYMENT_STATUSES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="sis.is_working_student === 'Yes'" x-cloak>
                    <label for="sis_company_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Name of Company <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_company_name" name="sis[company_name]" type="text" x-model="sis.company_name" required class="input-field w-full min-h-[44px] text-sm" placeholder="Company name or N/A">
                </div>
                <div x-show="sis.is_working_student === 'Yes'" x-cloak>
                    <label for="sis_company_address" class="block text-xs font-semibold text-foreground mb-1 label-required">Company Address <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_company_address" name="sis[company_address]" type="text" x-model="sis.company_address" required class="input-field w-full min-h-[44px] text-sm" placeholder="Company address or N/A">
                </div>
            </div>
        </div>

        <!-- ==================== PART 2: EDUCATIONAL & HEALTH BACKGROUND ==================== -->
        <div x-show="sisPart === 2" x-cloak data-sis-part="2" class="space-y-4">
            <h3 class="text-sm font-bold text-foreground border-b border-border pb-1.5">Educational Background</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="sis_elementary_school" class="block text-xs font-semibold text-foreground mb-1 label-required">Elementary Education: School Attended <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_elementary_school" name="sis[elementary_school]" type="text" x-model="sis.elementary_school" required :aria-invalid="hasSisError('elementary_school')" class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_elementary_year_graduated" class="block text-xs font-semibold text-foreground mb-1 label-required">Year Graduated <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_elementary_year_graduated" name="sis[elementary_year_graduated]" type="text" x-model="sis.elementary_year_graduated" required class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. 2018">
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="sis_secondary_school" class="block text-xs font-semibold text-foreground mb-1 label-required">Secondary Education (SHS): School Attended <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_secondary_school" name="sis[secondary_school]" type="text" x-model="sis.secondary_school" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_secondary_year_graduated" class="block text-xs font-semibold text-foreground mb-1 label-required">Year Graduated <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_secondary_year_graduated" name="sis[secondary_year_graduated]" type="text" x-model="sis.secondary_year_graduated" required class="input-field w-full min-h-[44px] text-sm" placeholder="e.g. 2024">
                </div>

                <div>
                    <label for="sis_attended_als" class="block text-xs font-semibold text-foreground mb-1 label-required">Attended ALS? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_attended_als" name="sis[attended_als]" x-model="sis.attended_als" @change="if (sis.attended_als !== 'Yes') { sis.als_school = 'N/A'; sis.als_year_graduated = 'N/A'; } else if (sis.als_school === 'N/A') { sis.als_school = ''; sis.als_year_graduated = ''; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
                <div x-show="sis.attended_als === 'Yes'" x-cloak class="sm:col-span-2">
                    <label for="sis_als_school" class="block text-xs font-semibold text-foreground mb-1 label-required">ALS School Attended <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_als_school" name="sis[als_school]" type="text" x-model="sis.als_school" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div x-show="sis.attended_als === 'Yes'" x-cloak>
                    <label for="sis_als_year_graduated" class="block text-xs font-semibold text-foreground mb-1 label-required">ALS Year Graduated <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_als_year_graduated" name="sis[als_year_graduated]" type="text" x-model="sis.als_year_graduated" required class="input-field w-full min-h-[44px] text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label for="sis_tertiary_school" class="block text-xs font-semibold text-foreground mb-1 label-required">Tertiary Education (Transferees; N/A if none) <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_tertiary_school" name="sis[tertiary_school]" type="text" x-model="sis.tertiary_school" required class="input-field w-full min-h-[44px] text-sm" placeholder="N/A if not applicable">
                </div>
                <div>
                    <label for="sis_tertiary_last_year_attended" class="block text-xs font-semibold text-foreground mb-1 label-required">Last SY Attended <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_tertiary_last_year_attended" name="sis[tertiary_last_year_attended]" type="text" x-model="sis.tertiary_last_year_attended" required class="input-field w-full min-h-[44px] text-sm" placeholder="N/A if not applicable">
                </div>
                <div>
                    <label for="sis_course_choice" class="block text-xs font-semibold text-foreground mb-1 label-required">Is your course your <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_course_choice" name="sis[course_choice]" x-model="sis.course_choice" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select choice</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::COURSE_CHOICES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h3 class="text-sm font-bold text-foreground border-b border-border pb-1.5 pt-2">Health &amp; Special Profile Background</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="sis_special_status" class="block text-xs font-semibold text-foreground mb-1 label-required">Are you a: <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_special_status" name="sis[special_status]" x-model="sis.special_status" @change="if (sis.special_status !== 'Person with Disability') { sis.disability_type = 'Not Applicable'; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select category</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::SPECIAL_STATUSES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="sis.special_status === 'Person with Disability'" x-cloak>
                    <label for="sis_disability_type" class="block text-xs font-semibold text-foreground mb-1 label-required">Type of Disability <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_disability_type" name="sis[disability_type]" x-model="sis.disability_type" required class="input-field w-full min-h-[44px] text-sm">
                        @foreach(\App\Support\StudentInformationSheetSchema::DISABILITY_TYPES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_hospitalized_before" class="block text-xs font-semibold text-foreground mb-1 label-required">Have you been confined in a hospital? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_hospitalized_before" name="sis[hospitalized_before]" x-model="sis.hospitalized_before" @change="if (sis.hospitalized_before !== 'Yes') { sis.hospitalization_cause = 'N/A'; } else if (sis.hospitalization_cause === 'N/A') { sis.hospitalization_cause = ''; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div x-show="sis.hospitalized_before === 'Yes'" x-cloak>
                    <label for="sis_hospitalization_cause" class="block text-xs font-semibold text-foreground mb-1 label-required">If YES, what is the cause? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_hospitalization_cause" name="sis[hospitalization_cause]" type="text" x-model="sis.hospitalization_cause" required class="input-field w-full min-h-[44px] text-sm">
                </div>
            </div>

            <fieldset class="border border-border rounded-token-md p-3.5 bg-surface-subtle/50">
                <legend class="px-1.5 text-xs font-semibold text-foreground">
                    Please check all medical condition(s) that run in your family <span class="text-destructive" aria-hidden="true">*</span>
                </legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
                    @foreach(\App\Support\StudentInformationSheetSchema::FAMILY_MEDICAL_CONDITIONS as $cond)
                        <label class="min-h-[44px] flex items-center gap-2.5 px-3 py-2 rounded-token-md border border-border bg-surface hover:bg-surface-subtle cursor-pointer text-xs sm:text-sm">
                            <input type="checkbox"
                                   name="sis[family_medical_conditions][]"
                                   value="{{ $cond }}"
                                   :checked="(sis.family_medical_conditions || []).includes(@js($cond))"
                                   @change="toggleSisCheckbox('family_medical_conditions', @js($cond), 'None that I know of.')"
                                   class="w-4 h-4 text-primary border-border-strong rounded focus:ring-primary shrink-0">
                            <span class="leading-snug">{{ $cond }}</span>
                        </label>
                    @endforeach
                </div>
                <p x-show="hasSisError('family_medical_conditions')" x-cloak class="mt-2 text-xs text-destructive" x-text="sisError('family_medical_conditions')"></p>
            </fieldset>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="sis_smoking_frequency" class="block text-xs font-semibold text-foreground mb-1 label-required">Do you smoke? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_smoking_frequency" name="sis[smoking_frequency]" x-model="sis.smoking_frequency" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::SUBSTANCE_FREQUENCIES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_liquor_frequency" class="block text-xs font-semibold text-foreground mb-1 label-required">Do you drink liquor/s? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_liquor_frequency" name="sis[liquor_frequency]" x-model="sis.liquor_frequency" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::SUBSTANCE_FREQUENCIES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_coffee_frequency" class="block text-xs font-semibold text-foreground mb-1 label-required">Do you drink coffee? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_coffee_frequency" name="sis[coffee_frequency]" x-model="sis.coffee_frequency" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::SUBSTANCE_FREQUENCIES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_prohibited_drugs_frequency" class="block text-xs font-semibold text-foreground mb-1">Prohibited drugs? (Optional)</label>
                    <select id="sis_prohibited_drugs_frequency" name="sis[prohibited_drugs_frequency]" x-model="sis.prohibited_drugs_frequency" class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Prefer not to answer / Select</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::SUBSTANCE_FREQUENCIES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sis_psych_consultation_before" class="block text-xs font-semibold text-foreground mb-1 label-required">Consulted a psychologist/psychiatrist before? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_psych_consultation_before" name="sis[psych_consultation_before]" x-model="sis.psych_consultation_before" @change="if (sis.psych_consultation_before !== 'Yes') { sis.psych_consultation_date = 'N/A'; sis.psych_consultation_reason = 'N/A'; } else if (sis.psych_consultation_date === 'N/A') { sis.psych_consultation_date = ''; sis.psych_consultation_reason = ''; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div x-show="sis.psych_consultation_before === 'Yes'" x-cloak>
                    <label for="sis_psych_consultation_date" class="block text-xs font-semibold text-foreground mb-1 label-required">When was the consultation? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_psych_consultation_date" name="sis[psych_consultation_date]" type="text" x-model="sis.psych_consultation_date" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div x-show="sis.psych_consultation_before === 'Yes'" x-cloak>
                    <label for="sis_psych_consultation_reason" class="block text-xs font-semibold text-foreground mb-1 label-required">Reason for consultation <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_psych_consultation_reason" name="sis[psych_consultation_reason]" type="text" x-model="sis.psych_consultation_reason" required class="input-field w-full min-h-[44px] text-sm">
                </div>
            </div>
        </div>

        <!-- ==================== PART 3: RESIDENCE, FAMILY & EMERGENCY CONTACT ==================== -->
        <div x-show="sisPart === 3" x-cloak data-sis-part="3" class="space-y-4">
            <h3 class="text-sm font-bold text-foreground border-b border-border pb-1.5">Residence &amp; Family Background</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="sis_residence_nature" class="block text-xs font-semibold text-foreground mb-1 label-required">Nature of Residence <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_residence_nature" name="sis[residence_nature]" x-model="sis.residence_nature" @change="if (sis.residence_nature !== 'Boarding house/Dormitory') { sis.boarding_house_address = 'N/A'; } else if (sis.boarding_house_address === 'N/A') { sis.boarding_house_address = ''; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select residence</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::RESIDENCE_NATURES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_residence_ownership" class="block text-xs font-semibold text-foreground mb-1 label-required">House Ownership <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_residence_ownership" name="sis[residence_ownership]" x-model="sis.residence_ownership" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select ownership</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::RESIDENCE_OWNERSHIPS as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="sis.residence_nature === 'Boarding house/Dormitory'" x-cloak>
                    <label for="sis_boarding_house_address" class="block text-xs font-semibold text-foreground mb-1 label-required">Address of Boarding House/Dormitory <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_boarding_house_address" name="sis[boarding_house_address]" type="text" x-model="sis.boarding_house_address" required class="input-field w-full min-h-[44px] text-sm">
                </div>

                <div>
                    <label for="sis_family_setup" class="block text-xs font-semibold text-foreground mb-1 label-required">Family Setup <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_family_setup" name="sis[family_setup]" x-model="sis.family_setup" @change="if (!['Living with mother', 'Living with father'].includes(sis.family_setup)) { sis.one_parent_reason = 'N/A'; } if (sis.family_setup !== 'Living with relatives') { sis.living_with_relatives_relation = 'N/A'; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select family setup</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::FAMILY_SETUPS as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="['Living with mother', 'Living with father'].includes(sis.family_setup)" x-cloak>
                    <label for="sis_one_parent_reason" class="block text-xs font-semibold text-foreground mb-1 label-required">If living with one parent <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_one_parent_reason" name="sis[one_parent_reason]" x-model="sis.one_parent_reason" required class="input-field w-full min-h-[44px] text-sm">
                        @foreach(\App\Support\StudentInformationSheetSchema::ONE_PARENT_REASONS as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="sis.family_setup === 'Living with relatives'" x-cloak>
                    <label for="sis_living_with_relatives_relation" class="block text-xs font-semibold text-foreground mb-1 label-required">If living with relatives <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_living_with_relatives_relation" name="sis[living_with_relatives_relation]" x-model="sis.living_with_relatives_relation" required class="input-field w-full min-h-[44px] text-sm">
                        @foreach(\App\Support\StudentInformationSheetSchema::RELATIVE_RELATIONS as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_family_monthly_income" class="block text-xs font-semibold text-foreground mb-1 label-required">Approx. Family Monthly Income <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_family_monthly_income" name="sis[family_monthly_income]" x-model="sis.family_monthly_income" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select income bracket</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::MONTHLY_INCOMES as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                <!-- Father -->
                <div class="border border-border rounded-token-md p-3.5 space-y-3 bg-surface-subtle/40">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-primary">Father's Information (Write N/A if not applicable)</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="sis_father_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Father's Full Name <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_father_name" name="sis[father_name]" type="text" x-model="sis.father_name" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_father_age" class="block text-xs font-semibold text-foreground mb-1 label-required">Age <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_father_age" name="sis[father_age]" type="text" x-model="sis.father_age" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_father_contact" class="block text-xs font-semibold text-foreground mb-1 label-required">Contact Number <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_father_contact" name="sis[father_contact]" type="text" x-model="sis.father_contact" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_father_education" class="block text-xs font-semibold text-foreground mb-1 label-required">Highest Educational Attainment <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_father_education" name="sis[father_education]" type="text" x-model="sis.father_education" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_father_occupation" class="block text-xs font-semibold text-foreground mb-1 label-required">Occupation <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_father_occupation" name="sis[father_occupation]" type="text" x-model="sis.father_occupation" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sis_father_email" class="block text-xs font-semibold text-foreground mb-1">Email Address (Optional)</label>
                            <input id="sis_father_email" name="sis[father_email]" type="email" x-model="sis.father_email" class="input-field w-full min-h-[42px] text-sm">
                        </div>
                    </div>
                </div>

                <!-- Mother -->
                <div class="border border-border rounded-token-md p-3.5 space-y-3 bg-surface-subtle/40">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-primary">Mother's Information (Write N/A if not applicable)</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="sis_mother_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Mother's Full Name <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_mother_name" name="sis[mother_name]" type="text" x-model="sis.mother_name" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_mother_age" class="block text-xs font-semibold text-foreground mb-1 label-required">Age <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_mother_age" name="sis[mother_age]" type="text" x-model="sis.mother_age" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_mother_contact" class="block text-xs font-semibold text-foreground mb-1 label-required">Contact Number <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_mother_contact" name="sis[mother_contact]" type="text" x-model="sis.mother_contact" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_mother_education" class="block text-xs font-semibold text-foreground mb-1 label-required">Highest Educational Attainment <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_mother_education" name="sis[mother_education]" type="text" x-model="sis.mother_education" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div>
                            <label for="sis_mother_occupation" class="block text-xs font-semibold text-foreground mb-1 label-required">Occupation <span class="text-destructive" aria-hidden="true">*</span></label>
                            <input id="sis_mother_occupation" name="sis[mother_occupation]" type="text" x-model="sis.mother_occupation" required class="input-field w-full min-h-[42px] text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sis_mother_email" class="block text-xs font-semibold text-foreground mb-1">Email Address (Optional)</label>
                            <input id="sis_mother_email" name="sis[mother_email]" type="email" x-model="sis.mother_email" class="input-field w-full min-h-[42px] text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact & Siblings -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="sis_emergency_contact_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Emergency Contact Person <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_emergency_contact_name" name="sis[emergency_contact_name]" type="text" x-model="sis.emergency_contact_name" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_emergency_relationship" class="block text-xs font-semibold text-foreground mb-1 label-required">Relationship <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_emergency_relationship" name="sis[emergency_relationship]" x-model="sis.emergency_relationship" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select relationship</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::EMERGENCY_RELATIONSHIPS as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sis_emergency_contact_number" class="block text-xs font-semibold text-foreground mb-1 label-required">Emergency Contact Number <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_emergency_contact_number" name="sis[emergency_contact_number]" type="text" x-model="sis.emergency_contact_number" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_emergency_email" class="block text-xs font-semibold text-foreground mb-1 label-required">Emergency Contact Email <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_emergency_email" name="sis[emergency_email]" type="email" x-model="sis.emergency_email" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label for="sis_emergency_address" class="block text-xs font-semibold text-foreground mb-1">Emergency Contact Address (Optional)</label>
                    <input id="sis_emergency_address" name="sis[emergency_address]" type="text" x-model="sis.emergency_address" class="input-field w-full min-h-[44px] text-sm">
                </div>

                <div>
                    <label for="sis_siblings_names" class="block text-xs font-semibold text-foreground mb-1 label-required">Name of Sibling/s (N/A if none) <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_siblings_names" name="sis[siblings_names]" rows="2" x-model="sis.siblings_names" required class="input-field w-full text-sm"></textarea>
                </div>
                <div>
                    <label for="sis_siblings_ages" class="block text-xs font-semibold text-foreground mb-1 label-required">Age of Sibling/s (N/A if none) <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_siblings_ages" name="sis[siblings_ages]" rows="2" x-model="sis.siblings_ages" required class="input-field w-full text-sm"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="sis_siblings_education_occupation" class="block text-xs font-semibold text-foreground mb-1 label-required">Educational Attainment / Occupation of Sibling/s (N/A if none) <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_siblings_education_occupation" name="sis[siblings_education_occupation]" rows="2" x-model="sis.siblings_education_occupation" required class="input-field w-full text-sm"></textarea>
                </div>
            </div>
        </div>

        <!-- ==================== PART 4: INTERESTS, PERSONALITY & GUIDANCE SUPPORT ==================== -->
        <div x-show="sisPart === 4" x-cloak data-sis-part="4" class="space-y-4">
            <h3 class="text-sm font-bold text-foreground border-b border-border pb-1.5">Interests, Personality Traits &amp; Guidance Support</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="sis_interests_hobbies_skills" class="block text-xs font-semibold text-foreground mb-1 label-required">Interests / Hobbies / Talents / Skills <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_interests_hobbies_skills" name="sis[interests_hobbies_skills]" rows="2" x-model="sis.interests_hobbies_skills" required class="input-field w-full text-sm"></textarea>
                </div>
                <div>
                    <label for="sis_awards_recognitions" class="block text-xs font-semibold text-foreground mb-1">Awards or Recognitions Received (Optional)</label>
                    <textarea id="sis_awards_recognitions" name="sis[awards_recognitions]" rows="2" x-model="sis.awards_recognitions" class="input-field w-full text-sm"></textarea>
                </div>

                <div>
                    <label for="sis_cct_org_member" class="block text-xs font-semibold text-foreground mb-1 label-required">Member of a student organization at CCT? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <select id="sis_cct_org_member" name="sis[cct_org_member]" x-model="sis.cct_org_member" @change="if (sis.cct_org_member === 'No') { sis.cct_org_name = 'N/A'; } else if (sis.cct_org_name === 'N/A') { sis.cct_org_name = ''; }" required class="input-field w-full min-h-[44px] text-sm">
                        <option value="">Select</option>
                        @foreach(\App\Support\StudentInformationSheetSchema::ORG_MEMBERSHIP as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="sis.cct_org_member === 'Yes' || sis.cct_org_member === 'Before'" x-cloak>
                    <label for="sis_cct_org_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Name of Student Organization <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_cct_org_name" name="sis[cct_org_name]" type="text" x-model="sis.cct_org_name" required class="input-field w-full min-h-[44px] text-sm">
                </div>

                <div>
                    <label for="sis_strengths_traits" class="block text-xs font-semibold text-foreground mb-1 label-required">Strengths and Positive Traits <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_strengths_traits" name="sis[strengths_traits]" rows="2" x-model="sis.strengths_traits" required class="input-field w-full text-sm"></textarea>
                </div>
                <div>
                    <label for="sis_traits_to_change" class="block text-xs font-semibold text-foreground mb-1 label-required">Traits you want to change <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_traits_to_change" name="sis[traits_to_change]" rows="2" x-model="sis.traits_to_change" required class="input-field w-full text-sm"></textarea>
                </div>
                <div>
                    <label for="sis_personal_goals" class="block text-xs font-semibold text-foreground mb-1 label-required">Personal Goals <span class="text-destructive" aria-hidden="true">*</span></label>
                    <textarea id="sis_personal_goals" name="sis[personal_goals]" rows="2" x-model="sis.personal_goals" required class="input-field w-full text-sm"></textarea>
                </div>
                <div>
                    <label for="sis_happiness_source" class="block text-xs font-semibold text-foreground mb-1 label-required">What gives you happiness? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_happiness_source" name="sis[happiness_source]" type="text" x-model="sis.happiness_source" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_worries_difficulties" class="block text-xs font-semibold text-foreground mb-1 label-required">What are your worries / problems / difficulties? <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_worries_difficulties" name="sis[worries_difficulties]" type="text" x-model="sis.worries_difficulties" required class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_college_success_plan" class="block text-xs font-semibold text-foreground mb-1">How do you intend to make college successful &amp; exciting? (Optional)</label>
                    <input id="sis_college_success_plan" name="sis[college_success_plan]" type="text" x-model="sis.college_success_plan" class="input-field w-full min-h-[44px] text-sm">
                </div>
                <div>
                    <label for="sis_close_friend_name" class="block text-xs font-semibold text-foreground mb-1 label-required">Name of a close friend in City College of Tagaytay <span class="text-destructive" aria-hidden="true">*</span></label>
                    <input id="sis_close_friend_name" name="sis[close_friend_name]" type="text" x-model="sis.close_friend_name" required class="input-field w-full min-h-[44px] text-sm" placeholder="Write N/A if none">
                </div>
                <div>
                    <label for="sis_residence_google_maps_link" class="block text-xs font-semibold text-foreground mb-1">Google Maps Link to Current Residence (Optional)</label>
                    <input id="sis_residence_google_maps_link" name="sis[residence_google_maps_link]" type="url" x-model="sis.residence_google_maps_link" class="input-field w-full min-h-[44px] text-sm" placeholder="https://maps.google.com/...">
                </div>
            </div>

            <fieldset class="border border-border rounded-token-md p-3.5 bg-surface-subtle/50">
                <legend class="px-1.5 text-xs font-semibold text-foreground">
                    Any difficulties / concerns where you might need assistance from Guidance and Counseling Unit? (Select all that apply, optional)
                </legend>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                    @foreach(\App\Support\StudentInformationSheetSchema::GUIDANCE_CONCERNS as $concern)
                        <label class="min-h-[44px] flex items-center gap-2.5 px-3 py-2 rounded-token-md border border-border bg-surface hover:bg-surface-subtle cursor-pointer text-xs sm:text-sm">
                            <input type="checkbox"
                                   name="sis[guidance_concerns][]"
                                   value="{{ $concern }}"
                                   :checked="(sis.guidance_concerns || []).includes(@js($concern))"
                                   @change="toggleSisCheckbox('guidance_concerns', @js($concern), null)"
                                   class="w-4 h-4 text-primary border-border-strong rounded focus:ring-primary shrink-0">
                            <span class="leading-snug">{{ $concern }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="sis_guidance_concerns_explanation" class="block text-xs font-semibold text-foreground mb-1 label-required">Please explain further the concern/s (Write N/A if none) <span class="text-destructive" aria-hidden="true">*</span></label>
                <input id="sis_guidance_concerns_explanation" name="sis[guidance_concerns_explanation]" type="text" x-model="sis.guidance_concerns_explanation" required class="input-field w-full min-h-[44px] text-sm" placeholder="Write N/A if none">
            </div>
        </div>
    </div>

    <!-- Bottom Action Bar -->
    <div class="px-4 sm:px-6 py-2.5 border-t border-border bg-surface shrink-0 flex items-center justify-between gap-2.5">
        <button type="button"
                x-show="sisPart > 1"
                x-cloak
                @click="prevSisPart()"
                :disabled="isSubmitting"
                class="btn-secondary min-h-[44px] px-4 sm:px-5 text-xs sm:text-sm cursor-pointer">
            &larr; Previous Part
        </button>
        <span x-show="sisPart === 1" class="text-xs text-muted-foreground hidden sm:inline">Required before inventory questions.</span>

        <div class="flex items-center gap-2 ml-auto">
            <button type="button"
                    x-show="sisPart < 4"
                    @click="nextSisPart()"
                    class="btn-primary min-h-[44px] px-5 sm:px-6 text-xs sm:text-sm cursor-pointer">
                Next Part &rarr;
            </button>
            <button type="button"
                    x-show="sisPart === 4"
                    x-cloak
                    @click="saveStudentInfoSheet()"
                    :disabled="isSubmitting"
                    class="btn-primary min-h-[44px] px-5 sm:px-6 text-xs sm:text-sm disabled:opacity-50 cursor-pointer">
                <span x-show="!isSubmitting">Save Sheet &amp; Continue &rarr;</span>
                <span x-show="isSubmitting" x-cloak>Saving&hellip;</span>
            </button>
        </div>
    </div>
</div>
