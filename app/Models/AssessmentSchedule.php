<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSchedule extends Model
{
    protected $fillable = [
        'academic_year_id',
        'year_level',
        'program',
        'open_date',
        'open_time',
        'close_date',
        'close_time'
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
