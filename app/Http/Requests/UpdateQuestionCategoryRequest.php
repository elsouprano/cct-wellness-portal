<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionCategoryRequest extends FormRequest
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
        $category = $this->route('category');
        $categoryId = $category instanceof \App\Models\QuestionCategory ? $category->id : $category;

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
            'items.*.id' => ['nullable', 'integer', Rule::exists('question_items', 'id')->where('question_category_id', $categoryId)],
            'items.*.item_number' => 'required|integer|distinct',
            'items.*.prompt' => 'required|string',
            'items.*.options' => 'nullable|string',
            'items.*.question_subcategory_id' => 'nullable|string',
            'subcategories' => 'nullable|array',
            'subcategories.*.id' => ['nullable', 'integer', Rule::exists('question_subcategories', 'id')->where('question_category_id', $categoryId)],
            'subcategories.*.temp_id' => 'nullable|string',
            'subcategories.*.name' => 'required|string|max:255',
            'subcategories.*.display_order' => 'required|integer',
            'pairs' => 'nullable|array',
            'pairs.*.id' => ['nullable', 'integer', Rule::exists('correlated_question_pairs', 'id')->where('question_category_id', $categoryId)],
            'pairs.*.question_item_id_a' => ['required', Rule::exists('question_items', 'id')->where('question_category_id', $categoryId)],
            'pairs.*.question_item_id_b' => ['required', Rule::exists('question_items', 'id')->where('question_category_id', $categoryId), 'different:pairs.*.question_item_id_a'],
            'pairs.*.relationship_type' => 'required|in:similar,inverse',
            'pairs.*.contradiction_threshold' => 'required|numeric|min:0|max:100',
            'pairs.*.notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $category = $this->route('category');
            $name = is_string($this->input('name')) ? trim($this->input('name')) : '';
            if (!empty($name) && $category) {
                $exists = \App\Models\QuestionCategory::where('academic_year_id', $category->academic_year_id)
                    ->where('id', '!=', $category->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('name', "A category named '{$name}' already exists in the Question Bank repository.");
                }
            }

            $rawSubcats = $this->input('subcategories', []);
            $validSubRefs = [];
            if (is_array($rawSubcats)) {
                foreach ($rawSubcats as $sub) {
                    if (!is_array($sub)) {
                        continue;
                    }
                    if (!empty($sub['temp_id']) && is_string($sub['temp_id'])) {
                        $validSubRefs[] = $sub['temp_id'];
                    }
                    if (!empty($sub['id'])) {
                        $validSubRefs[] = (string) $sub['id'];
                    }
                }
            }
            if ($category instanceof \App\Models\QuestionCategory) {
                foreach ($category->subcategories()->pluck('id')->all() as $ownedId) {
                    $validSubRefs[] = (string) $ownedId;
                }
            }
            $validSubRefs = array_values(array_unique($validSubRefs));

            $rawItems = $this->input('items', []);
            if (!is_array($rawItems)) {
                return;
            }

            // Duplicate question prevention & subcategory ownership validation
            $prompts = [];
            foreach ($rawItems as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $subId = $item['question_subcategory_id'] ?? null;
                if ($subId !== null && $subId !== '' && (!is_scalar($subId) || !in_array((string) $subId, $validSubRefs, true))) {
                    $validator->errors()->add("items.{$idx}.question_subcategory_id", 'The selected subcategory does not belong to this category.');
                }

                if (isset($item['prompt']) && is_string($item['prompt'])) {
                    $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $item['prompt'])));
                    if (!empty($normalized)) {
                        if (isset($prompts[$normalized])) {
                            $origItem = $prompts[$normalized];
                            $currItem = $item['item_number'] ?? ($idx + 1);
                            $validator->errors()->add("items.{$idx}.prompt", "Duplicate question prompt detected: Item #{$currItem} is identical or very similar to Item #{$origItem}.");
                        }
                        $prompts[$normalized] = $item['item_number'] ?? ($idx + 1);
                    }
                }
            }
        });
    }
}

