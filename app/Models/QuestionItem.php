<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionItem extends Model
{
    protected $fillable = [
        'question_category_id',
        'question_subcategory_id',
        'item_number',
        'prompt',
        'options',
        'allow_multiple',
        'is_required',
        'subscale_tag'
    ];

    protected $casts = [
        'options' => 'array',
        'allow_multiple' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function allowsMultiple(?QuestionCategory $category = null): bool
    {
        if ($this->allow_multiple !== null) {
            return (bool) $this->allow_multiple;
        }

        $cat = $category ?? $this->questionCategory;

        return (bool) ($cat?->allow_multiple ?? false);
    }

    public function questionCategory()
    {
        return $this->belongsTo(QuestionCategory::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(QuestionSubcategory::class, 'question_subcategory_id');
    }
}
