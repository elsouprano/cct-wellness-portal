<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'archived_at'];

    protected $casts = [
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

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function counselors()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function assignedCounselors()
    {
        return $this->belongsToMany(User::class, 'counselor_departments')
            ->where('role', 'guidance_counselor');
    }

    public function assignedCounselorCount(): int
    {
        return User::where('role', 'guidance_counselor')
            ->where(function ($q) {
                $q->where('department_id', $this->id)
                  ->orWhereHas('assignedDepartments', fn ($sub) => $sub->where('departments.id', $this->id));
            })
            ->count();
    }

    /**
     * Presentation-layer abbreviation for known CCT Schools.
     * Falls back to the full name for unmapped schools without inventing abbreviations.
     */
    public function getAbbreviationAttribute(): string
    {
        $map = [
            'school of computer studies' => 'SCS',
            'school of business and management' => 'SBM',
            'school of hospitality and tourism management' => 'SHTM',
            'school of education' => 'SED',
            'school of arts and sciences' => 'SAS',
        ];

        $normalized = strtolower(trim((string) $this->name));

        return $map[$normalized] ?? (string) $this->name;
    }
}
