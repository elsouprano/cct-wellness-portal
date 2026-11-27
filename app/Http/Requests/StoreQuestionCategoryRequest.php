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
            'display_name' => 'nullable|string|max:150',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'year_levels' => 'nullable|array',
            'year_levels.*' => 'in:1st,2nd,3rd,4th',
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
                        $hasDefaultOptions = is_string($value) && trim($value) !== '';
                        $allItemsHaveOptions = is_array($items) && !empty($items) && collect($items)->every(function ($item) {
                            return is_array($item) && is_string($item['options'] ?? null) && trim($item['options']) !== '';
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
            'items.*.item_number' => 'required|integer|distinct',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string',
            'items.*.question_subcategory_id' => 'nullable|string',
            'subcategories' => 'nullable|array',
            'subcategories.*.temp_id' => 'nullable|string',
            'subcategories.*.name' => 'required|string|max:255',
            'subcategories.*.display_order' => 'required|integer',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $name = is_string($this->input('name')) ? trim($this->input('name')) : '';
            if (!empty($name)) {
                $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
                $normalizedCode = \App\Support\QuestionTextNormalizer::normalizeCode($name);
                $query = \App\Models\QuestionCategory::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedCode]);
                if ($currentYear) {
                    $query->where('academic_year_id', $currentYear->id);
                }
                if ($query->exists()) {
                    $validator->errors()->add('name', "A category named '{$name}' already exists in the Question Bank repository.");
                }
            }

            // Duplicate question prevention & subcategory reference integrity within category
            $rawSubcats = $this->input('subcategories', []);
            $validTempIds = is_array($rawSubcats)
                ? collect($rawSubcats)->pluck('temp_id')->filter(fn ($v) => is_string($v) && $v !== '')->all()
                : [];

            $rawItems = $this->input('items', []);
            if (!is_array($rawItems)) {
                return;
            }

            $prompts = [];
            foreach ($rawItems as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $subId = $item['question_subcategory_id'] ?? null;
                if ($subId !== null && $subId !== '' && (!is_string($subId) || !in_array($subId, $validTempIds, true))) {
                    $validator->errors()->add("items.{$idx}.question_subcategory_id", 'The selected subcategory is invalid.');
                }

                if (isset($item['prompt']) && is_string($item['prompt'])) {
                    $normalized = \App\Support\QuestionTextNormalizer::normalize($item['prompt']);
                    if ($normalized !== '') {
                        if (isset($prompts[$normalized])) {
                            $origItem = $prompts[$normalized];
                            $currItem = $item['item_number'] ?? ($idx + 1);
                            $validator->errors()->add("items.{$idx}.prompt", "Duplicate question prompt detected: Item #{$currItem} is identical to Item #{$origItem}.");
                        } else {
                            $prompts[$normalized] = $item['item_number'] ?? ($idx + 1);
                        }
                    }
                }
            }
        });
    }
}

