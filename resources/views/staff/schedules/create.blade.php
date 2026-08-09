<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Create Assessment Schedule') }}
            </h2>
            <a href="{{ route('schedules.index') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back to Schedules</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg bg-white">
                    <form method="POST" action="{{ route('schedules.store') }}">
                        @csrf
                        
                        <!-- Academic Year -->
                        <div class="mb-6">
                            <label for="academic_year_id" class="block text-sm font-medium text-foreground/80">Academic Year</label>
                            <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                <option value="" disabled selected>Select an Academic Year</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                        {{ $year->label }} {{ $year->is_current ? '(Current)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Year Level -->
                            <div>
                                <label for="year_level" class="block text-sm font-medium text-foreground/80">Year Level</label>
                                <select id="year_level" name="year_level" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                    <option value="" disabled selected>Select Year Level</option>
                                    @foreach(['1st', '2nd', '3rd', '4th'] as $level)
                                        <option value="{{ $level }}" {{ old('year_level') == $level ? 'selected' : '' }}>{{ $level }} Year</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
                            </div>

                            <!-- Program -->
                            <div>
                                <label for="program" class="block text-sm font-medium text-foreground/80">Program (Optional)</label>
                                <input type="text" id="program" name="program" value="{{ old('program') }}" placeholder="e.g. BSCS, BSIT, leave blank for all" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors">
                                <span class="text-xs text-foreground/50 mt-1 block">Leave blank to apply to all programs in the selected year level.</span>
                                <x-input-error :messages="$errors->get('program')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6 pt-6 border-t border-border">
                            <!-- Open Date/Time -->
                            <div class="bg-muted/30 p-5 rounded-2xl border border-border">
                                <h4 class="font-heading font-semibold text-foreground mb-4">Opens At</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label for="open_date" class="block text-sm font-medium text-foreground/80">Open Date</label>
                                        <input type="date" id="open_date" name="open_date" value="{{ old('open_date') }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        <x-input-error :messages="$errors->get('open_date')" class="mt-2" />
                                    </div>
                                    <div>
                                        <label for="open_time" class="block text-sm font-medium text-foreground/80">Open Time</label>
                                        <input type="time" id="open_time" name="open_time" value="{{ old('open_time') }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        <x-input-error :messages="$errors->get('open_time')" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Close Date/Time -->
                            <div class="bg-muted/30 p-5 rounded-2xl border border-border">
                                <h4 class="font-heading font-semibold text-foreground mb-4">Closes At</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label for="close_date" class="block text-sm font-medium text-foreground/80">Close Date</label>
                                        <input type="date" id="close_date" name="close_date" value="{{ old('close_date') }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        <x-input-error :messages="$errors->get('close_date')" class="mt-2" />
                                    </div>
                                    <div>
                                        <label for="close_time" class="block text-sm font-medium text-foreground/80">Close Time</label>
                                        <input type="time" id="close_time" name="close_time" value="{{ old('close_time') }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        <x-input-error :messages="$errors->get('close_time')" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="submit" class="btn-primary">
                                {{ __('Create Schedule') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
