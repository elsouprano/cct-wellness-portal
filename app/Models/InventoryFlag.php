<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryFlag extends Model
{
    protected $fillable = [
        'inventory_submission_id',
        'flag_type',
        'category',
        'subscale_tag',
        'details',
        'is_reviewed',
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
}
