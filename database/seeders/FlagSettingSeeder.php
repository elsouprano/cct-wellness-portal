<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FlagSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['flag_type' => 'speed', 'setting_key' => 'seconds_per_item_threshold', 'setting_value' => '1.5'],
            ['flag_type' => 'straight_line', 'setting_key' => 'percentage_threshold', 'setting_value' => '90'],
            ['flag_type' => 'contradiction', 'setting_key' => 'spread_percentage', 'setting_value' => '75'],
        ];

        foreach ($settings as $setting) {
            \App\Models\FlagSetting::updateOrCreate(
                ['flag_type' => $setting['flag_type'], 'setting_key' => $setting['setting_key']],
                ['setting_value' => $setting['setting_value']]
            );
        }
    }
}
