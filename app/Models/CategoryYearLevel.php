<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryYearLevel extends Model
{
    protected $fillable = [
        'question_category_id',
        'academic_year_id',
        'year_level',
        'display_order',
    ];

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

