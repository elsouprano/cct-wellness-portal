<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['label', 'is_current', 'archived_at'];

    protected $casts = [
        'is_current' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Extract the 4-digit start year from the academic year label (e.g. "2026-2027" -> 2026).
     */
    public function getStartYear(): ?int
    {
        if (preg_match('/^(\d{4})/', (string) $this->label, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Determine if this academic year is eligible to be set as active.
     * Rule: Its start year must be greater than the currently active academic year's start year.
     * If no academic year is currently active, allow any year that is not before the current calendar year.
     */
    public function canBeSetActive(): bool
    {
        if ($this->is_current) {
            return false;
        }

        $targetStartYear = $this->getStartYear();
        if ($targetStartYear === null) {
            return false;
        }

        $currentActiveYear = static::where('is_current', true)->first();

        if ($currentActiveYear) {
            $currentStartYear = $currentActiveYear->getStartYear();
            return $currentStartYear !== null && $targetStartYear > $currentStartYear;
        }

        $currentCalendarYear = (int) now()->format('Y');

        return $targetStartYear >= $currentCalendarYear;
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
