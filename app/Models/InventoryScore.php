<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryScore extends Model
{
    protected $fillable = [
        'inventory_submission_id',
        'category_name',
        'subscale_name',
        'raw_score',
        'scaled_score',
        'severity_label',
        'severity_color',
    ];

    public function submission()
    {
        return $this->belongsTo(InventorySubmission::class, 'inventory_submission_id');
    }
}
