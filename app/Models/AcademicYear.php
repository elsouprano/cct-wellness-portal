<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['label', 'is_current'];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function questionCategories()
    {
        return $this->hasMany(QuestionCategory::class)->orderBy('display_order');
    }

    public function assessmentSchedules()
    {
        return $this->hasMany(AssessmentSchedule::class);
    }
}
