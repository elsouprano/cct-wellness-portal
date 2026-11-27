@props([
    'name',
    'id' => null,
    'model',
    'minMinutesExpr' => '0',
    'readonly' => false,
    'labelPrefix' => 'Time',
])

@php
    $baseId = $id ?: $name;
@endphp

<div x-data="{
        hour12: '08',
        minute: '00',
        period: 'AM',
        isLocked: @js((bool) $readonly),
        syncingFromParent: false,

        init() {
            this.parseFrom24({{ $model }});
            if (!this.isLocked && !{{ $model }}) {
                this.clampAndEmit();
            } else if (!this.isLocked) {
                this.clampIfInvalid();
            }

            this.$watch('{{ $model }}', (val) => {
                if (!this.syncingFromParent) {
                    this.parseFrom24(val);
                }
            });

            this.$watch(() => ({{ $minMinutesExpr }}), () => {
                if (!this.isLocked) {
                    this.clampIfInvalid();
                }
            });
        },

        minAllowed() {
            const raw = Number({{ $minMinutesExpr }});
            return Number.isFinite(raw) ? Math.max(0, Math.floor(raw)) : 0;
        },

        to24Minutes(p, h12Str, minStr) {
            const h12 = parseInt(h12Str, 10) || 12;
            const m = parseInt(minStr, 10) || 0;
            const h24 = (p === 'PM' ? 12 : 0) + (h12 % 12);
            return (h24 * 60) + m;
        },

        fromTotalMinutes(total) {
            const clamped = Math.max(0, Math.min(1439, total));
            const h24 = Math.floor(clamped / 60);
            const m = clamped % 60;
            const p = h24 >= 12 ? 'PM' : 'AM';
            const h12 = (h24 % 12) === 0 ? 12 : (h24 % 12);
            return {
                period: p,
                hour12: String(h12).padStart(2, '0'),
                minute: String(m).padStart(2, '0'),
                hhmm: String(h24).padStart(2, '0') + ':' + String(m).padStart(2, '0'),
            };
        },

        parseFrom24(val) {
            if (!val || typeof val !== 'string') {
                const start = Math.min(1439, Math.max(this.minAllowed(), 480));
                const parts = this.fromTotalMinutes(start);
                this.period = parts.period;
                this.hour12 = parts.hour12;
                this.minute = parts.minute;
                return;
            }
            const clean = val.trim().slice(0, 5);
            const match = clean.match(/^(\d{2}):(\d{2})$/);
            if (!match) return;
            const total = (parseInt(match[1], 10) * 60) + parseInt(match[2], 10);
            const parts = this.fromTotalMinutes(total);
            this.period = parts.period;
            this.hour12 = parts.hour12;
            this.minute = parts.minute;
        },

        isPeriodDisabled(p) {
            if (this.isLocked) return true;
            const minM = this.minAllowed();
            const maxForPeriod = p === 'AM' ? 719 : 1439;
            return maxForPeriod < minM;
        },

        isHourDisabled(h12Str) {
            if (this.isLocked) return true;
            const minM = this.minAllowed();
            const maxMinuteOfHour = this.to24Minutes(this.period, h12Str, '59');
            return maxMinuteOfHour < minM;
        },

        isMinuteDisabled(minStr) {
            if (this.isLocked) return true;
            const minM = this.minAllowed();
            return this.to24Minutes(this.period, this.hour12, minStr) < minM;
        },

        clampIfInvalid() {
            const minM = this.minAllowed();
            if (minM > 1439) return;
            const current = this.to24Minutes(this.period, this.hour12, this.minute);
            if (current < minM) {
                const next = this.fromTotalMinutes(minM);
                this.period = next.period;
                this.hour12 = next.hour12;
                this.minute = next.minute;
                this.emit24(next.hhmm);
            }
        },

        clampAndEmit() {
            const minM = Math.min(1439, this.minAllowed());
            let total = this.to24Minutes(this.period, this.hour12, this.minute);
            if (total < minM) {
                total = minM;
            }
            const parts = this.fromTotalMinutes(total);
            this.period = parts.period;
            this.hour12 = parts.hour12;
            this.minute = parts.minute;
            this.emit24(parts.hhmm);
        },

        emit24(hhmm) {
            this.syncingFromParent = true;
            {{ $model }} = hhmm;
            if (typeof onDateTimeInput === 'function') {
                onDateTimeInput();
            }
            this.$nextTick(() => { this.syncingFromParent = false; });
        }
     }"
     class="mt-1">

    <input type="hidden" id="{{ $baseId }}" name="{{ $name }}" :value="{{ $model }}">

    <div class="grid grid-cols-3 gap-2">
        <!-- Hour (01-12) -->
        <div>
            <label for="{{ $baseId }}_hour" class="sr-only">{{ $labelPrefix }} hour</label>
            <select id="{{ $baseId }}_hour"
                    x-model="hour12"
                    @change="clampAndEmit()"
                    :disabled="isLocked"
                    class="input-field w-full min-h-[44px] py-2 px-2.5 text-sm font-semibold tabular-nums disabled:bg-muted/60 disabled:text-muted-foreground disabled:cursor-not-allowed">
                @foreach(['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'] as $hr)
                    <option value="{{ $hr }}" :disabled="isHourDisabled('{{ $hr }}')">{{ (int) $hr }}</option>
                @endforeach
            </select>
        </div>

        <!-- Minute (00-59) -->
        <div>
            <label for="{{ $baseId }}_minute" class="sr-only">{{ $labelPrefix }} minute</label>
            <select id="{{ $baseId }}_minute"
                    x-model="minute"
                    @change="clampAndEmit()"
                    :disabled="isLocked"
                    class="input-field w-full min-h-[44px] py-2 px-2.5 text-sm font-semibold tabular-nums disabled:bg-muted/60 disabled:text-muted-foreground disabled:cursor-not-allowed">
                @for($m = 0; $m < 60; $m++)
                    @php $mPad = str_pad((string) $m, 2, '0', STR_PAD_LEFT); @endphp
                    <option value="{{ $mPad }}" :disabled="isMinuteDisabled('{{ $mPad }}')">:{{ $mPad }}</option>
                @endfor
            </select>
        </div>

        <!-- AM / PM -->
        <div>
            <label for="{{ $baseId }}_period" class="sr-only">{{ $labelPrefix }} AM or PM</label>
            <select id="{{ $baseId }}_period"
                    x-model="period"
                    @change="clampAndEmit()"
                    :disabled="isLocked"
                    class="input-field w-full min-h-[44px] py-2 px-2.5 text-sm font-semibold disabled:bg-muted/60 disabled:text-muted-foreground disabled:cursor-not-allowed">
                <option value="AM" :disabled="isPeriodDisabled('AM')">AM</option>
                <option value="PM" :disabled="isPeriodDisabled('PM')">PM</option>
            </select>
        </div>
    </div>
</div>
