@section('title', 'Create Assessment Schedule')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h1 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Create Assessment Schedule') }}
            </h1>
            <a href="{{ route('schedules.index') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back to Schedules</a>
        </div>
    </x-slot>

    @php
        $serverNow = now();
        $serverToday = $serverNow->format('Y-m-d');
        $defaultOpenTime = $serverNow->copy()->addMinutes(5)->format('H:i');
        $defaultCloseDate = $serverNow->copy()->addDays(7)->format('Y-m-d');
    @endphp

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="bg-surface overflow-hidden shadow-xs rounded-token-lg border border-border">
            <div class="p-4 sm:p-8">
                <form method="POST" action="{{ route('schedules.store') }}" novalidate data-confirm="save" data-confirm-title="Confirm Assessment Schedule"
                      x-data="{
                          departments: @js($departments ?? []),
                          selectedDepartment: @js((string) old('department', auth()->user()->isCounselor() ? (auth()->user()->getAllowedDepartmentIds()[0] ?? '') : '')),
                          selectedProgram: @js((string) old('program_id', '')),
                          openDate: @js((string) old('open_date', $serverToday)),
                          openTime: @js((string) old('open_time', $defaultOpenTime)),
                          closeDate: @js((string) old('close_date', $defaultCloseDate)),
                          closeTime: @js((string) old('close_time', '17:00')),
                          appTimezone: '{{ config('app.timezone', 'Asia/Manila') }}',
                          serverNowMs: {{ $serverNow->getTimestampMs() }},
                          clientLoadedMs: Date.now(),
                          today: '{{ $serverToday }}',
                          currentTime: '{{ $serverNow->format('H:i') }}',
                          hasAttemptedSubmit: false,
                          clientErrors: { open_date: '', open_time: '', close_date: '', close_time: '' },

                          get minCloseDate() {
                              const now = this.getNowInAppTimezone();
                              return (this.openDate && this.openDate > now.date) ? this.openDate : now.date;
                          },

                          timeToMinutes(t) {
                              if (!t || typeof t !== 'string') return 0;
                              const parts = t.trim().slice(0, 5).split(':').map(Number);
                              if (parts.length !== 2 || Number.isNaN(parts[0]) || Number.isNaN(parts[1])) return 0;
                              return (parts[0] * 60) + parts[1];
                          },

                          minOpenMinutes() {
                              const now = this.getNowInAppTimezone();
                              if (!this.openDate || this.openDate === now.date) {
                                  return this.timeToMinutes(now.time);
                              }
                              if (this.openDate < now.date) {
                                  return 1440;
                              }
                              return 0;
                          },

                          minCloseMinutes() {
                              const now = this.getNowInAppTimezone();
                              let minM = 0;
                              if (!this.closeDate || this.closeDate === now.date) {
                                  minM = Math.max(minM, this.timeToMinutes(now.time) + 1);
                              } else if (this.closeDate < now.date) {
                                  return 1440;
                              }
                              if (this.openDate && this.closeDate === this.openDate) {
                                  minM = Math.max(minM, this.timeToMinutes(this.openTime) + 1);
                              }
                              return minM;
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
                                  for (const p of parts) map[p.type] = p.value;
                                  const hour = map.hour === '24' ? '00' : map.hour;
                                  const date = `${map.year}-${map.month}-${map.day}`;
                                  const time = `${hour}:${map.minute}`;
                                  return { date, time, dateTime: `${date} ${time}` };
                              } catch (e) {
                                  return { date: this.today, time: this.currentTime, dateTime: `${this.today} ${this.currentTime}` };
                              }
                          },

                          normalizeTime(val) {
                              if (!val || typeof val !== 'string') return '';
                              const trimmed = val.trim();
                              return /^\d{2}:\d{2}:\d{2}$/.test(trimmed) ? trimmed.slice(0, 5) : trimmed;
                          },

                          isValidDateString(val) {
                              if (!val || typeof val !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(val.trim())) return false;
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

                          onOpenDateChanged() {
                              if (this.closeDate && this.openDate && this.closeDate < this.openDate) {
                                  this.closeDate = this.openDate;
                              }
                              this.onDateTimeInput();
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

                              if (!openDateValid) this.clientErrors.open_date = 'Please select a valid date.';
                              if (!openTimeValid) this.clientErrors.open_time = 'Please select a valid time.';
                              if (!closeDateValid) this.clientErrors.close_date = 'Please select a valid date.';
                              if (!closeTimeValid) this.clientErrors.close_time = 'Please select a valid time.';

                              if (!openDateValid || !openTimeValid || !closeDateValid || !closeTimeValid) return false;

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
                                  this.clientErrors.close_date = 'The closing date and time cannot be in the past.';
                              }

                              return !this.clientErrors.open_date && !this.clientErrors.open_time && !this.clientErrors.close_date && !this.clientErrors.close_time;
                          },

                          onDateTimeInput() {
                              if (this.hasAttemptedSubmit) this.validateSchedule();
                          },

                          validateForm(event) {
                              const form = event.target;
                              if (form && form.dataset && form.dataset.confirmed === 'true') return true;
                              const selectFields = form.querySelectorAll('select[required]');
                              for (const field of selectFields) {
                                  if (!field.disabled && typeof field.checkValidity === 'function' && !field.checkValidity()) {
                                      if (typeof field.reportValidity === 'function') field.reportValidity();
                                      return false;
                                  }
                              }
                              this.hasAttemptedSubmit = true;
                              return this.validateSchedule();
                          }
                      }"
                      @submit.capture="if (!validateForm($event)) { $event.preventDefault(); $event.stopImmediatePropagation(); return false; }">
                    @csrf

                    <p class="text-xs text-muted-foreground mb-5">
                        <span class="text-destructive font-bold" aria-hidden="true">*</span> Required field
                    </p>

                    <!-- Scope & Targeting Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-foreground label-required">Academic Year</label>
                            <select id="academic_year_id" name="academic_year_id" class="input-field mt-1 block w-full text-sm" required>
                                <option value="" disabled {{ old('academic_year_id') ? '' : 'selected' }}>Select an Academic Year</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ old('academic_year_id', $year->is_current ? $year->id : '') == $year->id ? 'selected' : '' }}>
                                        {{ $year->label }} {{ $year->is_current ? '(Current)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                        </div>

                        <div>
                            <label for="year_level" class="block text-sm font-medium text-foreground label-required">Year Level</label>
                            <select id="year_level" name="year_level" class="input-field mt-1 block w-full text-sm" required>
                                <option value="" disabled selected>Select Year Level</option>
                                @foreach(['1st', '2nd', '3rd', '4th'] as $level)
                                    <option value="{{ $level }}" {{ old('year_level') == $level ? 'selected' : '' }}>{{ $level }} Year</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
                        </div>

                        @if(auth()->user()->isCounselor())
                            <div>
                                <label class="block text-sm font-medium text-foreground">School</label>
                                <input type="text" class="input-field mt-1 block w-full bg-muted/50 text-muted-foreground cursor-not-allowed text-sm" value="{{ auth()->user()->assignedDepartment?->name }}" disabled>
                                <span class="text-xs text-muted-foreground mt-1 block">Your assigned school.</span>
                            </div>

                            <div>
                                <label for="program_id" class="block text-sm font-medium text-foreground label-required">Program</label>
                                <select id="program_id" name="program_id" class="input-field mt-1 block w-full text-sm" x-model="selectedProgram" required>
                                    <option value="" disabled selected>Select Program</option>
                                    <template x-for="prog in (departments[0]?.programs || [])" :key="prog.id">
                                        <option :value="prog.id" x-text="prog.name + (prog.code ? ' (' + prog.code + ')' : '')"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                            </div>
                        @else
                            <div>
                                <label for="department" class="block text-sm font-medium text-foreground">School (Filter)</label>
                                <select id="department" class="input-field mt-1 block w-full text-sm" x-model="selectedDepartment" @change="selectedProgram = ''">
                                    <option value="">All Schools</option>
                                    <template x-for="dept in departments" :key="dept.id">
                                        <option :value="dept.id" x-text="dept.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label for="program_id" class="block text-sm font-medium text-foreground">Program (Optional)</label>
                                <select id="program_id" name="program_id" class="input-field mt-1 block w-full text-sm" x-model="selectedProgram" :disabled="!selectedDepartment && departments.length > 0">
                                    <option value="">All Programs in Year Level</option>
                                    <template x-for="prog in (selectedDepartment ? (departments.find(d => d.id == selectedDepartment)?.programs || []) : [])" :key="prog.id">
                                        <option :value="prog.id" x-text="prog.name + (prog.code ? ' (' + prog.code + ')' : '')"></option>
                                    </template>
                                </select>
                                <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                            </div>
                        @endif
                    </div>

                    <!-- Schedule Window Section -->
                    @php
                        $openDateErrors = ($errors->has('open_time') && $errors->first('open_date') === $errors->first('open_time'))
                            ? []
                            : $errors->get('open_date');
                        $closeDateErrors = ($errors->has('close_time') && $errors->first('close_date') === $errors->first('close_time'))
                            ? []
                            : $errors->get('close_date');
                    @endphp
                    <div class="pt-6 border-t border-border mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-stretch">
                            <!-- Opens At Card -->
                            <div class="bg-surface-subtle/60 p-4 sm:p-5 rounded-token-lg border border-border flex flex-col justify-between">
                                <div class="space-y-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-success"></span>
                                        <h2 class="font-heading font-semibold text-foreground text-base">Opens At</h2>
                                    </div>
                                    <div>
                                        <label for="open_date" class="block text-sm font-medium text-foreground label-required">Open Date</label>
                                        <input type="date"
                                               id="open_date"
                                               name="open_date"
                                               x-model="openDate"
                                               @change="onOpenDateChanged()"
                                               min="{{ $serverToday }}"
                                               class="input-field mt-1 block w-full text-sm"
                                               required>
                                        <p x-show="clientErrors.open_date" x-cloak x-text="clientErrors.open_date" class="text-sm text-destructive mt-1.5"></p>
                                        <template x-if="!clientErrors.open_date">
                                            <div><x-input-error :messages="$openDateErrors" class="mt-1.5" /></div>
                                        </template>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-foreground label-required">Open Time</span>
                                        <x-schedule-time-picker
                                            name="open_time"
                                            id="open_time"
                                            model="openTime"
                                            min-minutes-expr="minOpenMinutes()"
                                            label-prefix="Opening"
                                        />
                                        <p x-show="clientErrors.open_time" x-cloak x-text="clientErrors.open_time" class="text-sm text-destructive mt-1.5"></p>
                                        <template x-if="!clientErrors.open_time">
                                            <div><x-input-error :messages="$errors->get('open_time')" class="mt-1.5" /></div>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground mt-4 pt-3 border-t border-border/60">
                                    Past times on today's date are automatically disabled.
                                </p>
                            </div>

                            <!-- Closes At Card -->
                            <div class="bg-surface-subtle/60 p-4 sm:p-5 rounded-token-lg border border-border flex flex-col justify-between">
                                <div class="space-y-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-destructive"></span>
                                        <h2 class="font-heading font-semibold text-foreground text-base">Closes At</h2>
                                    </div>
                                    <div>
                                        <label for="close_date" class="block text-sm font-medium text-foreground label-required">Close Date</label>
                                        <input type="date"
                                               id="close_date"
                                               name="close_date"
                                               x-model="closeDate"
                                               @change="onDateTimeInput()"
                                               min="{{ $serverToday }}"
                                               :min="minCloseDate"
                                               class="input-field mt-1 block w-full text-sm"
                                               required>
                                        <p x-show="clientErrors.close_date" x-cloak x-text="clientErrors.close_date" class="text-sm text-destructive mt-1.5"></p>
                                        <template x-if="!clientErrors.close_date">
                                            <div><x-input-error :messages="$closeDateErrors" class="mt-1.5" /></div>
                                        </template>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-medium text-foreground label-required">Close Time</span>
                                        <x-schedule-time-picker
                                            name="close_time"
                                            id="close_time"
                                            model="closeTime"
                                            min-minutes-expr="minCloseMinutes()"
                                            label-prefix="Closing"
                                        />
                                        <p x-show="clientErrors.close_time" x-cloak x-text="clientErrors.close_time" class="text-sm text-destructive mt-1.5"></p>
                                        <template x-if="!clientErrors.close_time">
                                            <div><x-input-error :messages="$errors->get('close_time')" class="mt-1.5" /></div>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground mt-4 pt-3 border-t border-border/60">
                                    Closing date and time must always be after the opening date and time.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-5 border-t border-border">
                        <a href="{{ route('schedules.index') }}" class="btn-secondary w-full sm:w-auto">
                            Cancel
                        </a>
                        <button type="submit" class="btn-primary w-full sm:w-auto">
                            {{ __('Create Schedule') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-staff-layout>
