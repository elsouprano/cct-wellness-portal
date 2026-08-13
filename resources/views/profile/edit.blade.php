<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Profile & Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-3xl border border-border p-8 sm:p-12">
                
                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('patch')

                    <!-- Section: Personal Information -->
                    <div class="border-b border-border pb-3 mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <h3 class="font-heading text-lg font-bold text-primary">Personal Information</h3>
                    </div>

                    <div class="flex items-center gap-6 mb-8">
                        <div class="relative group shrink-0" style="width: 96px; height: 96px;">
                            <div class="w-full h-full rounded-full overflow-hidden border-2 border-border shadow-sm">
                                <img id="avatar-preview" src="{{ $user->avatar_url }}" alt="Profile Picture" class="w-full h-full object-cover">
                            </div>
                            <label for="profile_picture" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                </svg>
                            </label>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-foreground mb-1">Change Profile Picture</label>
                            <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/jpg" class="mt-1 block w-full text-sm text-foreground/70 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer" onchange="previewImage(this)" />
                            <p class="text-xs text-foreground/50 mt-1">JPG or PNG. Max 2MB. Image will be cropped to a square.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('profile_picture')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-semibold text-foreground mb-1">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                        </div>

                        <!-- Middle Initial -->
                        <div>
                            <label for="middle_initial" class="block text-sm font-semibold text-foreground mb-1">Middle Initial <span class="text-foreground/50 font-normal">(Optional)</span></label>
                            <input type="text" name="middle_initial" id="middle_initial" value="{{ old('middle_initial', $user->middle_initial) }}" class="input-field w-full">
                            <x-input-error class="mt-2" :messages="$errors->get('middle_initial')" />
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-semibold text-foreground mb-1">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                        </div>

                        <!-- Birthdate -->
                        <div>
                            <label for="birthdate" class="block text-sm font-semibold text-foreground mb-1">Date of Birth</label>
                            <input type="date" name="birthdate" id="birthdate" value="{{ old('birthdate', $user->birthdate ? $user->birthdate->format('Y-m-d') : '') }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('birthdate')" />
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="contact_number" class="block text-sm font-semibold text-foreground mb-1">Contact Number</label>
                            <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('contact_number')" />
                        </div>

                        <!-- Address Line 1 -->
                        <div class="md:col-span-2">
                            <label for="address_line1" class="block text-sm font-semibold text-foreground mb-1">Address Line 1</label>
                            <input type="text" name="address_line1" id="address_line1" value="{{ old('address_line1', $user->address_line1) }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('address_line1')" />
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city" class="block text-sm font-semibold text-foreground mb-1">City</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $user->city) }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('city')" />
                        </div>

                        <!-- Province -->
                        <div>
                            <label for="province" class="block text-sm font-semibold text-foreground mb-1">Province</label>
                            <input type="text" name="province" id="province" value="{{ old('province', $user->province) }}" class="input-field w-full" required>
                            <x-input-error class="mt-2" :messages="$errors->get('province')" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mb-12">
                        @if (session('status') === 'profile-updated')
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-primary font-semibold flex items-center">
                                Saved Successfully
                            </p>
                        @endif
                        <button type="submit" class="btn-primary">Update Profile</button>
                    </div>
                </form>

                <!-- Section: Academic Information (Read-only) -->
                <div class="border-b border-border pb-3 mb-6 mt-12 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                    <h3 class="font-heading text-lg font-bold text-primary">Academic Information</h3>
                </div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-12">
                    <p class="text-sm text-foreground/70 mb-5">
                        <span class="font-semibold">Note:</span> Academic records cannot be updated manually. Please contact the Guidance Office to update this information.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Student ID -->
                        <div>
                            <label class="block text-sm font-semibold text-foreground mb-1">Student ID</label>
                            <input type="text" value="{{ $user->student_id }}" class="input-field w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                        </div>
                        
                        <!-- Program -->
                        <div>
                            <label class="block text-sm font-semibold text-foreground mb-1">Program</label>
                            <input type="text" value="{{ $user->structuredProgram ? $user->structuredProgram->code : ($user->program ?? 'N/A') }}" class="input-field w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                        </div>
                        
                        <!-- Section -->
                        <div>
                            <label class="block text-sm font-semibold text-foreground mb-1">Section</label>
                            <input type="text" value="{{ $user->section }}" class="input-field w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                        </div>
                        
                        <!-- Year Level -->
                        <div>
                            <label class="block text-sm font-semibold text-foreground mb-1">Year Level</label>
                            <input type="text" value="{{ $user->year_level }}" class="input-field w-full bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                        </div>
                    </div>
                </div>

                <!-- Section: Account Security -->
                <div class="border-b border-border pb-3 mb-6 mt-12 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <h3 class="font-heading text-lg font-bold text-primary">Account Security</h3>
                </div>

                <!-- Institutional Email (Read Only) -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-foreground mb-1">Institutional Email</label>
                    <input type="text" value="{{ $user->email }}" class="input-field w-full md:w-1/2 bg-gray-100 text-gray-500 cursor-not-allowed" disabled>
                    <p class="text-xs text-foreground/50 mt-1">Institutional emails cannot be changed.</p>
                </div>

                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                        <div class="md:col-span-2">
                            <label for="update_password_current_password" class="block text-sm font-semibold text-foreground mb-1">Current Password</label>
                            <input type="password" name="current_password" id="update_password_current_password" class="input-field w-full md:w-1/2" autocomplete="current-password" required>
                            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('current_password')" />
                        </div>

                        <div>
                            <label for="update_password_password" class="block text-sm font-semibold text-foreground mb-1">New Password</label>
                            <input type="password" name="password" id="update_password_password" class="input-field w-full" autocomplete="new-password" required>
                            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password')" />
                        </div>

                        <div>
                            <label for="update_password_password_confirmation" class="block text-sm font-semibold text-foreground mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="update_password_password_confirmation" class="input-field w-full" autocomplete="new-password" required>
                            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password_confirmation')" />
                        </div>
                    </div>

                    <div class="flex justify-start items-center gap-4">
                        <button type="submit" class="btn-primary">Update Password</button>
                        @if (session('status') === 'password-updated')
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-primary font-semibold">
                                Password Updated
                            </p>
                        @endif
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <script nonce="{{ $cspNonce }}">
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</x-app-layout>
