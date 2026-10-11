<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearLevelAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'action',
        'old_year_level',
        'new_year_level',
        'old_section',
        'new_section'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
