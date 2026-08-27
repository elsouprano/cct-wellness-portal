<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CCT Wellness Portal') }} - Register</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .register-layout { 
            min-height: 100vh; 
            padding: 2.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background-image: url('{{ asset('images/bg.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        /* Deep Overlay to ensure card readability */
        .register-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(to bottom right, rgba(44, 26, 26, 0.7), rgba(139, 16, 20, 0.4));
            z-index: 0;
            pointer-events: none;
        }
        
        .register-card { 
            width: 100%; 
            max-width: 44rem; /* Wider than login to accommodate multi-column grid comfortably */
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2.5rem 3rem; 
            border-radius: 1.5rem; 
            border: 1px solid rgba(255, 255, 255, 0.8); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.3) inset;
            position: relative;
            z-index: 10;
        }

        .logo-badge { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 1.5rem; 
            width: 4.5rem;
            height: 4.5rem;
            background-color: var(--color-muted, #f8f9fa); 
            border-radius: 50%;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 2px solid #ffffff;
        }
        
        .logo-img { 
            height: 3rem; 
            width: auto; 
            mix-blend-mode: multiply; 
        }

        /* Section Styling */
        .section-header {
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }
        .section-header:first-of-type {
            margin-top: 2rem;
        }
        .section-header h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--color-primary);
            letter-spacing: -0.01em;
            margin: 0;
        }

        .text-muted { color: rgba(44, 26, 26, 0.65); }
        .text-foreground { color: rgba(44, 26, 26, 1); }

        /* Robust Inputs */
        .custom-input { 
            width: 100%; 
            border: 1px solid rgba(44, 26, 26, 0.2); 
            border-radius: 0.5rem; 
            padding: 0.625rem 0.875rem; 
            background-color: #ffffff; 
            transition: all 0.2s ease; 
            outline: none;
            color: var(--color-foreground, #0f172a);
            font-size: 0.95rem;
        }
        .custom-input:focus { 
            border-color: var(--color-primary); 
            box-shadow: 0 0 0 3px rgba(139, 16, 20, 0.15); 
        }
        .custom-input::placeholder {
            color: rgba(44, 26, 26, 0.4);
        }
        
        .custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Responsive Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .register-card {
                padding: 3rem 4rem;
            }
        }
        
        .input-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
            color: var(--color-foreground);
        }
        
        .btn-submit {
            width: 100%; 
            padding: 0.875rem; 
            display: flex; 
            justify-content: center; 
            background-color: var(--color-primary);
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(139, 16, 20, 0.2); 
            font-weight: 600; 
            font-size: 1rem; 
            border-radius: 0.5rem; 
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(139, 16, 20, 0.3);
        }
    </style>
</head>
<body class="font-sans antialiased selection:bg-primary/20">

    <div class="register-layout">
        <div class="register-overlay"></div>
        <div class="register-card">
            
            <!-- Logo Badge -->
            <div class="logo-badge">
                <a href="/">
                    <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" class="logo-img" />
                </a>
            </div>
            
            <div style="text-align: center;">
                <h2 class="font-heading text-primary" style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; letter-spacing: -0.025em;">Student Registration</h2>
                <p class="text-muted" style="font-size: 0.95rem;">Create your CCT Wellness Portal account</p>
            </div>

            <form method="POST" action="{{ route('register') }}" style="margin-top: 1rem;" 
                  x-data="{ departments: {{ isset($departments) ? $departments->toJson() : '[]' }}, selectedDepartment: '{{ old('department') ?? '' }}', selectedProgram: '{{ old('program_id') ?? '' }}' }">
                @csrf

                <!-- Section: Personal Information -->
                <div class="section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; color: var(--color-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <h3>Personal Information</h3>
                </div>
                
                <div class="form-grid">
                    <div class="input-group">
                        <label for="first_name">{{ __('First Name') }}</label>
                        <input id="first_name" class="custom-input" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus placeholder="e.g. Juan" />
                        <x-input-error :messages="$errors->get('first_name')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="last_name">{{ __('Last Name') }}</label>
                        <input id="last_name" class="custom-input" type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="e.g. Dela Cruz" />
                        <x-input-error :messages="$errors->get('last_name')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="middle_initial">{{ __('Middle Initial') }} <span style="font-weight: 400; color: rgba(44, 26, 26, 0.5);">(Optional)</span></label>
                        <input id="middle_initial" class="custom-input" type="text" name="middle_initial" value="{{ old('middle_initial') }}" placeholder="e.g. M" maxlength="2" />
                        <x-input-error :messages="$errors->get('middle_initial')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="birthdate">{{ __('Birthdate') }}</label>
                        <input id="birthdate" class="custom-input" type="date" name="birthdate" value="{{ old('birthdate') }}" required />
                        <x-input-error :messages="$errors->get('birthdate')" style="margin-top: 0.25rem;" />
                    </div>
                </div>

                <!-- Section: Academic Information -->
                <div class="section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; color: var(--color-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                    <h3>Academic Information</h3>
                </div>

                <div class="form-grid">
                    <div class="input-group">
                        <label for="student_id">{{ __('Student ID') }}</label>
                        <input id="student_id" class="custom-input" type="text" name="student_id" value="{{ old('student_id') }}" required placeholder="e.g. 2023010305" />
                        <x-input-error :messages="$errors->get('student_id')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="year_level">{{ __('Year Level') }}</label>
                        <select id="year_level" name="year_level" class="custom-input custom-select" required>
                            <option value="" disabled {{ old('year_level') ? '' : 'selected' }}>Select Year Level</option>
                            <option value="1st" {{ old('year_level') == '1st' ? 'selected' : '' }}>1st Year</option>
                            <option value="2nd" {{ old('year_level') == '2nd' ? 'selected' : '' }}>2nd Year</option>
                            <option value="3rd" {{ old('year_level') == '3rd' ? 'selected' : '' }}>3rd Year</option>
                            <option value="4th" {{ old('year_level') == '4th' ? 'selected' : '' }}>4th Year</option>
                        </select>
                        <x-input-error :messages="$errors->get('year_level')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="department">{{ __('Department') }}</label>
                        <select id="department" class="custom-input custom-select" name="department" x-model="selectedDepartment" @change="selectedProgram = ''" required>
                            <option value="" disabled>Select Department</option>
                            <template x-for="dept in departments" :key="dept.id">
                                <option :value="dept.id" x-text="dept.name"></option>
                            </template>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="program_id">{{ __('Program') }}</label>
                        <select id="program_id" class="custom-input custom-select" name="program_id" x-model="selectedProgram" required :disabled="!selectedDepartment">
                            <option value="" disabled>Select Program</option>
                            <template x-for="prog in (departments.find(d => d.id == selectedDepartment)?.programs || [])" :key="prog.id">
                                <option :value="prog.id" x-text="prog.name + (prog.code ? ' (' + prog.code + ')' : '')"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('program_id')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="section">{{ __('Section') }}</label>
                        <input id="section" class="custom-input" type="text" name="section" value="{{ old('section') }}" required placeholder="e.g. 1A" />
                        <x-input-error :messages="$errors->get('section')" style="margin-top: 0.25rem;" />
                    </div>
                </div>

                <!-- Section: Contact Information -->
                <div class="section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; color: var(--color-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                    </svg>
                    <h3>Contact Information</h3>
                </div>

                <div class="form-grid">
                    <div class="input-group md:col-span-2">
                        <label for="email">{{ __('Institutional Email') }}</label>
                        <input id="email" class="custom-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@cct.edu.ph" />
                        <x-input-error :messages="$errors->get('email')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group md:col-span-2">
                        <label for="address_line1">{{ __('Address Line 1') }}</label>
                        <input id="address_line1" class="custom-input" type="text" name="address_line1" value="{{ old('address_line1') }}" required placeholder="Street, Barangay" />
                        <x-input-error :messages="$errors->get('address_line1')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="city">{{ __('City') }}</label>
                        <input id="city" class="custom-input" type="text" name="city" value="{{ old('city') }}" required placeholder="e.g. Tagaytay City" />
                        <x-input-error :messages="$errors->get('city')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group">
                        <label for="province">{{ __('Province') }}</label>
                        <input id="province" class="custom-input" type="text" name="province" value="{{ old('province') }}" required placeholder="e.g. Cavite" />
                        <x-input-error :messages="$errors->get('province')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group md:col-span-2">
                        <label for="contact_number">{{ __('Contact Number') }}</label>
                        <input id="contact_number" class="custom-input" type="text" name="contact_number" value="{{ old('contact_number') }}" required placeholder="e.g. 09xxxxxxxxx" />
                        <x-input-error :messages="$errors->get('contact_number')" style="margin-top: 0.25rem;" />
                    </div>
                </div>

                <!-- Section: Account Security -->
                <div class="section-header">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem; color: var(--color-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <h3>Account Security</h3>
                </div>

                <div class="form-grid">
                    <div class="input-group" x-data="{ show: false }">
                    <div class="input-group" x-data="{ show: false }">
                        <label for="password">{{ __('Password') }}</label>
                        <div style="position: relative;">
                            <input id="password" class="custom-input" x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="new-password" placeholder="••••••••" style="padding-right: 2.5rem;" />
                            <button type="button" @click="show = !show" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: rgba(15,23,42,0.4);" aria-label="Toggle Password Visibility">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                                <svg x-show="show" style="display: none; width: 1.25rem; height: 1.25rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" style="margin-top: 0.25rem;" />
                    </div>

                    <div class="input-group" x-data="{ show: false }">
                        <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                        <div style="position: relative;">
                            <input id="password_confirmation" class="custom-input" x-bind:type="show ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" style="padding-right: 2.5rem;" />
                            <button type="button" @click="show = !show" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: rgba(15,23,42,0.4);" aria-label="Toggle Password Visibility">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                                <svg x-show="show" style="display: none; width: 1.25rem; height: 1.25rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" style="margin-top: 0.25rem;" />
                    </div>
                </div>

                <!-- Submit Area -->
                <div style="margin-top: 3.5rem; display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                    <button type="submit" class="btn-submit">
                        {{ __('Complete Registration') }}
                    </button>
                    
                    <p style="font-size: 0.95rem; color: rgba(44, 26, 26, 0.65);">
                        Already registered? 
                        <a href="{{ route('login') }}" style="color: var(--color-primary); font-weight: 700; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Log in instead</a>
                    </p>
                </div>
            </form>

        </div>
    </div>

</body>
</html>
