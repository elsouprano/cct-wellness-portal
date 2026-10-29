<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPromotionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'file_name',
        'rows_applied',
        'rows_skipped',
        'invalid_rows_data',
    ];

    protected function casts(): array
    {
        return [
            'invalid_rows_data' => 'array',
            'rows_applied' => 'integer',
            'rows_skipped' => 'integer',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
