<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait Archivable
{
    /**
     * Determine if the model instance is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Determine if the model instance is active (not archived).
     */
    public function isActive(): bool
    {
        return is_null($this->archived_at);
    }

    /**
     * Scope a query to only include active (non-archived) records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.archived_at');
    }

    /**
     * Scope a query to only include archived records.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull($this->getTable() . '.archived_at');
    }

    /**
     * Soft-archive the model instance.
     */
    public function archive(?int $userId = null): bool
    {
        $this->archived_at = now();
        $this->archived_by = $userId ?? auth()->id();
        return $this->save();
    }

    /**
     * Restore / unarchive the model instance.
     */
    public function unarchive(): bool
    {
        $this->archived_at = null;
        $this->archived_by = null;
        return $this->save();
    }

    /**
     * Get the user who archived this model instance.
     */
    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}

