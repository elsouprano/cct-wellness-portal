<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'display_order' => 'required|integer',
            'instructions' => 'nullable|string',
            'scale_type' => 'required|string|in:numeric_scale,multiple_choice_unscored',
            'scale_min' => 'required_if:scale_type,numeric_scale|nullable|integer',
            'scale_max' => 'required_if:scale_type,numeric_scale|nullable|integer|gt:scale_min',
            'default_options' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($this->input('scale_type') === 'multiple_choice_unscored') {
                        $items = $this->input('items', []);
                        $hasDefaultOptions = !empty(trim($value ?? ''));
                        $allItemsHaveOptions = collect($items)->every(function ($item) {
                            return !empty(trim($item['options'] ?? ''));
                        });

                        if (!$hasDefaultOptions && !$allItemsHaveOptions) {
                            $fail('For Multiple Choice categories, you must either provide Default Options or ensure every individual item has custom options provided.');
                        }
                    }
                }
            ],
            'scale_labels' => 'nullable|array',
            'scale_labels.*' => 'nullable|string|max:255',
            'items' => 'required|array',
            'items.*.item_number' => 'required|integer',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string',
            'items.*.question_subcategory_id' => 'nullable|string',
            'subcategories' => 'nullable|array',
            'subcategories.*.temp_id' => 'nullable|string',
            'subcategories.*.name' => 'required|string|max:255',
            'subcategories.*.display_order' => 'required|integer',
        ];
    }
}

