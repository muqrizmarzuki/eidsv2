<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings   = Setting::orderBy('id')->get()->keyBy('key');
        $components = config('eids.components');
        $locations  = config('eids.default_locations');

        return view('settings.index', compact('settings', 'components', 'locations'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (Setting::defaults() as $row) {
            if ($row['type'] === 'json') continue;
            $rules["settings.{$row['key']}"] = $row['type'] === 'number'
                ? 'required|numeric|min:0'
                : 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        foreach ($validated['settings'] as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        // Handle locations JSON field
        $locations = array_values(array_filter(array_map('trim', $request->input('locations', []))));
        Setting::where('key', 'default_locations')->update(['value' => json_encode($locations)]);

        Setting::clearCache();

        return redirect()->route('settings.index')
            ->with('success', 'Settings saved successfully.');
    }
}
