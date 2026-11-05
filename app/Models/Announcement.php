<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    public const DEFAULT_EXPIRATION_DAYS = 14;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'attachment_path',
        'attachment_original_name',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include announcements active on student home.
     */
    public function scopeCurrent($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->where('expires_at', '>', now())
                  ->orWhere(function ($sub) {
                      $sub->whereNull('expires_at')
                          ->where('published_at', '>=', now()->subDays(self::DEFAULT_EXPIRATION_DAYS));
                  });
            });
    }

    /**
     * Determine if a published announcement is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->published_at || $this->published_at > now()) {
            return false;
        }

        if ($this->expires_at) {
            return $this->expires_at <= now();
        }

        return $this->published_at < now()->subDays(self::DEFAULT_EXPIRATION_DAYS);
    }
}
