<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentInformationSheet extends Model
{
    protected $fillable = [
        'user_id',
        'academic_year',
        'data',
        'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
