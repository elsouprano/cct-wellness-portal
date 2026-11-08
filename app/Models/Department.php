<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'archived_at'];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function counselors()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
