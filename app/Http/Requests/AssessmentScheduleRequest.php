<?php

namespace App\Http\Requests;

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
            'close_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    if ($this->input('open_date') && $this->input('close_date') && $this->input('open_date') === $this->input('close_date')) {
                        if ($this->input('open_time') && $value <= $this->input('open_time')) {
                            $fail('The close time must be after the open time on the same date.');
                        }
                    }
                },
            ],
        ];
    }
}

