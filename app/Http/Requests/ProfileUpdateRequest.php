<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'contact_number' => ['required', 'string', 'regex:/^[0-9]{11}\z/'],
            'address_line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];

        if (empty($this->user()?->gender)) {
            $rules['gender'] = ['nullable', 'string', Rule::in(User::GENDERS)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contact_number.regex' => 'Contact number must be exactly 11 digits using numbers only.',
        ];
    }
}
