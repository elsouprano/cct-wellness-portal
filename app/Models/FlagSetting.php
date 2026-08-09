<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlagSetting extends Model
{
    protected $fillable = [
        'flag_type',
        'setting_key',
        'setting_value',
    ];
}
