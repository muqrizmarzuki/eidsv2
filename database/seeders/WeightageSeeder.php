<?php

namespace Database\Seeders;

use App\Models\SamplingRule;
use App\Models\WeightageArchitecturalElement;
use App\Models\WeightageLocation;
use App\Models\WeightageOverall;
use Illuminate\Database\Seeder;

/**
 * Seeds the CIS 7:2021 reference tables (Tables 1, 2, 3, 4) as DB-backed,
 * admin-editable defaults. See docs/superpowers/specs/2026-08-05-cis7-scoring-and-checklist-redesign-design.md
 */
class WeightageSeeder extends Seeder
{
    public function run(): void
    {
        // Table 1 — overall weightage by building category.
        $overall = [
            'A' => ['architectural_pct' => 85, 'me_pct' => 2, 'external_pct' => 13],
            'B' => ['architectural_pct' => 83, 'me_pct' => 3, 'external_pct' => 14],
            'C' => ['architectural_pct' => 82, 'me_pct' => 4, 'external_pct' => 14],
            'D' => ['architectural_pct' => 80, 'me_pct' => 5, 'external_pct' => 15],
        ];
        foreach ($overall as $category => $data) {
            WeightageOverall::updateOrCreate(['building_category' => $category], $data);
        }

        // Table 4 — location-type weightage by building category (internal finishes only).
        $locations = [
            'A' => ['principal_pct' => 40, 'service_pct' => 40, 'circulation_pct' => 20],
            'B' => ['principal_pct' => 40, 'service_pct' => 40, 'circulation_pct' => 20],
            'C' => ['principal_pct' => 60, 'service_pct' => 15, 'circulation_pct' => 25],
            'D' => ['principal_pct' => 60, 'service_pct' => 15, 'circulation_pct' => 25],
        ];
        foreach ($locations as $category => $data) {
            WeightageLocation::updateOrCreate(['building_category' => $category], $data);
        }

        // Table 3 — sample count formula by building category.
        $sampling = [
            'A' => ['gfa_divisor' => 70,  'min_samples' => 30, 'max_samples' => 700],
            'B' => ['gfa_divisor' => 70,  'min_samples' => 30, 'max_samples' => 600],
            'C' => ['gfa_divisor' => 500, 'min_samples' => 30, 'max_samples' => 150],
            'D' => ['gfa_divisor' => 500, 'min_samples' => 30, 'max_samples' => 100],
        ];
        foreach ($sampling as $category => $data) {
            SamplingRule::updateOrCreate(['building_category' => $category], $data);
        }

        // Table 2 — architectural element weightage (fixed regardless of category).
        $elements = [
            ['A1_FLOOR',           'Floor (Lantai)',                'Internal finishes',            18, 'sample_average', false, 1],
            ['A2_WALL',            'Internal Wall (Dinding Dalam)', 'Internal finishes',            18, 'sample_average', false, 2],
            ['A3_CEILING',         'Ceiling (Siling)',              'Internal finishes',             8, 'sample_average', false, 3],
            ['A4_DOOR',            'Door (Pintu)',                  'Internal finishes',             8, 'sample_average', false, 4],
            ['A5_WINDOW',          'Window (Tingkap)',              'Internal finishes',             8, 'sample_average', false, 5],
            ['A6_FIXTURES',        'Internal Fixtures',             'Internal finishes',             8, 'sample_average', false, 6],
            ['A7_ROOF',            'Roof (Bumbung)',                'External finishes',            10, 'sample_average', false, 7],
            ['A8_EXT_WALL',        'External Wall (Dinding Luar)',  'External finishes',            10, 'sample_average', false, 8],
            ['A9_APRON_DRAIN',     'Apron and Perimeter Drain',     'External finishes',             3, 'sample_average', true,  9],
            ['A10_CAR_PARK',       'Car Park / Car Porch',          'External finishes',             3, 'sample_average', true,  10],
            ['QP_SKIM_COAT',       'Skim Coat or Prepacked Plaster','Material and functional test',  3, 'declaration',    false, 11],
            ['QP_WATER_TIGHTNESS', 'Wet-area Water-tightness Test', 'Material and functional test',  3, 'declaration',    false, 12],
        ];
        foreach ($elements as [$code, $name, $group, $pct, $mode, $optional, $sort]) {
            WeightageArchitecturalElement::updateOrCreate(
                ['component_code' => $code],
                ['name' => $name, 'group' => $group, 'breakdown_pct' => $pct,
                 'scoring_mode' => $mode, 'optional' => $optional, 'sort_order' => $sort]
            );
        }
    }
}
