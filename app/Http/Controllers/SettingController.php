<?php

namespace App\Http\Controllers;

use App\Models\SamplingRule;
use App\Models\Setting;
use App\Models\WeightageArchitecturalElement;
use App\Models\WeightageLocation;
use App\Models\WeightageOverall;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings         = Setting::orderBy('id')->get()->keyBy('key');
        $locations        = config('eids.default_locations');
        $components       = WeightageArchitecturalElement::ordered();
        $overallWeights   = WeightageOverall::orderBy('building_category')->get();
        $locationWeights  = WeightageLocation::orderBy('building_category')->get();
        $samplingRules    = SamplingRule::orderBy('building_category')->get();

        return view('settings.index', compact(
            'settings', 'components', 'locations',
            'overallWeights', 'locationWeights', 'samplingRules'
        ));
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

    /**
     * Admin editing of the CIS 7:2021 reference tables (Tables 1, 2, 4, sampling
     * rules) — DB-backed defaults from WeightageSeeder, editable here per §1/§2
     * of the design spec.
     */
    public function updateWeightage(Request $request)
    {
        $data = $request->validate([
            'overall'                    => 'required|array',
            'overall.*.architectural_pct' => 'required|numeric|min:0|max:100',
            'overall.*.me_pct'            => 'required|numeric|min:0|max:100',
            'overall.*.external_pct'      => 'required|numeric|min:0|max:100',

            'locations'                       => 'required|array',
            'locations.*.principal_pct'       => 'required|numeric|min:0|max:100',
            'locations.*.service_pct'         => 'required|numeric|min:0|max:100',
            'locations.*.circulation_pct'     => 'required|numeric|min:0|max:100',

            'sampling'                    => 'required|array',
            'sampling.*.gfa_divisor'      => 'required|numeric|min:1',
            'sampling.*.min_samples'      => 'required|integer|min:1',
            'sampling.*.max_samples'      => 'required|integer|min:1',

            'elements'                    => 'required|array',
            'elements.*.breakdown_pct'    => 'required|numeric|min:0|max:100',
        ]);

        foreach ($data['overall'] as $category => $row) {
            WeightageOverall::where('building_category', $category)->update($row);
        }
        foreach ($data['locations'] as $category => $row) {
            WeightageLocation::where('building_category', $category)->update($row);
        }
        foreach ($data['sampling'] as $category => $row) {
            SamplingRule::where('building_category', $category)->update($row);
        }
        foreach ($data['elements'] as $code => $row) {
            WeightageArchitecturalElement::where('component_code', $code)->update($row);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Weightage & sampling tables saved successfully.');
    }
}
