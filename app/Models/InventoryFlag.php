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

    /**
     * Get a polished human-readable description for this flag in sentence case with no snake_case.
     */
    public function getReadableDescriptionAttribute(): string
    {
        $categoryName = $this->category ? \App\Support\InstrumentLabel::for($this->category) : null;
        $details = is_array($this->details) ? $this->details : json_decode($this->details ?? '[]', true);

        return match ($this->flag_type) {
            'speed' => 'Unusually fast responses',
            'straight_line' => $categoryName
                ? "Repeated identical answers (straight-lining) in {$categoryName}"
                : 'Repeated identical answers (straight-lining)',
            'contradiction' => (function () use ($categoryName, $details) {
                $itemA = $details['item_a']['item_number'] ?? null;
                $itemB = $details['item_b']['item_number'] ?? null;
                if ($itemA && $itemB) {
                    return $categoryName
                        ? "Contradictory answers in {$categoryName} between Q{$itemA} and Q{$itemB}"
                        : "Contradictory answers between Q{$itemA} and Q{$itemB}";
                }
                return $categoryName
                    ? "Contradictory answers in {$categoryName}"
                    : 'Contradictory answers';
            })(),
            default => (function () use ($categoryName) {
                if (!empty($this->attributes['description']) && !str_contains($this->attributes['description'], '_')) {
                    return ucfirst(trim($this->attributes['description']));
                }

                $humanType = ucfirst(str_replace(['_', '-'], ' ', $this->flag_type ?? 'Quality'));
                return $categoryName
                    ? "{$humanType} flag on {$categoryName}"
                    : "{$humanType} flag";
            })(),
        };
    }
}
