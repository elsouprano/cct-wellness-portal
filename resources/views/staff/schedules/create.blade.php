@section('title', 'Create Assessment Schedule')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Create Assessment Schedule') }}
            </h2>
            <a href="{{ route('schedules.index') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back to Schedules</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-6 sm:p-8 lg:p-10 bg-white">
                    <form method="POST" action="{{ route('schedules.store') }}" novalidate data-confirm="save" data-confirm-title="Confirm Assessment Schedule"
                          x-data="{ 
                              departments: {{ isset($departments) ? $departments->toJson() : '[]' }}, 
                              selectedDepartment: '{{ old('department', auth()->user()->isCounselor() ? auth()->user()->department_id : '') }}', 
                              selectedProgram: '{{ old('program_id') ?? '' }}',
                              openDate: '{{ old('open_date', '') }}',
                              openTime: '{{ old('open_time', '') }}',
                              closeDate: '{{ old('close_date', '') }}',
                              closeTime: '{{ old('close_time', '') }}',
                              appTimezone: '{{ config('app.timezone', 'Asia/Manila') }}',
                              serverNowMs: {{ now()->getTimestampMs() }},
                              clientLoadedMs: Date.now(),
                              today: '{{ now()->format('Y-m-d') }}',
                              currentTime: '{{ now()->format('H:i') }}',
                              hasAttemptedSubmit: false,
                              clientErrors: {
                                  open_date: '',
                                  open_time: '',
                                  close_date: '',
                                  close_time: ''
                              },
                              get minCloseDate() {
                                  const now = this.getNowInAppTimezone();
                                  return (this.openDate && this.openDate > now.date) ? this.openDate : now.date;
                              },
                              getNowInAppTimezone() {
                                  const elapsed = Math.max(0, Date.now() - this.clientLoadedMs);
                                  const currentInstant = new Date(this.serverNowMs + elapsed);
                                  try {
                                      const parts = new Intl.DateTimeFormat('en-CA', {
                                          timeZone: this.appTimezone,
                                          year: 'numeric',
                                          month: '2-digit',
                                          day: '2-digit',
                                          hour: '2-digit',
                                          minute: '2-digit',
                                          hour12: false
                                      }).formatToParts(currentInstant);
                                      const map = {};
                                      for (const p of parts) {
                                          map[p.type] = p.value;
                                      }
                                      const hour = map.hour === '24' ? '00' : map.hour;
                                      const date = `${map.year}-${map.month}-${map.day}`;
                                      const time = `${hour}:${map.minute}`;
                                      return { date, time, dateTime: `${date} ${time}` };
                                  } catch (e) {
                                      return {
                                          date: this.today,
                                          time: this.currentTime,
                                          dateTime: `${this.today} ${this.currentTime}`
                                      };
                                  }
                              },
                              normalizeTime(val) {
                                  if (!val || typeof val !== 'string') return '';
                                  const trimmed = val.trim();
                                  return /^\d{2}:\d{2}:\d{2}$/.test(trimmed) ? trimmed.slice(0, 5) : trimmed;
                              },
                              isValidDateString(val) {
                                  if (!val || typeof val !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(val.trim())) {
                                      return false;
                                  }
                                  const [y, m, d] = val.trim().split('-').map(Number);
                                  if (m < 1 || m > 12 || d < 1 || d > 31) return false;
                                  const dt = new Date(Date.UTC(y, m - 1, d));
                                  return dt.getUTCFullYear() === y && (dt.getUTCMonth() + 1) === m && dt.getUTCDate() === d;
                              },
                              isValidTimeString(val) {
                                  const normalized = this.normalizeTime(val);
                                  if (!/^\d{2}:\d{2}$/.test(normalized)) return false;
                                  const [h, min] = normalized.split(':').map(Number);
                                  return h >= 0 && h <= 23 && min >= 0 && min <= 59;
                              },
                              validateSchedule() {
                                  this.clientErrors = { open_date: '', open_time: '', close_date: '', close_time: '' };
                                  const openDate = (this.openDate || '').trim();
                                  const openTime = this.normalizeTime(this.openTime || '');
                                  const closeDate = (this.closeDate || '').trim();
                                  const closeTime = this.normalizeTime(this.closeTime || '');

                                  const openDateValid = this.isValidDateString(openDate);
                                  const openTimeValid = this.isValidTimeString(openTime);
                                  const closeDateValid = this.isValidDateString(closeDate);
                                  const closeTimeValid = this.isValidTimeString(closeTime);

                                  if (!openDateValid) {
                                      this.clientErrors.open_date = 'Please select a valid date.';
                                  }
                                  if (!openTimeValid) {
                                      this.clientErrors.open_time = 'Please select a valid time.';
                                  }
                                  if (!closeDateValid) {
                                      this.clientErrors.close_date = 'Please select a valid date.';
                                  }
                                  if (!closeTimeValid) {
                                      this.clientErrors.close_time = 'Please select a valid time.';
                                  }

                                  if (!openDateValid || !openTimeValid || !closeDateValid || !closeTimeValid) {
                                      return false;
                                  }

                                  const now = this.getNowInAppTimezone();
                                  const openDateTime = `${openDate} ${openTime}`;
                                  const closeDateTime = `${closeDate} ${closeTime}`;

                                  if (openDateTime < now.dateTime) {
                                      if (openDate < now.date) {
                                          this.clientErrors.open_date = 'The opening date and time cannot be in the past.';
                                      } else {
                                          this.clientErrors.open_time = 'The opening date and time cannot be in the past.';
                                      }
                                  }

                                  if (closeDateTime <= openDateTime) {
                                      if (closeDate < openDate) {
                                          this.clientErrors.close_date = 'The closing date and time must be later than the opening date and time.';
                                      } else {
                                          this.clientErrors.close_time = 'The closing date and time must be later than the opening date and time.';
                                      }
                                  } else if (closeDateTime <= now.dateTime) {
                                      this.clientErrors.close_date = 'The closing date and time must be later than the opening date and time.';
                                  }

                                  return !this.clientErrors.open_date && !this.clientErrors.open_time && !this.clientErrors.close_date && !this.clientErrors.close_time;
                              },
                              onDateTimeInput() {
                                  if (this.hasAttemptedSubmit) {
                                      this.validateSchedule();
                                  }
                              },
                              validateForm(event) {
                                  const form = event.target;
                                  if (form && form.dataset && form.dataset.confirmed === 'true') {
                                      return true;
                                  }
                                  const selectFields = form.querySelectorAll('select[required]');
                                  for (const field of selectFields) {
                                      if (!field.disabled && typeof field.checkValidity === 'function' && !field.checkValidity()) {
                                          if (typeof field.reportValidity === 'function') {
                                              field.reportValidity();
                                          }
                                          return false;
                                      }
                                  }
                                  this.hasAttemptedSubmit = true;
                                  return this.validateSchedule();
                              }
                          }"
                          @submit.capture="if (!validateForm($event)) { $event.preventDefault(); $event.stopImmediatePropagation(); return false; }">
                        @csrf
                        
                        <p class="text-xs text-foreground/50 mb-6">
                            <span class="text-destructive font-bold" aria-hidden="true">*</span> Required field
                        </p>
                        
                        <!-- Scope & Targeting Grid (2 Columns on md+) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- Academic Year -->
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-foreground/80 label-required">Academic Year</label>
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

                            <!-- Year Level -->
                            <div>
                                <label for="year_level" class="block text-sm font-medium text-foreground/80 label-required">Year Level</label>
                                <select id="year_level" name="year_level" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                    <option value="" disabled selected>Select Year Level</option>
                                    @foreach(['1st', '2nd', '3rd', '4th'] as $level)
                                        <option value="{{ $level }}" {{ old('year_level') == $level ? 'selected' : '' }}>{{ $level }} Year</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
                            </div>

                            @if(auth()->user()->isCounselor())
                                <!-- Counselor School (Fixed) -->
                                <div>
                                    <label class="block text-sm font-medium text-foreground/80">School</label>
                                    <input type="text" class="mt-1 block w-full rounded-xl border-border bg-muted/50 text-foreground/70 shadow-sm cursor-not-allowed" value="{{ auth()->user()->assignedDepartment?->name }}" disabled>
                                    <span class="text-xs text-foreground/50 mt-1 block">Your assigned school.</span>
                                </div>

                                <!-- Program (Required for Counselors) -->
                                <div>
                                    <label for="program_id" class="block text-sm font-medium text-foreground/80 label-required">Program</label>
                                    <select id="program_id" name="program_id" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" x-model="selectedProgram" required>
                                        <option value="" disabled selected>Select Program</option>
                                        <template x-for="prog in (departments[0]?.programs || [])" :key="prog.id">
                                            <option :value="prog.id" x-text="prog.name + (prog.code ? ' (' + prog.code + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <span class="text-xs text-foreground/50 mt-1 block">Required: Schedule will be specific to this program.</span>
                                    <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                                </div>
                            @else
                                <!-- School (For Filtering) -->
                                <div>
                                    <label for="department" class="block text-sm font-medium text-foreground/80">School (Filter)</label>
                                    <select id="department" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" x-model="selectedDepartment" @change="selectedProgram = ''">
                                        <option value="">All Schools</option>
                                        <template x-for="dept in departments" :key="dept.id">
                                            <option :value="dept.id" x-text="dept.name"></option>
                                        </template>
                                    </select>
                                    <span class="text-xs text-foreground/50 mt-1 block">Optional: Filter programs by school.</span>
                                </div>

                                <!-- Program -->
                                <div>
                                    <label for="program_id" class="block text-sm font-medium text-foreground/80">Program (Optional)</label>
                                    <select id="program_id" name="program_id" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" x-model="selectedProgram" :disabled="!selectedDepartment && departments.length > 0">
                                        <option value="">All Programs in Year Level</option>
                                        <template x-for="prog in (selectedDepartment ? (departments.find(d => d.id == selectedDepartment)?.programs || []) : [])" :key="prog.id">
                                            <option :value="prog.id" x-text="prog.name + (prog.code ? ' (' + prog.code + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <span class="text-xs text-foreground/50 mt-1 block">Leave blank to apply to all programs.</span>
                                    <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                                </div>
                            @endif
                        </div>

                        <!-- Schedule Window Section (Side-by-Side Equal Height Cards) -->
                        @php
                            $openDateErrors = ($errors->has('open_time') && $errors->first('open_date') === $errors->first('open_time'))
                                ? []
                                : $errors->get('open_date');
                            $closeDateErrors = ($errors->has('close_time') && $errors->first('close_date') === $errors->first('close_time'))
                                ? []
                                : $errors->get('close_date');
                        @endphp
                        <div class="pt-8 border-t border-border mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                                <!-- Open Date/Time Card -->
                                <div class="bg-muted/30 p-6 rounded-2xl border border-border flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                            <h4 class="font-heading font-semibold text-foreground text-base">Opens At</h4>
                                        </div>
                                        <div class="space-y-4">
                                            <div>
                                                <label for="open_date" class="block text-sm font-medium text-foreground/80 label-required">Open Date</label>
                                                <input type="date" 
                                                       id="open_date" 
                                                       name="open_date" 
                                                       x-model="openDate"
                                                       @input="onDateTimeInput()"
                                                       @change="onDateTimeInput()"
                                                       :min="today" 
                                                       class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" 
                                                       required>
                                                <p x-show="clientErrors.open_date" x-text="clientErrors.open_date" class="text-sm text-destructive space-y-1 mt-2" style="display: none;"></p>
                                                <template x-if="!clientErrors.open_date">
                                                    <div>
                                                        <x-input-error :messages="$openDateErrors" class="mt-2" />
                                                    </div>
                                                </template>
                                            </div>
                                            <div>
                                                <label for="open_time" class="block text-sm font-medium text-foreground/80 label-required">Open Time</label>
                                                <input type="time" 
                                                       id="open_time" 
                                                       name="open_time" 
                                                       x-model="openTime"
                                                       @input="onDateTimeInput()"
                                                       @change="onDateTimeInput()"
                                                       class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" 
                                                       required>
                                                <p x-show="clientErrors.open_time" x-text="clientErrors.open_time" class="text-sm text-destructive space-y-1 mt-2" style="display: none;"></p>
                                                <template x-if="!clientErrors.open_time">
                                                    <div>
                                                        <x-input-error :messages="$errors->get('open_time')" class="mt-2" />
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-foreground/50 mt-4 pt-3 border-t border-border/40">
                                        Schedule opens for student responses on this date and time.
                                    </div>
                                </div>

                                <!-- Close Date/Time Card -->
                                <div class="bg-muted/30 p-6 rounded-2xl border border-border flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                            <h4 class="font-heading font-semibold text-foreground text-base">Closes At</h4>
                                        </div>
                                        <div class="space-y-4">
                                            <div>
                                                <label for="close_date" class="block text-sm font-medium text-foreground/80 label-required">Close Date</label>
                                                <input type="date" 
                                                       id="close_date" 
                                                       name="close_date" 
                                                       x-model="closeDate"
                                                       @input="onDateTimeInput()"
                                                       @change="onDateTimeInput()"
                                                       :min="minCloseDate" 
                                                       class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" 
                                                       required>
                                                <p x-show="clientErrors.close_date" x-text="clientErrors.close_date" class="text-sm text-destructive space-y-1 mt-2" style="display: none;"></p>
                                                <template x-if="!clientErrors.close_date">
                                                    <div>
                                                        <x-input-error :messages="$closeDateErrors" class="mt-2" />
                                                    </div>
                                                </template>
                                            </div>
                                            <div>
                                                <label for="close_time" class="block text-sm font-medium text-foreground/80 label-required">Close Time</label>
                                                <input type="time" 
                                                       id="close_time" 
                                                       name="close_time" 
                                                       x-model="closeTime"
                                                       @input="onDateTimeInput()"
                                                       @change="onDateTimeInput()"
                                                       class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" 
                                                       required>
                                                <p x-show="clientErrors.close_time" x-text="clientErrors.close_time" class="text-sm text-destructive space-y-1 mt-2" style="display: none;"></p>
                                                <template x-if="!clientErrors.close_time">
                                                    <div>
                                                        <x-input-error :messages="$errors->get('close_time')" class="mt-2" />
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-foreground/50 mt-4 pt-3 border-t border-border/40">
                                        Schedule closes and responses are locked on this date and time.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
                            <a href="{{ route('schedules.index') }}" class="btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                {{ __('Create Schedule') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
