<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FlagSetting;
use Illuminate\Support\Facades\Cache;

class FlagSettingController extends Controller
{
    /**
     * Standard established defaults for rule-based detection.
     */
    public const DEFAULTS = [
        'speed' => [
            'seconds_per_item_threshold' => '1.5',
        ],
        'straight_line' => [
            'percentage_threshold' => '90',
        ],
        'contradiction' => [
            'spread_percentage' => '75',
        ],
    ];

    public function index()
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        $settings = Cache::remember('flag_settings', 3600, fn() => FlagSetting::all());

        // Ensure default settings exist in DB if empty
        if ($settings->isEmpty()) {
            foreach (self::DEFAULTS as $type => $keys) {
                foreach ($keys as $key => $val) {
                    FlagSetting::firstOrCreate(
                        ['flag_type' => $type, 'setting_key' => $key],
                        ['setting_value' => $val]
                    );
                }
            }
            Cache::forget('flag_settings');
            Cache::forget('flag_settings_mapped');
            $settings = FlagSetting::all();
        }

        $methodDefinitions = [
            'speed' => [
                'title' => 'Unusually Fast Response Detection',
                'description' => 'Detects student submissions completed with an average time per item below reading feasibility. Submissions without reliable timing data are safely identified without penalty.',
                'default' => '1.50 seconds per question',
                'key' => 'seconds_per_item_threshold',
                'unit' => 'seconds / question',
                'min' => '0.5',
                'max' => '10.0',
                'step' => '0.1',
            ],
            'straight_line' => [
                'title' => 'Straightlining Detection',
                'description' => 'Detects respondents who repeatedly select identical answers across applicable question rating batteries (minimum 5 items). Non-rating demographic questions are safely excluded.',
                'default' => '90% identical answers',
                'key' => 'percentage_threshold',
                'unit' => '%',
                'min' => '50',
                'max' => '100',
                'step' => '1',
            ],
            'contradiction' => [
                'title' => 'Contradictory Answer Detection',
                'description' => 'Detects logically conflicting or reverse-coded responses using explicitly documented question relationships in the Question Bank. Unpaired questions are never guessed.',
                'default' => '75% deviation spread',
                'key' => 'spread_percentage',
                'unit' => '%',
                'min' => '30',
                'max' => '100',
                'step' => '1',
            ],
        ];

        return view('staff.flag-settings.index', compact('settings', 'methodDefinitions'));
    }

    public function update(Request $request)
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|numeric|min:0.1'
        ]);

        foreach ($validated['settings'] as $id => $value) {
            FlagSetting::where('id', $id)->update(['setting_value' => (string) $value]);
        }

        Cache::forget('flag_settings');
        Cache::forget('flag_settings_mapped');

        return redirect()->route('flag-settings.index')->with('success', 'Sensitivity thresholds updated successfully.');
    }

    public function resetDefaults()
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        foreach (self::DEFAULTS as $type => $keys) {
            foreach ($keys as $key => $val) {
                FlagSetting::updateOrCreate(
                    ['flag_type' => $type, 'setting_key' => $key],
                    ['setting_value' => $val]
                );
            }
        }

        Cache::forget('flag_settings');
        Cache::forget('flag_settings_mapped');

        return redirect()->route('flag-settings.index')->with('success', 'Thresholds have been reset to established system defaults.');
    }
}
