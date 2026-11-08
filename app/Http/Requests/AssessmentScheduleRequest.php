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
            'open_date' => 'required|date',
            'open_time' => 'required|date_format:H:i',
            'close_date' => 'required|date|after_or_equal:open_date',
            'close_time' => 'required|date_format:H:i',
        ];
    }

    /**
     * Configure the validator instance with custom datetime integrity checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $openDate = $this->input('open_date');
            $openTime = $this->input('open_time');
            $closeDate = $this->input('close_date');
            $closeTime = $this->input('close_time');

            if (! $openDate || ! $openTime || ! $closeDate || ! $closeTime) {
                return;
            }

            try {
                $openDateTime = Carbon::parse("{$openDate} {$openTime}");
                $closeDateTime = Carbon::parse("{$closeDate} {$closeTime}");
            } catch (\Exception $e) {
                return;
            }

            $now = now();
            $schedule = $this->route('schedule');
            $isOpenUnchanged = false;

            if ($schedule instanceof \App\Models\AssessmentSchedule) {
                $origOpenDate = $schedule->open_date instanceof Carbon 
                    ? $schedule->open_date->format('Y-m-d') 
                    : substr((string) $schedule->open_date, 0, 10);
                $origOpenTime = substr($schedule->open_time, 0, 5);

                if ($openDate === $origOpenDate && $openTime === $origOpenTime) {
                    $isOpenUnchanged = true;
                }
            }

            // On create, or on update if open datetime was changed:
            if (! $isOpenUnchanged && $openDateTime->lt($now)) {
                $v->errors()->add('open_date', 'The open date and time cannot be in the past.');
            }

            // Close datetime must be after open datetime
            if ($closeDateTime->lte($openDateTime)) {
                if ($openDate === $closeDate) {
                    $v->errors()->add('close_time', 'The close time must be after the open time on the same date.');
                } else {
                    $v->errors()->add('close_date', 'The close date and time must be after the open date and time.');
                }
            }

            // Close datetime must be in the future
            if ($closeDateTime->lte($now)) {
                $v->errors()->add('close_date', 'The close date and time must be in the future.');
            }
        });
    }
}
