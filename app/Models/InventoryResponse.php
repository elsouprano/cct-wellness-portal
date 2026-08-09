<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryResponse extends Model
{
    protected $fillable = [
        'inventory_submission_id',
        'category',
        'item_number',
        'response_value',
    ];

    public function submission()
    {
        return $this->belongsTo(InventorySubmission::class, 'inventory_submission_id');
    }
}
