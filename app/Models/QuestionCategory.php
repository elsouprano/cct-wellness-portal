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
        'is_locked'
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function questionItems()
    {
        return $this->hasMany(QuestionItem::class)->orderBy('item_number');
    }

    public function isDynamicallyLocked()
    {
        if ($this->is_locked) {
            return true;
        }

        // Check if any submission exists for this academic year
        // We look up by the academic year label string
        $hasSubmissions = \App\Models\InventorySubmission::where('academic_year', $this->academicYear->label)
            ->exists();

        if ($hasSubmissions) {
            // Permanently lock it in DB so we don't have to keep querying
            $this->update(['is_locked' => true]);
            return true;
        }

        return false;
    }
}
