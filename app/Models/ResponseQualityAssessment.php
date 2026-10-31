<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseQualityAssessment extends Model
{
    protected $fillable = [
        'inventory_submission_id',
        'classification',
        'speed_status',
        'straight_line_status',
        'contradiction_status',
        'flag_reasons',
        'metrics',
        'version',
        'assessed_at',
    ];

    protected $casts = [
        'flag_reasons' => 'array',
        'metrics' => 'array',
        'assessed_at' => 'datetime',
        'version' => 'integer',
    ];

    public function submission()
    {
        return $this->belongsTo(InventorySubmission::class, 'inventory_submission_id');
    }

    public function isAcceptable(): bool
    {
        return $this->classification === 'ACCEPTABLE';
    }

    public function isForReview(): bool
    {
        return $this->classification === 'FOR REVIEW';
    }

    public function hasSpeedFlag(): bool
    {
        return $this->speed_status === 'flagged';
    }

    public function hasStraightLineFlag(): bool
    {
        return $this->straight_line_status === 'flagged';
    }

    public function hasContradictionFlag(): bool
    {
        return $this->contradiction_status === 'flagged';
    }
}

