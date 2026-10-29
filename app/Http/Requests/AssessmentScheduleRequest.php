<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessmentScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isCounselor());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'program_id' => 'nullable|exists:programs,id',
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

