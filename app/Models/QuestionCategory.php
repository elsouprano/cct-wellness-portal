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

    public function yearLevelAssignments()
    {
        return $this->hasMany(CategoryYearLevel::class, 'question_category_id')->orderBy('display_order');
    }

    public function getAssignedYearLevelsAttribute(): array
    {
        if ($this->relationLoaded('yearLevelAssignments')) {
            $fromAssignments = $this->yearLevelAssignments->pluck('year_level')->filter()->all();
        } else {
            $fromAssignments = $this->yearLevelAssignments()->pluck('year_level')->filter()->all();
        }

        if (!empty($fromAssignments)) {
            return array_values(array_unique($fromAssignments));
        }

        return $this->year_level ? [$this->year_level] : [];
    }

    public function isAssignedToYearLevel(string $yearLevel): bool
    {
        return in_array($yearLevel, $this->assigned_year_levels, true);
    }

    public function scopeForYearLevel($query, string $yearLevel, ?int $academicYearId = null)
    {
        return $query->where(function ($q) use ($yearLevel, $academicYearId) {
            $q->whereHas('yearLevelAssignments', function ($sub) use ($yearLevel, $academicYearId) {
                $sub->where('year_level', $yearLevel);
                if ($academicYearId) {
                    $sub->where(function ($ay) use ($academicYearId) {
                        $ay->where('academic_year_id', $academicYearId)->orWhereNull('academic_year_id');
                    });
                }
            })
            ->orWhere('year_level', $yearLevel);
        });
    }

    public function hasStudentResponses(): bool
    {
        return \App\Models\InventoryResponse::where('category', $this->name)->exists();
    }

    public function canBeSafelyDeleted(): bool
    {
        return !$this->isDynamicallyLocked() && !$this->hasStudentResponses();
    }

    public function isDynamicallyLocked()
    {
        $assignedLevels = $this->assigned_year_levels;
        if (empty($assignedLevels) && $this->year_level) {
            $assignedLevels = [$this->year_level];
        }

        $hasActiveSchedule = \App\Models\AssessmentSchedule::where('academic_year_id', $this->academic_year_id)
            ->whereIn('year_level', $assignedLevels)
            ->get()
            ->contains(function ($schedule) {
                $closeDateTime = \Carbon\Carbon::parse($schedule->close_date . ' ' . $schedule->close_time);
                return now()->lessThan($closeDateTime);
            });

        $shouldBeLocked = $hasActiveSchedule;

        // If the DB state doesn't match the true state, update it
        if ($this->is_locked !== $shouldBeLocked) {
            // Use DB to avoid Eloquent events loop if any
            \Illuminate\Support\Facades\DB::table('question_categories')
                ->where('id', $this->id)
                ->update(['is_locked' => $shouldBeLocked]);
                
            $this->is_locked = $shouldBeLocked;
        }

        return $shouldBeLocked;
    }

    public function interpretationRanges()
    {
        return $this->hasMany(InterpretationRange::class)->orderBy('display_order');
    }
}
