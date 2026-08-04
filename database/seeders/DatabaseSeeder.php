<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\ExternalElement;
use App\Models\ExternalSample;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\QpDeclaration;
use App\Models\Setting;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

        // ── Project 1: fully walked through, one defect in each of the 4 lifecycle states ──
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
            'floor_area_sqm'         => 210.00,
            'calculated_samples'     => 4,
            'status'                 => 'dalam_pemeriksaan',
            'created_by'             => $admin->id,
            'assigned_to'            => $inspectorA->id,
            'assigned_contractor_id' => $contractorA->id,
        ]);

        $p1Samples = [];
        foreach (['Living Room', 'Service Area', 'Passageway', 'Bedroom 1'] as $index => $location) {
            $p1Samples[] = ProjectSample::create([
                'project_id'    => $project1->id,
                'sample_index'  => $index + 1,
                'location_name' => $location,
                'location_type' => ProjectSample::guessLocationType($location),
            ]);
        }
        [$livingRoom, $serviceArea, $passageway, $bedroom1] = $p1Samples;
        // Bedroom 1 is deliberately left with zero assessments — shows "Pending Inspection"
        // and lets you demo Inspector A's "Continue — 3/4 sample units done" banner.

        $this->assessAllComponents($project1, $livingRoom, failOn: ['A2_WALL', 'A5_WINDOW']);
        $this->assessAllComponents($project1, $serviceArea, failOn: ['A1_FLOOR']);
        $this->assessAllComponents($project1, $passageway, failOn: ['A7_ROOF']);
        $this->declareQp($project1, skimCoat: true, waterTightness: false);

        $this->makeDefectFor($project1, $livingRoom, 'A2_WALL', 'Internal Wall (Dinding Dalam)', 'medium', 'OPEN');
        $this->makeDefectFor($project1, $livingRoom, 'A5_WINDOW', 'Window (Tingkap)', 'low', 'IN_PROGRESS');
        $this->makeDefectFor($project1, $serviceArea, 'A1_FLOOR', 'Floor (Lantai)', 'high', 'PENDING_VERIFICATION');
        $this->makeDefectFor($project1, $passageway, 'A7_ROOF', 'Roof (Bumbung)', 'medium', 'RESOLVED');
        $this->setupExternalWorks($project1, extraPresent: ['EXT_PLAYGROUND'], failOn: ['EXT_DRAIN']);
        app(ScoringService::class)->recalculateAndSave($project1);

        // ── Project 2: minimal, single-purpose — proves Inspector B/Contractor B
        // see ONLY this project, never Project 1. No car park on this project — a
        // live example of Table 2 weightage redistribution. ──
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
            'floor_area_sqm'         => 130.00,
            'calculated_samples'     => 3,
            'status'                 => 'dalam_pemeriksaan',
            'created_by'             => $admin->id,
            'assigned_to'            => $inspectorB->id,
            'assigned_contractor_id' => $contractorB->id,
        ]);

        $p2Samples = [];
        foreach (['Master Bedroom', 'Kitchen', 'Bathroom'] as $index => $location) {
            $p2Samples[] = ProjectSample::create([
                'project_id'    => $project2->id,
                'sample_index'  => $index + 1,
                'location_name' => $location,
                'location_type' => ProjectSample::guessLocationType($location),
            ]);
        }
        [$masterBedroom] = $p2Samples;
        // Kitchen and Bathroom are deliberately left unassessed.

        $this->assessAllComponents($project2, $masterBedroom, failOn: ['A4_DOOR']);
        $this->makeDefectFor($project2, $masterBedroom, 'A4_DOOR', 'Door (Pintu)', 'low', 'OPEN');
        $this->setupExternalWorks($project2);
        app(ScoringService::class)->recalculateAndSave($project2);

        // ── Project 3: fully signed off — the only seeded project that demonstrates
        // the unlocked score gauge, the certification-seal stepper button, and the
        // formal PDF certificate. Assigned to Inspector A / Contractor B so both
        // accounts have at least one project in every phase of the lifecycle. ──
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
            'floor_area_sqm'         => 180.00,
            'calculated_samples'     => 3,
            'status'                 => 'selesai',
            'created_by'             => $admin->id,
            'assigned_to'            => $inspectorA->id,
            'assigned_contractor_id' => $contractorB->id,
        ]);

        $p3Samples = [];
        foreach (['Living Room', 'Master Bedroom', 'Kitchen'] as $index => $location) {
            $p3Samples[] = ProjectSample::create([
                'project_id'    => $project3->id,
                'sample_index'  => $index + 1,
                'location_name' => $location,
                'location_type' => ProjectSample::guessLocationType($location),
            ]);
        }
        [$p3LivingRoom, $p3MasterBedroom, $p3Kitchen] = $p3Samples;

        // One historical defect per sample, all resolved — proves a completed
        // project can still have a full repair history without blocking sign-off.
        $this->assessAllComponents($project3, $p3LivingRoom, failOn: ['A3_CEILING']);
        $this->assessAllComponents($project3, $p3MasterBedroom, failOn: ['A6_FIXTURES']);
        $this->assessAllComponents($project3, $p3Kitchen);
        $this->declareQp($project3, skimCoat: true, waterTightness: true);

        $this->makeDefectFor($project3, $p3LivingRoom, 'A3_CEILING', 'Ceiling (Siling)', 'low', 'RESOLVED');
        $this->makeDefectFor($project3, $p3MasterBedroom, 'A6_FIXTURES', 'Internal Fixtures', 'low', 'RESOLVED');
        $this->setupExternalWorks($project3, extraPresent: ['EXT_PLAYGROUND', 'EXT_COURT', 'EXT_POOL']);

        app(ScoringService::class)->recalculateAndSave($project3);
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
     * Marks the default-present Infrastructure elements (plus any $extraPresent
     * Facilities/Amenities) present for a project, generates their Table 6
     * sample units, and assesses every checklist item PASS except the elements
     * listed in $failOn, whose first item is answered FAIL.
     */
    private function setupExternalWorks(Project $project, array $extraPresent = [], array $failOn = []): void
    {
        $registry = ExternalElement::ordered();
        $toEnable = $registry->filter(fn ($el) => $el->default_present || in_array($el->element_code, $extraPresent, true));

        foreach ($toEnable as $code => $element) {
            $project->externalElementSettings()->create(['element_code' => $code, 'present' => true]);

            $samples = [];
            for ($i = 0; $i < $element->sample_count; $i++) {
                $samples[] = ExternalSample::create([
                    'project_id'   => $project->id,
                    'element_code' => $code,
                    'sample_index' => $i + 1,
                    'label'        => "{$element->name} #" . ($i + 1),
                ]);
            }

            $shouldFail = in_array($code, $failOn, true);
            $items      = ChecklistItem::forComponent($code)->get();
            if ($items->isEmpty()) continue;

            foreach ($samples as $sample) {
                $assessment = ComponentAssessment::create([
                    'project_id'         => $project->id,
                    'external_sample_id' => $sample->id,
                    'component_code'     => $code,
                    'na'                 => false,
                ]);

                $anyFail = false;
                foreach ($items as $i => $item) {
                    $fail = $shouldFail && $i === 0;
                    $anyFail = $anyFail || $fail;
                    $assessment->answers()->create([
                        'checklist_item_id' => $item->id,
                        'result'            => $fail ? 'FAIL' : 'PASS',
                    ]);
                }

                $assessment->update(['overall_sample_status' => $anyFail ? 'FAIL' : 'PASS']);
            }
        }
    }

    private function declareQp(Project $project, bool $skimCoat, bool $waterTightness): void
    {
        QpDeclaration::create([
            'project_id' => $project->id, 'item_code' => 'QP_SKIM_COAT',
            'declared' => $skimCoat, 'evidence_path' => $skimCoat ? 'demo/qp-skim-coat.pdf' : null,
            'declared_at' => $skimCoat ? now() : null,
        ]);
        QpDeclaration::create([
            'project_id' => $project->id, 'item_code' => 'QP_WATER_TIGHTNESS',
            'declared' => $waterTightness, 'evidence_path' => $waterTightness ? 'demo/qp-water-tightness.pdf' : null,
            'declared_at' => $waterTightness ? now() : null,
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
