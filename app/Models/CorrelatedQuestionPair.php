<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrelatedQuestionPair extends Model
{
    protected $fillable = [
        'question_category_id',
        'question_item_id_a',
        'question_item_id_b',
        'relationship_type',
        'contradiction_threshold',
        'notes',
        'created_by'
    ];

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function itemA()
    {
        return $this->belongsTo(QuestionItem::class, 'question_item_id_a');
    }

    public function itemB()
    {
        return $this->belongsTo(QuestionItem::class, 'question_item_id_b');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
