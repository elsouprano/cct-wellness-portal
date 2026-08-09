<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="font-heading text-2xl font-bold text-primary">Student Registration</h2>
        <p class="text-sm text-foreground/70 mt-2">Join the CCT Wellness Portal</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-[var(--space-md)]">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-[var(--space-md)]">
            <!-- First Name -->
            <div>
                <label for="first_name" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('First Name') }}</label>
                <input id="first_name" class="input-field w-full" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus />
                <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
            </div>

            <!-- Middle Initial -->
            <div>
                <label for="middle_initial" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Middle Initial') }}</label>
                <input id="middle_initial" class="input-field w-full" type="text" name="middle_initial" value="{{ old('middle_initial') }}" />
                <x-input-error :messages="$errors->get('middle_initial')" class="mt-1" />
            </div>
        </div>

        <!-- Last Name -->
        <div>
            <label for="last_name" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Last Name') }}</label>
            <input id="last_name" class="input-field w-full" type="text" name="last_name" value="{{ old('last_name') }}" required />
            <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-[var(--space-md)]">
            <!-- Birthdate -->
            <div>
                <label for="birthdate" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Birthdate') }}</label>
                <input id="birthdate" class="input-field w-full" type="date" name="birthdate" value="{{ old('birthdate') }}" required />
                <x-input-error :messages="$errors->get('birthdate')" class="mt-1" />
            </div>

            <!-- Program -->
            <div>
                <label for="program" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Program') }}</label>
                <input id="program" class="input-field w-full" type="text" name="program" value="{{ old('program') }}" required />
                <x-input-error :messages="$errors->get('program')" class="mt-1" />
            </div>

            <!-- Year Level -->
            <div>
                <label for="year_level" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Year Level') }}</label>
                <select id="year_level" name="year_level" class="input-field w-full" required>
                    <option value="" disabled selected>Select Year Level</option>
                    <option value="1st" {{ old('year_level') == '1st' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd" {{ old('year_level') == '2nd' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd" {{ old('year_level') == '3rd' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th" {{ old('year_level') == '4th' ? 'selected' : '' }}>4th Year</option>
                </select>
                <x-input-error :messages="$errors->get('year_level')" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-[var(--space-md)]">
            <!-- Section -->
            <div>
                <label for="section" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Section') }}</label>
                <input id="section" class="input-field w-full" type="text" name="section" value="{{ old('section') }}" required />
                <x-input-error :messages="$errors->get('section')" class="mt-1" />
            </div>

            <!-- Contact Number -->
            <div>
                <label for="contact_number" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Contact Number') }}</label>
                <input id="contact_number" class="input-field w-full" type="text" name="contact_number" value="{{ old('contact_number') }}" required />
                <x-input-error :messages="$errors->get('contact_number')" class="mt-1" />
            </div>
        </div>

        <!-- Address Line 1 -->
        <div>
            <label for="address_line1" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Address Line 1') }}</label>
            <input id="address_line1" class="input-field w-full" type="text" name="address_line1" value="{{ old('address_line1') }}" required />
            <x-input-error :messages="$errors->get('address_line1')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-[var(--space-md)]">
            <!-- City -->
            <div>
                <label for="city" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('City') }}</label>
                <input id="city" class="input-field w-full" type="text" name="city" value="{{ old('city') }}" required />
                <x-input-error :messages="$errors->get('city')" class="mt-1" />
            </div>

            <!-- Province -->
            <div>
                <label for="province" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Province') }}</label>
                <input id="province" class="input-field w-full" type="text" name="province" value="{{ old('province') }}" required />
                <x-input-error :messages="$errors->get('province')" class="mt-1" />
            </div>
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Email') }}</label>
            <input id="email" class="input-field w-full" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Password') }}</label>
            <input id="password" class="input-field w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-[var(--space-xs)]">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" class="input-field w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pt-[var(--space-md)] gap-4">
            <a class="text-sm text-secondary hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-md" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="btn-primary w-full sm:w-auto">
                {{ __('Register') }}
            </button>
        </div>
    </form>
</x-guest-layout>
