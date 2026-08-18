<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FlagSetting;
use Illuminate\Support\Facades\Cache;

class FlagSettingController extends Controller
{
    public function index()
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized access.');
        
        $settings = Cache::remember('flag_settings', 3600, fn() => FlagSetting::all());
        return view('staff.flag-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|numeric'
        ]);

        foreach ($validated['settings'] as $id => $value) {
            FlagSetting::where('id', $id)->update(['setting_value' => $value]);
        }

        Cache::forget('flag_settings');

        return redirect()->route('flag-settings.index')->with('success', 'Flag settings updated successfully.');
    }
}
