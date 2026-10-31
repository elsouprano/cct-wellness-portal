<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySubmission extends Model
{
    protected $fillable = [
        'user_id',
        'academic_year',
        'started_at',
        'submitted_at',
        'consent_given_at',
        'consent_version',
        'signature_type',
        'signature_data',
        'signature_font',
        'quality_classification',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responses()
    {
        return $this->hasMany(InventoryResponse::class);
    }

    public function itemTimings()
    {
        return $this->hasMany(InventoryItemTiming::class);
    }

    public function scores()
    {
        return $this->hasMany(InventoryScore::class);
    }

    public function timings()
    {
        return $this->hasMany(InventoryItemTiming::class);
    }

    public function flags()
    {
        return $this->hasMany(InventoryFlag::class);
    }

    public function qualityAssessment()
    {
        return $this->hasOne(ResponseQualityAssessment::class)->latestOfMany('version');
    }

    public function qualityAssessments()
    {
        return $this->hasMany(ResponseQualityAssessment::class)->orderByDesc('version');
    }

    public function scopeAccessibleByStaff($query, User $staff)
    {
        if ($staff->isAdmin()) {
            return $query;
        }

        $allowedProgramIds = $staff->getAllowedProgramIds();
        if (empty($allowedProgramIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('user', function ($q) use ($allowedProgramIds) {
            $q->where(function ($subQ) use ($allowedProgramIds) {
                $subQ->whereIn('program_id', $allowedProgramIds);
                $allowedCodes = \App\Models\Program::whereIn('id', $allowedProgramIds)->pluck('code')->all();
                $allowedNames = \App\Models\Program::whereIn('id', $allowedProgramIds)->pluck('name')->all();
                $allowedStrings = array_merge($allowedCodes, $allowedNames);
                if (!empty($allowedStrings)) {
                    $subQ->orWhere(function ($fallbackQ) use ($allowedStrings) {
                        $fallbackQ->whereNull('program_id')
                                  ->whereIn('program', $allowedStrings);
                    });
                }
            });
        });
    }
}
