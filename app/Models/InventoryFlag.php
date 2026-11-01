<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryFlag extends Model
{
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_NEEDS_VERIFICATION = 'needs_verification';
    public const STATUS_UNABLE_TO_DETERMINE = 'unable_to_determine';

    protected $fillable = [
        'inventory_submission_id',
        'flag_type',
        'category',
        'subscale_tag',
        'details',
        'is_reviewed',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
    ];

    protected $casts = [
        'details' => 'array',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(InventorySubmission::class, 'inventory_submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getReviewStatusLabelAttribute(): string
    {
        return match ($this->review_status) {
            self::STATUS_NEEDS_VERIFICATION => 'Needs Verification',
            self::STATUS_UNABLE_TO_DETERMINE => 'Unable to Determine',
            self::STATUS_REVIEWED => 'Reviewed',
            default => $this->is_reviewed ? 'Reviewed' : 'Unreviewed',
        };
    }

    public function getReviewStatusBadgeClassAttribute(): string
    {
        return match ($this->review_status) {
            self::STATUS_NEEDS_VERIFICATION => 'bg-amber-100 text-amber-900 border-amber-300',
            self::STATUS_UNABLE_TO_DETERMINE => 'bg-slate-100 text-slate-800 border-slate-300',
            self::STATUS_REVIEWED => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            default => $this->is_reviewed ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-100 text-rose-800 border-rose-300',
        };
    }
}
