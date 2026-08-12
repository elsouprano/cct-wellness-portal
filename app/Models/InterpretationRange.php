<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterpretationRange extends Model
{
    protected $fillable = [
        'question_category_id',
        'subscale_tag',
        'min_score',
        'max_score',
        'label',
        'description',
        'color_tag',
        'display_order',
        'is_official_default',
        'created_by'
    ];

    protected $casts = [
        'is_official_default' => 'boolean',
    ];

    public function questionCategory()
    {
        return $this->belongsTo(QuestionCategory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
