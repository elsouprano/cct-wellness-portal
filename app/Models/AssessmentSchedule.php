<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSchedule extends Model
{
    public const CLOSING_SOON_HOURS = 48;

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

    protected $casts = [
        'academic_year_id' => 'integer',
        'program_id' => 'integer',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function structuredProgram()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function scopeVisibleToStaff($query, User $staff)
    {
        if ($staff->isAdmin()) {
            return $query;
        }

        $allowedProgramIds = $staff->getAllowedProgramIds();
        if (empty($allowedProgramIds)) {
            return $query->whereNull('program_id');
        }

        return $query->where(function ($q) use ($allowedProgramIds) {
            $q->whereNull('program_id')
              ->orWhereIn('program_id', $allowedProgramIds);
        });
    }

    private static function applyUserProgramFilter($query, $user): void
    {
        $programId = $user->program_id ?? null;
        if (! $programId && ! empty($user->program)) {
            $programId = Program::where('code', $user->program)
                ->orWhere('name', $user->program)
                ->value('id');
        }

        $query->where(function ($q) use ($programId, $user) {
            $q->whereNull('program_id');
            if ($programId) {
                $q->orWhere('program_id', $programId);
            } elseif (! empty($user->program)) {
                $q->orWhere('program', $user->program);
            }
        });
    }

    public static function getActiveForUser($user, $academicYearId)
    {
        if (empty($user->year_level)) {
            return null; // Block users with no year_level
        }
        
        $yearLevel = $user->year_level;
        $now = now();
        $dateStr = $now->toDateString();
        $timeHi = $now->format('H:i');

        $query = self::where('academic_year_id', $academicYearId)
            ->where('year_level', $yearLevel);

        self::applyUserProgramFilter($query, $user);

        return $query
            ->where(function ($query) use ($dateStr, $timeHi) {
                $query->where('open_date', '<', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeHi) {
                          $q->where('open_date', '=', $dateStr)
                            ->whereRaw('SUBSTR(open_time, 1, 5) <= ?', [$timeHi]);
                      });
            })
            ->where(function ($query) use ($dateStr, $timeHi) {
                $query->where('close_date', '>', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeHi) {
                          $q->where('close_date', '=', $dateStr)
                            ->whereRaw('SUBSTR(close_time, 1, 5) >= ?', [$timeHi]);
                      });
            })
            ->orderByRaw('program_id IS NOT NULL DESC') // Prioritize program-specific schedules
            ->first();
    }

    public static function getUpcomingForUser($user, $academicYearId)
    {
        if (empty($user->year_level)) {
            return null;
        }

        $yearLevel = $user->year_level;
        $now = now();
        $dateStr = $now->toDateString();
        $timeHi = $now->format('H:i');

        $query = self::where('academic_year_id', $academicYearId)
            ->where('year_level', $yearLevel);

        self::applyUserProgramFilter($query, $user);

        return $query
            ->where(function ($query) use ($dateStr, $timeHi) {
                $query->where('open_date', '>', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeHi) {
                          $q->where('open_date', '=', $dateStr)
                            ->whereRaw('SUBSTR(open_time, 1, 5) > ?', [$timeHi]);
                      });
            })
            ->orderByRaw('program_id IS NOT NULL DESC')
            ->orderBy('open_date')
            ->orderBy('open_time')
            ->first();
    }

    public function getOpenAtAttribute(): ?\Carbon\Carbon
    {
        if (! $this->open_date) return null;
        $tz = config('app.timezone', 'Asia/Manila');
        $date = $this->open_date instanceof \Carbon\Carbon
            ? $this->open_date->format('Y-m-d')
            : substr((string) $this->open_date, 0, 10);
        $time = substr((string) ($this->open_time ?: '00:00'), 0, 5);
        return \Carbon\Carbon::createFromFormat('!Y-m-d H:i', "{$date} {$time}", $tz);
    }

    public function getCloseAtAttribute(): ?\Carbon\Carbon
    {
        if (! $this->close_date) return null;
        $tz = config('app.timezone', 'Asia/Manila');
        $date = $this->close_date instanceof \Carbon\Carbon
            ? $this->close_date->format('Y-m-d')
            : substr((string) $this->close_date, 0, 10);
        $time = substr((string) ($this->close_time ?: '23:59'), 0, 5);
        return \Carbon\Carbon::createFromFormat('!Y-m-d H:i', "{$date} {$time}", $tz);
    }

    public function getComputedStatusAttribute(): string
    {
        $now = now();
        $openAt = $this->open_at;
        $closeAt = $this->close_at;

        if (! $openAt || ! $closeAt) {
            return 'Unknown';
        }

        if ($now->lt($openAt)) {
            return 'Upcoming';
        }

        if ($now->gt($closeAt)) {
            return 'Archived';
        }

        if ($now->diffInHours($closeAt, false) <= self::CLOSING_SOON_HOURS) {
            return 'Closing soon';
        }

        return 'Open';
    }

    public function isArchived(): bool
    {
        return $this->computed_status === 'Archived';
    }

    public function hasOpened(): bool
    {
        $openAt = $this->open_at;
        if (! $openAt) {
            return false;
        }

        return now()->greaterThanOrEqualTo($openAt);
    }

    public function scopeActiveSchedules($query)
    {
        $today = now()->toDateString();
        $nowHi = now()->format('H:i');

        return $query->where(function ($q) use ($today, $nowHi) {
            $q->where('close_date', '>', $today)
              ->orWhere(function ($sub) use ($today, $nowHi) {
                  $sub->where('close_date', $today)
                      ->whereRaw('SUBSTR(close_time, 1, 5) >= ?', [$nowHi]);
              });
        });
    }

    public function scopeArchivedSchedules($query)
    {
        $today = now()->toDateString();
        $nowHi = now()->format('H:i');

        return $query->where(function ($q) use ($today, $nowHi) {
            $q->where('close_date', '<', $today)
              ->orWhere(function ($sub) use ($today, $nowHi) {
                  $sub->where('close_date', $today)
                      ->whereRaw('SUBSTR(close_time, 1, 5) < ?', [$nowHi]);
              });
        });
    }
}
