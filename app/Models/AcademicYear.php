<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model
{
    protected $fillable = ['label', 'is_current'];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    /**
     * Retrieve the active academic year from cache (cleared on update in InstitutionController).
     */
    public static function current(): ?self
    {
        return Cache::remember('current_academic_year', 3600, function () {
            return static::where('is_current', true)->first();
        });
    }

    public function questionCategories()
    {
        return $this->hasMany(QuestionCategory::class)->orderBy('display_order');
    }

    public function assessmentSchedules()
    {
        return $this->hasMany(AssessmentSchedule::class);
    }
}
