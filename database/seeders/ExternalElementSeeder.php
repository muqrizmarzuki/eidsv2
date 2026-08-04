<?php

namespace Database\Seeders;

use App\Models\ExternalElement;
use Illuminate\Database\Seeder;

/**
 * Seeds Annex C's element registry (Table 6 sampling guideline, converted to a
 * fixed sample count per element rather than a GFA-based formula — see
 * docs/superpowers/specs/2026-08-05-cis7-scoring-and-checklist-redesign-design.md §6).
 * Infrastructure elements default present (expected on nearly every housing
 * project); Facilities/Amenities default absent (genuinely optional).
 */
class ExternalElementSeeder extends Seeder
{
    public function run(): void
    {
        $elements = [
            ['EXT_LINKWAY',    'Link-way / Shelter',              'Infrastructure',          true,  1, 1],
            ['EXT_DRAIN',      'External Drain',                  'Infrastructure',          true,  2, 2],
            ['EXT_ROADWORK',   'Roadwork (incl. Parking Bay)',    'Infrastructure',          true,  2, 3],
            ['EXT_FOOTPATH',   'Footpath and Turfing',            'Infrastructure',          true,  2, 4],
            ['EXT_FENCE_GATE', 'Fence and Gate',                  'Infrastructure',          true,  1, 5],
            ['EXT_PLAYGROUND', 'Playground',                      'Facilities or Amenities', false, 1, 6],
            ['EXT_COURT',      'Court (Sports)',                  'Facilities or Amenities', false, 1, 7],
            ['EXT_POOL',       'Swimming Pool',                   'Facilities or Amenities', false, 1, 8],
        ];

        foreach ($elements as [$code, $name, $group, $defaultPresent, $sampleCount, $sort]) {
            ExternalElement::updateOrCreate(
                ['element_code' => $code],
                ['name' => $name, 'group' => $group, 'default_present' => $defaultPresent,
                 'sample_count' => $sampleCount, 'sort_order' => $sort]
            );
        }
    }
}
