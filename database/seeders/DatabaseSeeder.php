<?php

namespace Database\Seeders;

use App\Models\ArchExternalSample;
use App\Models\AssessmentAnswer;
use App\Models\ChecklistItem;
use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\ExternalElement;
use App\Models\ExternalSample;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\Setting;
use App\Models\User;
use App\Models\WeightageArchitecturalElement;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Laravel's query log records every query's SQL + bindings in memory by
        // default — harmless at normal request scale, but with a 150-sample
        // project generating tens of thousands of inserts, it's what actually
        // exhausts PHP's memory limit, not the data itself.
        \DB::connection()->disableQueryLog();

        // Seed default settings
        foreach (Setting::defaults() as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }

        $this->call([WeightageSeeder::class, ExternalElementSeeder::class, ChecklistItemSeeder::class]);

        // ── Users: Admin, plus one Inspector/Contractor pair, so role-scoped
        // visibility is easy to demo (log in as "A" accounts, confirm you only see
        // Project 1; log in as "B" accounts, confirm you only see Project 2)
        $admin = User::create([
            'name' => 'Admin E-IDS', 'email' => 'admin@eids.gov.my',
            'password' => Hash::make('password'), 'role' => 'admin', 'employee_id' => 'ADMIN-001',
        ]);

        $inspectorA = User::create([
            'name' => 'Ahmad Firdaus', 'email' => 'inspector.a@eids.gov.my',
            'password' => Hash::make('password'), 'role' => 'inspector', 'employee_id' => 'INSP-2024-001',
        ]);
        $inspectorB = User::create([
            'name' => 'Farah Aziz', 'email' => 'inspector.b@eids.gov.my',
            'password' => Hash::make('password'), 'role' => 'inspector', 'employee_id' => 'INSP-2024-002',
        ]);

        $contractorA = User::create([
            'name' => 'Bina Jaya QC Team', 'email' => 'contractor.a@eids.gov.my',
            'password' => Hash::make('password'), 'role' => 'contractor', 'employee_id' => 'CONT-2024-001',
        ]);
        $contractorB = User::create([
            'name' => 'Kukuh Sentosa QC Team', 'email' => 'contractor.b@eids.gov.my',
            'password' => Hash::make('password'), 'role' => 'contractor', 'employee_id' => 'CONT-2024-002',
        ]);

        $scoring = app(ScoringService::class);

        // ── Project 1: 50-unit scheme — a realistic total-project GFA (50 x ~210
        // m²) so Table 3 correctly demands 150 samples, not a token handful.
        // The first 4 (Living Room/Service Area/Passageway/Bedroom 1, all in
        // Unit #1) carry the hand-authored defect narrative; the rest are bulk
        // seeded PASS so the project's score reflects the full, correct sample set. ──
        $project1 = Project::create([
            'project_no'             => 'PRJ-2026-001',
            'project_name'           => 'Taman Merlimau Perdana',
            'location'               => 'Merlimau, Melaka',
            'developer_name'         => 'Mutiara Development Sdn Bhd',
            'contractor_name'        => 'Bina Jaya Construction Sdn Bhd',
            'building_type'          => 'teres',
            'building_category'      => 'A',
            'car_park_present'       => true,
            'apron_drain_present'    => true,
            'total_units'            => 50,
            'floor_area_sqm'         => 50 * 210.00, // total project GFA, not one unit's size
            'status'                 => 'dalam_pemeriksaan',
            'created_by'             => $admin->id,
            'assigned_to'            => $inspectorA->id,
            'assigned_contractor_id' => $contractorA->id,
        ]);
        $project1->update(['calculated_samples' => $scoring->sampleCount('A', (float) $project1->floor_area_sqm)]);

        $p1Samples = $this->generateSamples($project1);
        [$livingRoom, $serviceArea, $passageway, $bedroom1] = $p1Samples->take(4)->values();
        // Bedroom 1 is deliberately left with zero assessments — shows "Pending Inspection"
        // and lets you demo Inspector A's "Continue — x/N sample units done" banner.

        $this->assessAllComponents($project1, $livingRoom, failOn: ['A2_WALL', 'A5_WINDOW']);
        $this->assessAllComponents($project1, $serviceArea, failOn: ['A1_FLOOR']);
        $this->assessAllComponents($project1, $passageway);
        $this->bulkPassRemaining($project1, $p1Samples->skip(4)->reject(fn ($s) => $s->id === $bedroom1->id));

        $this->makeDefectFor($project1, $livingRoom, 'A2_WALL', 'Internal Wall (Dinding Dalam)', 'medium', 'OPEN');
        $this->makeDefectFor($project1, $livingRoom, 'A5_WINDOW', 'Window (Tingkap)', 'low', 'IN_PROGRESS');
        $this->makeDefectFor($project1, $serviceArea, 'A1_FLOOR', 'Floor (Lantai)', 'high', 'PENDING_VERIFICATION');

        // Roof/External Wall/Apron/Car Park are Table 3's "building" sampling
        // scope — their own sections, never a room. The Roof defect narrative
        // moves here instead of a room sample.
        $roofSample = $this->setupBuildingExternal($project1, failOn: ['A7_ROOF']);
        $this->makeDefectForArchSample($project1, $roofSample, 'A7_ROOF', 'Roof (Bumbung)', 'medium', 'RESOLVED');

        // 2 playgrounds on this project — demonstrates an element with
        // multiple physical instances, each independently inspected.
        $this->setupExternalWorks($project1, quantities: ['EXT_PLAYGROUND' => 2], failOn: ['EXT_DRAIN']);
        $scoring->recalculateAndSave($project1);

        // ── Project 2: 20-unit scheme, no car park — a live example of Table 2
        // weightage redistribution at a smaller, still formula-correct scale. ──
        $project2 = Project::create([
            'project_no'             => 'PRJ-2026-002',
            'project_name'           => 'Desa Aman Villa',
            'location'               => 'Alor Gajah, Melaka',
            'developer_name'         => 'Impian Setia Sdn Bhd',
            'contractor_name'        => 'Kukuh Sentosa Construction Sdn Bhd',
            'building_type'          => 'semi_d',
            'building_category'      => 'A',
            'car_park_present'       => false,
            'apron_drain_present'    => true,
            'total_units'            => 20,
            'floor_area_sqm'         => 20 * 130.00,
            'status'                 => 'dalam_pemeriksaan',
            'created_by'             => $admin->id,
            'assigned_to'            => $inspectorB->id,
            'assigned_contractor_id' => $contractorB->id,
        ]);
        $project2->update(['calculated_samples' => $scoring->sampleCount('A', (float) $project2->floor_area_sqm)]);

        $p2Samples = $this->generateSamples($project2);
        [$masterBedroom] = $p2Samples->take(1)->values();
        // Every other sample is deliberately left unassessed — shows an
        // early-stage, mostly-pending inspection.

        $this->assessAllComponents($project2, $masterBedroom, failOn: ['A4_DOOR']);
        $this->makeDefectFor($project2, $masterBedroom, 'A4_DOOR', 'Door (Pintu)', 'low', 'OPEN');
        $this->setupBuildingExternal($project2);
        $this->setupExternalWorks($project2);
        $scoring->recalculateAndSave($project2);

        // ── Project 3: 12-unit scheme, fully signed off — the only seeded
        // project that demonstrates the unlocked score gauge, the
        // certification-seal stepper button, and the formal PDF certificate. ──
        $project3 = Project::create([
            'project_no'             => 'PRJ-2026-003',
            'project_name'           => 'Kota Laksamana Heights',
            'location'               => 'Bandar Hilir, Melaka',
            'developer_name'         => 'Warisan Bina Sdn Bhd',
            'contractor_name'        => 'Kukuh Sentosa Construction Sdn Bhd',
            'building_type'          => 'banglo',
            'building_category'      => 'A',
            'car_park_present'       => true,
            'apron_drain_present'    => true,
            'total_units'            => 12,
            'floor_area_sqm'         => 12 * 180.00,
            'status'                 => 'selesai',
            'created_by'             => $admin->id,
            'assigned_to'            => $inspectorA->id,
            'assigned_contractor_id' => $contractorB->id,
        ]);
        $project3->update(['calculated_samples' => $scoring->sampleCount('A', (float) $project3->floor_area_sqm)]);

        $p3Samples = $this->generateSamples($project3);
        [$p3LivingRoom, $p3MasterBedroom, $p3Kitchen] = $p3Samples->take(3)->values();

        // One historical defect per sample, all resolved — proves a completed
        // project can still have a full repair history without blocking sign-off.
        $this->assessAllComponents($project3, $p3LivingRoom, failOn: ['A3_CEILING']);
        $this->assessAllComponents($project3, $p3MasterBedroom, failOn: ['A6_FIXTURES']);
        $this->assessAllComponents($project3, $p3Kitchen);
        $this->bulkPassRemaining($project3, $p3Samples->skip(3));

        $this->makeDefectFor($project3, $p3LivingRoom, 'A3_CEILING', 'Ceiling (Siling)', 'low', 'RESOLVED');
        $this->makeDefectFor($project3, $p3MasterBedroom, 'A6_FIXTURES', 'Internal Fixtures', 'low', 'RESOLVED');
        $this->setupBuildingExternal($project3);
        $this->setupExternalWorks($project3, quantities: ['EXT_PLAYGROUND' => 1, 'EXT_COURT' => 1, 'EXT_POOL' => 1]);

        $scoring->recalculateAndSave($project3);
    }

    /**
     * Generates a project's full sample set, distributed across its units per
     * CIS 7:2021 §1.7 (ProjectSample::distributeAcrossUnits), and returns the
     * created rows in plan order.
     */
    private function generateSamples(Project $project): \Illuminate\Support\Collection
    {
        $locations = json_decode(setting('default_locations', '[]'), true) ?: config('eids.default_locations');
        $plan      = ProjectSample::distributeAcrossUnits($project->calculated_samples, $locations, $project->total_units);

        $rows = [];
        foreach ($plan as $i => $entry) {
            $rows[] = [
                'project_id'     => $project->id,
                'unit_reference' => $entry['unit_reference'],
                'sample_index'   => $i + 1,
                'location_name'  => $entry['location_name'],
                'location_type'  => ProjectSample::guessLocationType($entry['location_name']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }
        ProjectSample::insert($rows);

        return ProjectSample::where('project_id', $project->id)->orderBy('sample_index')->get();
    }

    /**
     * Assess every active architectural component plus M&E Fittings for one
     * sample, answering every checklist item PASS except the components listed
     * in $failOn, whose first item is answered FAIL (numeric items get a value
     * over tolerance).
     */
    private function assessAllComponents(Project $project, ProjectSample $sample, array $failOn = []): void
    {
        foreach ($project->inspectableComponentCodes() as $code) {
            $items = ChecklistItem::forComponent($code)->get();
            if ($items->isEmpty()) continue;

            $shouldFail = in_array($code, $failOn, true);

            $assessment = ComponentAssessment::create([
                'project_id'     => $project->id,
                'sample_id'      => $sample->id,
                'component_code' => $code,
                'na'             => false,
                'remarks'        => $shouldFail ? 'Defect observed during inspection.' : null,
            ]);

            $anyFail = false;
            foreach ($items as $i => $item) {
                $fail = $shouldFail && $i === 0;
                $anyFail = $anyFail || $fail;

                if ($item->input_type === 'numeric_with_tolerance') {
                    $max   = (float) ($item->tolerance_max_mm ?? 3.0);
                    $value = $fail ? $max + 2.0 : round($max * 0.4, 2);
                    $assessment->answers()->create([
                        'checklist_item_id' => $item->id,
                        'numeric_value'     => $value,
                        'result'            => $value <= $max ? 'PASS' : 'FAIL',
                    ]);
                } else {
                    $assessment->answers()->create([
                        'checklist_item_id' => $item->id,
                        'result'            => $fail ? 'FAIL' : 'PASS',
                    ]);
                }
            }

            $assessment->update(['overall_sample_status' => $anyFail ? 'FAIL' : 'PASS']);
        }
    }

    /**
     * Bulk-seeds a full PASS assessment (every checklist item, every active
     * component + M&E) for a set of samples, via batched inserts rather than
     * one Eloquent create() per answer — needed now that a realistic project
     * can have 30-700 samples (Table 3), not just a handful.
     */
    private function bulkPassRemaining(Project $project, \Illuminate\Support\Collection $samples): void
    {
        if ($samples->isEmpty()) return;

        $codes       = $project->inspectableComponentCodes();
        $itemsByCode = collect($codes)->mapWithKeys(fn ($c) => [$c => ChecklistItem::forComponent($c)->get()]);

        $assessmentRows = [];
        foreach ($samples as $sample) {
            foreach ($codes as $code) {
                if ($itemsByCode[$code]->isEmpty()) continue;
                $assessmentRows[] = [
                    'project_id' => $project->id, 'sample_id' => $sample->id, 'external_sample_id' => null,
                    'component_code' => $code, 'na' => false, 'overall_sample_status' => 'PASS',
                    'photo_path' => null, 'remarks' => null, 'created_at' => now(), 'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($assessmentRows, 1000) as $chunk) {
            ComponentAssessment::insert($chunk);
        }

        $assessments = ComponentAssessment::where('project_id', $project->id)
            ->whereIn('sample_id', $samples->pluck('id'))->get();

        $answerRows = [];
        foreach ($assessments as $assessment) {
            foreach ($itemsByCode[$assessment->component_code] as $item) {
                $answerRows[] = [
                    'component_assessment_id' => $assessment->id,
                    'checklist_item_id'       => $item->id,
                    'result'                  => 'PASS',
                    'numeric_value'           => $item->input_type === 'numeric_with_tolerance'
                        ? round((float) ($item->tolerance_max_mm ?? 3.0) * 0.4, 2) : null,
                    'remarks'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($answerRows, 1000) as $chunk) {
            AssessmentAnswer::insert($chunk);
        }
    }

    /**
     * Marks the default-present Infrastructure elements (plus any $extraPresent
     * Facilities/Amenities) present for a project, generates their Table 6
     * sample units, and assesses every checklist item PASS except the elements
     * listed in $failOn, whose first item is answered FAIL.
     */
    /**
     * Sets each External Works element's quantity (defaulting to 1 for
     * Infrastructure elements, 0 otherwise, per ExternalElement::default_present
     * — overridable via $quantities, e.g. ['EXT_PLAYGROUND' => 2] to demo a
     * project with multiple playgrounds), generates every instance's Table 6
     * sample sections, and assesses every checklist item PASS except the
     * elements in $failOn, whose first instance's first item is answered FAIL.
     */
    private function setupExternalWorks(Project $project, array $quantities = [], array $failOn = []): void
    {
        $registry = ExternalElement::ordered();

        foreach ($registry as $code => $element) {
            $quantity = $quantities[$code] ?? ($element->default_present ? 1 : 0);
            $project->externalElementSettings()->create(['element_code' => $code, 'quantity' => $quantity]);
            if ($quantity < 1) continue;

            $items = ChecklistItem::forComponent($code)->get();
            if ($items->isEmpty()) continue;

            $shouldFail = in_array($code, $failOn, true);

            for ($instance = 1; $instance <= $quantity; $instance++) {
                for ($section = 1; $section <= $element->sample_count; $section++) {
                    $label = $quantity > 1
                        ? ($element->sample_count > 1 ? "{$element->name} {$instance} - Section {$section}" : "{$element->name} {$instance}")
                        : ($element->sample_count > 1 ? "{$element->name} #{$section}" : $element->name);

                    $sample = ExternalSample::create([
                        'project_id'     => $project->id,
                        'element_code'   => $code,
                        'instance_index' => $instance,
                        'sample_index'   => $section,
                        'label'          => $label,
                    ]);

                    $fail = $shouldFail && $instance === 1 && $section === 1;

                    $assessment = ComponentAssessment::create([
                        'project_id'         => $project->id,
                        'external_sample_id' => $sample->id,
                        'component_code'     => $code,
                        'na'                 => false,
                    ]);

                    $anyFail = false;
                    foreach ($items as $i => $item) {
                        $itemFail = $fail && $i === 0;
                        $anyFail  = $anyFail || $itemFail;
                        $assessment->answers()->create([
                            'checklist_item_id' => $item->id,
                            'result'            => $itemFail ? 'FAIL' : 'PASS',
                        ]);
                    }

                    $assessment->update(['overall_sample_status' => $anyFail ? 'FAIL' : 'PASS']);
                }
            }
        }
    }

    /**
     * Generates and assesses the building-level sample sections for Roof,
     * External Wall, Apron/Drain and Car Park (Table 3's "building" sampling
     * scope, whichever of these are present on the project) — every checklist
     * item PASS except the components in $failOn, whose first section's first
     * item is answered FAIL. Returns the failing component's first sample (if
     * any) so the caller can attach a defect narrative to it.
     */
    private function setupBuildingExternal(Project $project, array $failOn = []): ?ArchExternalSample
    {
        $registry     = WeightageArchitecturalElement::ordered();
        $failedSample = null;

        foreach ($project->buildingBasedComponentCodes() as $code) {
            $count = $project->buildingSampleCountFor($code);
            $el    = $registry[$code];
            $items = ChecklistItem::forComponent($code)->get();
            if ($items->isEmpty()) continue;

            $shouldFail = in_array($code, $failOn, true);

            for ($i = 0; $i < $count; $i++) {
                $sample = ArchExternalSample::create([
                    'project_id'     => $project->id,
                    'component_code' => $code,
                    'sample_index'   => $i + 1,
                    'label'          => "{$el->name} - Section " . ($i + 1),
                ]);

                $fail = $shouldFail && $i === 0;
                if ($fail) $failedSample = $sample;

                $assessment = ComponentAssessment::create([
                    'project_id'     => $project->id,
                    'arch_sample_id' => $sample->id,
                    'component_code' => $code,
                    'na'             => false,
                ]);

                $anyFail = false;
                foreach ($items as $j => $item) {
                    $itemFail = $fail && $j === 0;
                    $anyFail  = $anyFail || $itemFail;
                    $assessment->answers()->create([
                        'checklist_item_id' => $item->id,
                        'result'            => $itemFail ? 'FAIL' : 'PASS',
                    ]);
                }

                $assessment->update(['overall_sample_status' => $anyFail ? 'FAIL' : 'PASS']);
            }
        }

        return $failedSample;
    }

    private function makeDefectForArchSample(Project $project, ?ArchExternalSample $sample, string $componentCode, string $componentName, string $severity, string $status): ?Defect
    {
        if (!$sample) return null;

        $assessment = ComponentAssessment::where([
            'project_id'     => $project->id,
            'arch_sample_id' => $sample->id,
            'component_code' => $componentCode,
        ])->firstOrFail();

        return Defect::create([
            'project_id'         => $project->id,
            'assessment_id'      => $assessment->id,
            'component_name'     => $componentName,
            'location'           => $sample->label,
            'defect_description' => "FAIL on {$componentCode} ({$componentName}) at {$sample->label}.",
            'severity'           => $severity,
            'status'             => $status,
        ]);
    }

    private function makeDefectFor(Project $project, ProjectSample $sample, string $componentCode, string $componentName, string $severity, string $status): Defect
    {
        $assessment = ComponentAssessment::where([
            'project_id'     => $project->id,
            'sample_id'      => $sample->id,
            'component_code' => $componentCode,
        ])->firstOrFail();

        return Defect::create([
            'project_id'         => $project->id,
            'assessment_id'      => $assessment->id,
            'component_name'     => $componentName,
            'location'           => $sample->location_name,
            'defect_description' => "FAIL on {$componentCode} ({$componentName}) at {$sample->location_name}.",
            'severity'           => $severity,
            'status'             => $status,
        ]);
    }
}
