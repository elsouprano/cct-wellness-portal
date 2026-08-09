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
}
