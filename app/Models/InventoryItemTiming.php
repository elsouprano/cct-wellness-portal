<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItemTiming extends Model
{
    protected $fillable = [
        'inventory_submission_id',
        'category',
        'item_number',
        'time_spent_ms',
    ];

    public function submission()
    {
        return $this->belongsTo(InventorySubmission::class, 'inventory_submission_id');
    }
}
