<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionSubcategory extends Model
{
    protected $fillable = [
        'question_category_id',
        'name',
        'display_order',
    ];

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function questionItems()
    {
        return $this->hasMany(QuestionItem::class, 'question_subcategory_id');
    }
}
