<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSchedule extends Model
{
    protected $fillable = [
        'academic_year_id',
        'year_level',
        'program',
        'program_id',
        'open_date',
        'open_time',
        'close_date',
        'close_time'
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function structuredProgram()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public static function getActiveForUser($user, $academicYearId)
    {
        $yearLevel = $user->year_level ?? '3rd';
        $now = now();
        $dateStr = $now->toDateString();
        $timeStr = $now->toTimeString();

        return self::where('academic_year_id', $academicYearId)
            ->where('year_level', $yearLevel)
            ->where(function ($query) use ($user) {
                $query->whereNull('program_id')
                      ->orWhere('program_id', $user->program_id);
            })
            ->where(function ($query) use ($dateStr, $timeStr) {
                $query->where('open_date', '<', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeStr) {
                          $q->where('open_date', '=', $dateStr)->where('open_time', '<=', $timeStr);
                      });
            })
            ->where(function ($query) use ($dateStr, $timeStr) {
                $query->where('close_date', '>', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeStr) {
                          $q->where('close_date', '=', $dateStr)->where('close_time', '>=', $timeStr);
                      });
            })
            ->first();
    }

    public static function getUpcomingForUser($user, $academicYearId)
    {
        $yearLevel = $user->year_level ?? '3rd';
        $now = now();
        $dateStr = $now->toDateString();
        $timeStr = $now->toTimeString();

        return self::where('academic_year_id', $academicYearId)
            ->where('year_level', $yearLevel)
            ->where(function ($query) use ($user) {
                $query->whereNull('program_id')
                      ->orWhere('program_id', $user->program_id);
            })
            ->where(function ($query) use ($dateStr, $timeStr) {
                $query->where('open_date', '>', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeStr) {
                          $q->where('open_date', '=', $dateStr)->where('open_time', '>', $timeStr);
                      });
            })
            ->orderBy('open_date')->orderBy('open_time')
            ->first();
    }
}
