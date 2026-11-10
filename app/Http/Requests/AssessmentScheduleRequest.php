<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessmentScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $schedule = $this->route('schedule');
        if (! ($schedule instanceof \App\Models\AssessmentSchedule) && ! empty($schedule)) {
            $schedule = \App\Models\AssessmentSchedule::find($schedule);
        }

        if ($schedule instanceof \App\Models\AssessmentSchedule) {
            if (! $user->canManageSchedule($schedule)) {
                abort(404);
            }
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCounselor()) {
            return ! empty($user->department_id);
        }

        return false;
    }

    /**
     * Normalize date and time inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['open_date', 'close_date'] as $dateField) {
            if ($this->has($dateField) && is_string($this->input($dateField))) {
                $merged[$dateField] = trim($this->input($dateField));
            }
        }

        foreach (['open_time', 'close_time'] as $timeField) {
            if ($this->has($timeField) && is_string($this->input($timeField))) {
                $trimmed = trim($this->input($timeField));
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $trimmed)) {
                    $trimmed = substr($trimmed, 0, 5);
                }
                $merged[$timeField] = $trimmed;
            }
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        $programRules = ['nullable', 'exists:programs,id'];
        if ($user && $user->isCounselor()) {
            $allowedProgramIds = $user->getAllowedProgramIds() ?? [];
            $programRules = ['required', Rule::in($allowedProgramIds)];
        }

        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'program_id' => $programRules,
            'open_date' => ['required', 'string', 'date_format:Y-m-d'],
            'open_time' => ['required', 'string', 'date_format:H:i'],
            'close_date' => ['required', 'string', 'date_format:Y-m-d'],
            'close_time' => ['required', 'string', 'date_format:H:i'],
        ];
    }

    /**
     * Get custom validation messages for schedule date/time fields.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'open_date.required' => 'Please select a valid date.',
            'open_date.string' => 'Please select a valid date.',
            'open_date.date' => 'Please select a valid date.',
            'open_date.date_format' => 'Please select a valid date.',
            'close_date.required' => 'Please select a valid date.',
            'close_date.string' => 'Please select a valid date.',
            'close_date.date' => 'Please select a valid date.',
            'close_date.date_format' => 'Please select a valid date.',
            'open_time.required' => 'Please select a valid time.',
            'open_time.string' => 'Please select a valid time.',
            'open_time.date_format' => 'Please select a valid time.',
            'close_time.required' => 'Please select a valid time.',
            'close_time.string' => 'Please select a valid time.',
            'close_time.date_format' => 'Please select a valid time.',
        ];
    }

    /**
     * Combine a normalized YYYY-MM-DD date and HH:mm time in the given timezone.
     */
    private function combineDateAndTime(string $date, string $time, string $timezone): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }

        try {
            $dateTime = Carbon::createFromFormat('!Y-m-d H:i', "{$date} {$time}", $timezone);
            if (! $dateTime || $dateTime->format('Y-m-d H:i') !== "{$date} {$time}") {
                return null;
            }

            return $dateTime;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Configure the validator instance with custom datetime integrity checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Do not run datetime comparison checks when format validation has failed
            if ($v->errors()->hasAny(['open_date', 'open_time', 'close_date', 'close_time'])) {
                return;
            }

            $openDate = (string) $this->input('open_date', '');
            $openTime = (string) $this->input('open_time', '');
            $closeDate = (string) $this->input('close_date', '');
            $closeTime = (string) $this->input('close_time', '');

            $timezone = config('app.timezone', 'Asia/Manila');

            $openDateTime = $this->combineDateAndTime($openDate, $openTime, $timezone);
            if (! $openDateTime) {
                $v->errors()->add('open_date', 'Please select a valid date.');
                return;
            }

            $closeDateTime = $this->combineDateAndTime($closeDate, $closeTime, $timezone);
            if (! $closeDateTime) {
                $v->errors()->add('close_date', 'Please select a valid date.');
                return;
            }

            // Compare at minute precision in the authoritative application timezone
            $now = Carbon::now($timezone)->startOfMinute();
            $schedule = $this->route('schedule');
            $isOpenUnchanged = false;

            if ($schedule instanceof \App\Models\AssessmentSchedule) {
                $origOpenDate = $schedule->open_date instanceof Carbon 
                    ? $schedule->open_date->format('Y-m-d') 
                    : substr((string) $schedule->open_date, 0, 10);
                $origOpenTime = substr((string) $schedule->open_time, 0, 5);

                if ($openDate === $origOpenDate && $openTime === $origOpenTime) {
                    $isOpenUnchanged = true;
                }
            }

            // On create, or on update if open datetime was changed: open_datetime >= now
            if (! $isOpenUnchanged && $openDateTime->lt($now)) {
                $v->errors()->add('open_date', 'The opening date and time cannot be in the past.');
                if ($openDate === $now->format('Y-m-d')) {
                    $v->errors()->add('open_time', 'The opening date and time cannot be in the past.');
                }
            }

            // Close datetime must be strictly later than open datetime
            if ($closeDateTime->lte($openDateTime)) {
                $v->errors()->add('close_date', 'The closing date and time must be later than the opening date and time.');
                if ($openDate === $closeDate) {
                    $v->errors()->add('close_time', 'The closing date and time must be later than the opening date and time.');
                }
            } elseif ($closeDateTime->lte($now)) {
                $v->errors()->add('close_date', 'The closing date and time must be later than the opening date and time.');
            }
        });
    }
}
