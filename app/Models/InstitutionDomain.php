<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'institution_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Always store domain in lower case without leading '@' or whitespace.
     */
    public function setDomainAttribute($value): void
    {
        $this->attributes['domain'] = strtolower(ltrim(trim((string) $value), '@'));
    }

    /**
     * Scope query to only active domains.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

