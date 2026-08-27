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
        'subscale_tag'
    ];

    protected $casts = [
        'options' => 'array'
    ];

    public function questionCategory()
    {
        return $this->belongsTo(QuestionCategory::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(QuestionSubcategory::class, 'question_subcategory_id');
    }
}
