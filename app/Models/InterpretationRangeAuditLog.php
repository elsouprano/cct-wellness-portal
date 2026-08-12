<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterpretationRangeAuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'action',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
