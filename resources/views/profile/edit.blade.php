@section('title', 'Profile')
@php
    $hasProfileErrors = $errors->hasAny(['contact_number', 'address_line1', 'city', 'province', 'profile_picture']);
    $initialTab = $hasProfileErrors
        ? 'contact'
        : (($errors->updatePassword->any() || session('status') === 'password-updated') ? 'password' : 'contact');
@endphp

<x-app-layout>
    <div x-data="{
        activeTab: '{{ $initialTab }}',
        avatarPreview: '{{ $user->avatar_url }}',
        fileSelected: false,
        handleFile(e) {
            if (e.target.files && e.target.files[0]) {
                this.fileSelected = true;
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.avatarPreview = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        }
    }" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10 space-y-6">

        {{-- CARD 1: Identity & Official Records --}}
        <div class="bg-white rounded-2xl border border-border p-5 sm:p-6 shadow-sm">
            {{-- Avatar & Identity Row --}}
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="relative shrink-0">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden border-2 border-border shadow-sm bg-muted/40">
                        <img :src="avatarPreview" alt="Profile Picture" class="w-full h-full object-cover">
                    </div>
                    <label for="profile_picture" title="Change photo" class="absolute bottom-0 right-0 w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-primary text-white flex items-center justify-center shadow-md cursor-pointer hover:bg-primary/90 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 sm:w-4 sm:h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                        </svg>
                    </label>
                    <input id="profile_picture" name="profile_picture" type="file" form="profile-form" accept="image/jpeg,image/png,image/jpg" class="sr-only" @change="handleFile($event)">
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1">
                        <h2 class="font-heading text-lg sm:text-xl font-bold text-primary leading-tight truncate">
                            {{ $user->first_name }} {{ $user->middle_initial ? $user->middle_initial . '.' : '' }} {{ $user->last_name }}
                        </h2>
                        <p class="text-xs text-foreground/50 shrink-0">JPG or PNG, max 2MB. Cropped to a square.</p>
                    </div>
                    <p class="text-xs sm:text-sm text-foreground/60 truncate mt-0.5" title="{{ $user->email }}">
                        {{ $user->email }}
                    </p>
                    <div x-show="fileSelected" x-cloak class="mt-2">
                        <button type="submit" form="profile-form" class="btn-primary text-xs py-1 px-3 shadow-sm">
                            Save photo
                        </button>
                    </div>
                    <x-input-error class="mt-1 text-xs" :messages="$errors->get('profile_picture')" />
                </div>
            </div>

            {{-- Divider --}}
            <div class="border-t border-border/60 my-4 sm:my-5"></div>

            {{-- Official Records Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground/70">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-foreground/50 shrink-0">
                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
                    </svg>
                    <span>Official Records</span>
                </div>
                <p class="text-xs text-foreground/50">To update these, contact the Guidance Office.</p>
            </div>

            {{-- Official Records Grid: 2-col on mobile, 3-col on sm+ --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3">
                <div>
                    <span class="text-foreground/50 text-xs block">Student ID</span>
                    <span class="text-sm font-semibold text-foreground truncate block">{{ $user->student_id ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-foreground/50 text-xs block">Program</span>
                    <span class="text-sm font-semibold text-foreground truncate block">{{ $user->structuredProgram ? $user->structuredProgram->code : ($user->program ?? 'N/A') }}</span>
                </div>
                <div>
                    <span class="text-foreground/50 text-xs block">Year level</span>
                    <span class="text-sm font-semibold text-foreground truncate block">{{ $user->year_level ? ($user->year_level . (is_numeric($user->year_level) ? ' Year' : '')) : 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-foreground/50 text-xs block">Section</span>
                    <span class="text-sm font-semibold text-foreground truncate block">{{ $user->section ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-foreground/50 text-xs block">Date of birth</span>
                    <span class="text-sm font-semibold text-foreground truncate block">{{ $user->birthdate ? $user->birthdate->format('F d, Y') : 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-foreground/50 text-xs block">Department</span>
                    <span class="text-sm font-semibold text-foreground truncate block">{{ $user->structuredProgram?->department?->code ?? ($user->department ?? 'N/A') }}</span>
                </div>
            </div>
        </div>

        {{-- CARD 2: Profile & Settings --}}
        <div class="bg-white rounded-2xl border border-border p-5 sm:p-6 shadow-sm">
            {{-- Header + Tabs --}}
            <h1 class="font-heading text-xl sm:text-[22px] font-bold text-primary leading-tight">
                Profile & settings
            </h1>

            <div class="flex items-center gap-6 border-b border-border/60 mt-3">
                <button type="button" @click="activeTab = 'contact'"
                    class="h-10 px-1 text-sm font-semibold transition-colors relative border-b-2 flex items-center profile-tab-fade"
                    :class="activeTab === 'contact' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground'">
                    Contact details
                </button>
                <button type="button" @click="activeTab = 'password'"
                    class="h-10 px-1 text-sm font-semibold transition-colors relative border-b-2 flex items-center profile-tab-fade"
                    :class="activeTab === 'password' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground'">
                    Password
                </button>
            </div>

            {{-- Tab Content Area --}}
            <div class="pt-5">
                {{-- Tab 1: Contact Details Form --}}
                <div x-show="activeTab === 'contact'"
                     x-transition:enter="transition ease-out duration-150 profile-tab-fade"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">

                    <form id="profile-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Contact Number --}}
                            <div class="col-span-1 sm:col-span-2">
                                <label for="contact_number" class="block text-sm font-semibold text-foreground mb-1">Contact number</label>
                                <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="input-field profile-input-height w-full" required>
                                <x-input-error class="mt-1" :messages="$errors->get('contact_number')" />
                            </div>

                            {{-- Address Line 1 --}}
                            <div class="col-span-1 sm:col-span-2">
                                <label for="address_line1" class="block text-sm font-semibold text-foreground mb-1">Address line 1</label>
                                <input type="text" name="address_line1" id="address_line1" value="{{ old('address_line1', $user->address_line1) }}" class="input-field profile-input-height w-full" required>
                                <x-input-error class="mt-1" :messages="$errors->get('address_line1')" />
                            </div>

                            {{-- City --}}
                            <div class="col-span-1">
                                <label for="city" class="block text-sm font-semibold text-foreground mb-1">City</label>
                                <input type="text" name="city" id="city" value="{{ old('city', $user->city) }}" class="input-field profile-input-height w-full" required>
                                <x-input-error class="mt-1" :messages="$errors->get('city')" />
                            </div>

                            {{-- Province --}}
                            <div class="col-span-1">
                                <label for="province" class="block text-sm font-semibold text-foreground mb-1">Province</label>
                                <input type="text" name="province" id="province" value="{{ old('province', $user->province) }}" class="input-field profile-input-height w-full" required>
                                <x-input-error class="mt-1" :messages="$errors->get('province')" />
                            </div>
                        </div>

                        {{-- Footer: Update Profile Button & Status --}}
                        <div class="flex items-center justify-end gap-3 mt-6">
                            @if (session('status') === 'profile-updated')
                                <p x-data="{ show: true }" x-show="show" x-transition.opacity.duration.150ms x-init="setTimeout(() => show = false, 3000)" class="text-sm text-primary font-semibold flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-primary">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                    Saved successfully
                                </p>
                            @endif
                            <button type="submit" class="btn-primary text-sm px-6 py-2.5 h-11 min-h-[44px]">Update profile</button>
                        </div>
                    </form>
                </div>

                {{-- Tab 2: Password Form --}}
                <div x-show="activeTab === 'password'"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150 profile-tab-fade"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">

                    <form method="post" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        @php
                            $passwordFields = [
                                [
                                    'id' => 'update_password_current_password',
                                    'name' => 'current_password',
                                    'label' => 'Current password',
                                    'autocomplete' => 'current-password',
                                    'error_key' => 'current_password',
                                    'col_span' => 'col-span-1 sm:col-span-2',
                                ],
                                [
                                    'id' => 'update_password_password',
                                    'name' => 'password',
                                    'label' => 'New password',
                                    'autocomplete' => 'new-password',
                                    'error_key' => 'password',
                                    'col_span' => 'col-span-1',
                                ],
                                [
                                    'id' => 'update_password_password_confirmation',
                                    'name' => 'password_confirmation',
                                    'label' => 'Confirm new password',
                                    'autocomplete' => 'new-password',
                                    'error_key' => 'password_confirmation',
                                    'col_span' => 'col-span-1',
                                ],
                            ];
                        @endphp

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($passwordFields as $field)
                                <div class="{{ $field['col_span'] }}" x-data="{ show: false }">
                                    <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-foreground mb-1">{{ $field['label'] }}</label>
                                    <div class="relative">
                                        <input :type="show ? 'text' : 'password'"
                                               name="{{ $field['name'] }}"
                                               id="{{ $field['id'] }}"
                                               class="input-field profile-input-height w-full pr-10"
                                               autocomplete="{{ $field['autocomplete'] }}"
                                               required>
                                        <button type="button"
                                                @click="show = !show"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-foreground/40 hover:text-foreground transition-colors p-1"
                                                :aria-label="show ? 'Hide password' : 'Show password'">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.775M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                    <x-input-error class="mt-1" :messages="$errors->updatePassword->get($field['error_key'])" />
                                </div>
                            @endforeach
                        </div>

                        {{-- Footer: Update Password Button & Status --}}
                        <div class="flex items-center justify-end gap-3 mt-6">
                            @if (session('status') === 'password-updated')
                                <p x-data="{ show: true }" x-show="show" x-transition.opacity.duration.150ms x-init="setTimeout(() => show = false, 3000)" class="text-sm text-primary font-semibold flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-primary">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                    Password updated
                                </p>
                            @endif
                            <button type="submit" class="btn-primary text-sm px-6 py-2.5 h-11 min-h-[44px]">Update password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
