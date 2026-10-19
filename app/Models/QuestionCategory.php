<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    protected $fillable = [
        'academic_year_id',
        'year_level',
        'name',
        'display_order',
        'instructions',
        'scale_type',
        'scale_min',
        'scale_max',
        'scale_labels',
        'default_options',
        'is_locked'
    ];

    protected $casts = [
        'default_options' => 'array',
        'scale_labels' => 'array',
        'is_locked' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function questionItems()
    {
        return $this->hasMany(QuestionItem::class)->orderBy('item_number');
    }

    public function subcategories()
    {
        return $this->hasMany(QuestionSubcategory::class)->orderBy('display_order');
    }

    public function correlatedPairs()
    {
        return $this->hasMany(CorrelatedQuestionPair::class);
    }

    public function isDynamicallyLocked(): bool
    {
        if ($this->is_locked) {
            return true;
        }

        // Check if there is any active assessment schedule using this category's year level and academic year
        // Active means the schedule's close date/time hasn't passed yet.
        return \App\Models\AssessmentSchedule::where('academic_year_id', $this->academic_year_id)
            ->where('year_level', $this->year_level)
            ->get()
            ->contains(function ($schedule) {
                $closeDateTime = \Carbon\Carbon::parse($schedule->close_date . ' ' . $schedule->close_time);
                return now()->lessThan($closeDateTime);
            });
    }

    public function interpretationRanges()
    {
        return $this->hasMany(InterpretationRange::class)->orderBy('display_order');
    }
}
