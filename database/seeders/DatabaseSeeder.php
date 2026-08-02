<?php

namespace Database\Seeders;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectSample;
use App\Models\Setting;
use App\Models\User;
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
            'total_units'            => 50,
            'floor_area_sqm'         => 210.00,
            'calculated_samples'     => 4,
            'overall_score'          => 84.80, // MODERATE (< 85) — mid-inspection, 1 of 4 samples still pending
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
            ]);
        }
        [$livingRoom, $serviceArea, $passageway, $bedroom1] = $p1Samples;
        // Bedroom 1 is deliberately left with zero assessments — shows "Pending Inspection"
        // and lets you demo Inspector A's "Continue — 3/4 sample units done" banner.

        $this->assessAllComponents($project1, $livingRoom, failOn: ['A2_WALL' => 'crack', 'A5_WINDOW' => 'hollow']);
        $this->assessAllComponents($project1, $serviceArea, failOn: ['A1_FLOOR' => 'levelling']);
        $this->assessAllComponents($project1, $passageway, failOn: ['A7_ROOF' => 'finishing']);

        $this->makeDefectFor($project1, $livingRoom, 'A2_WALL', 'Internal Wall (Dinding Dalam)', 'medium', 'OPEN');
        $this->makeDefectFor($project1, $livingRoom, 'A5_WINDOW', 'Window (Tingkap)', 'low', 'IN_PROGRESS');
        $this->makeDefectFor($project1, $serviceArea, 'A1_FLOOR', 'Floor (Lantai)', 'high', 'PENDING_VERIFICATION');
        $this->makeDefectFor($project1, $passageway, 'A7_ROOF', 'Roof (Bumbung)', 'medium', 'RESOLVED');

        // ── Project 2: minimal, single-purpose — proves Inspector B/Contractor B
        // see ONLY this project, never Project 1 ──
        $project2 = Project::create([
            'project_no'             => 'PRJ-2026-002',
            'project_name'           => 'Desa Aman Villa',
            'location'               => 'Alor Gajah, Melaka',
            'developer_name'         => 'Impian Setia Sdn Bhd',
            'contractor_name'        => 'Kukuh Sentosa Construction Sdn Bhd',
            'building_type'          => 'semi_d',
            'total_units'            => 20,
            'floor_area_sqm'         => 130.00,
            'calculated_samples'     => 3,
            'overall_score'          => 92.80, // GOOD — early days, only 1 of 3 samples assessed
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
            ]);
        }
        [$masterBedroom] = $p2Samples;
        // Kitchen and Bathroom are deliberately left unassessed.

        $this->assessAllComponents($project2, $masterBedroom, failOn: ['A4_DOOR' => 'crack']);
        $this->makeDefectFor($project2, $masterBedroom, 'A4_DOOR', 'Door (Pintu)', 'low', 'OPEN');

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
            ]);
        }
        [$p3LivingRoom, $p3MasterBedroom, $p3Kitchen] = $p3Samples;

        // One historical defect per sample, all resolved — proves a completed
        // project can still have a full repair history without blocking sign-off.
        $this->assessAllComponents($project3, $p3LivingRoom, failOn: ['A3_CEILING' => 'hollow']);
        $this->assessAllComponents($project3, $p3MasterBedroom, failOn: ['A6_FIXTURES' => 'finishing']);
        $this->assessAllComponents($project3, $p3Kitchen);

        $this->makeDefectFor($project3, $p3LivingRoom, 'A3_CEILING', 'Ceiling (Siling)', 'low', 'RESOLVED');
        $this->makeDefectFor($project3, $p3MasterBedroom, 'A6_FIXTURES', 'Internal Fixtures', 'low', 'RESOLVED');

        $project3->update(['overall_score' => $this->computeScore($project3)]);
    }

    /**
     * Recompute a project's overall_score from its actual assessments, using the
     * same S_arch + M&E + External formula as InspectionController::recalculateScore().
     */
    private function computeScore(Project $project): float
    {
        $project->load('assessments');
        $sArch = 0;
        foreach (config('eids.components') as $code => $cfg) {
            $assessments = $project->assessments->where('component_code', $code);
            $total = $assessments->count();
            if ($total === 0) continue;
            $pass = $assessments->where('overall_sample_status', 'PASS')->count();
            $sArch += ($pass / $total) * $cfg['weightage'];
        }
        $total = $sArch + (float) setting('me_score', 2.0) + (float) setting('external_score', 11.8);
        return round($total, 2);
    }

    /**
     * Assess all 8 components for one sample. $failOn maps component_code => which
     * sub-check to fail ('finishing'|'hollow'|'levelling'|'joint'|'crack'); every
     * other component and every other sub-check on a failing component passes.
     */
    private function assessAllComponents(Project $project, ProjectSample $sample, array $failOn = []): void
    {
        foreach (config('eids.components') as $code => $cfg) {
            $fail = $failOn[$code] ?? null;

            $levellingMm = $fail === 'levelling' ? 4.5 : 1.2;
            $jointMm     = $fail === 'joint' ? 2.0 : 0.5;

            $finishing = $fail === 'finishing' ? 'FAIL' : 'PASS';
            $hollow    = $fail === 'hollow' ? 'FAIL' : 'PASS';
            $crack     = $fail === 'crack' ? 'FAIL' : 'PASS';
            $levelling = $levellingMm > (float) setting('levelling_max_mm', 3.0) ? 'FAIL' : 'PASS';
            $joint     = $jointMm > (float) setting('joint_max_mm', 1.0) ? 'FAIL' : 'PASS';

            $overall = in_array('FAIL', [$finishing, $hollow, $levelling, $joint, $crack], true) ? 'FAIL' : 'PASS';

            ComponentAssessment::create([
                'project_id'            => $project->id,
                'sample_id'             => $sample->id,
                'component_code'        => $code,
                'component_name'        => $cfg['name'],
                'weightage'             => $cfg['weightage'],
                'finishing_status'      => $finishing,
                'hollow_status'         => $hollow,
                'levelling_mm'          => $levellingMm,
                'levelling_status'      => $levelling,
                'joint_mm'              => $jointMm,
                'joint_status'          => $joint,
                'crack_status'          => $crack,
                'overall_sample_status' => $overall,
                'remarks'               => $fail ? ucfirst($fail) . ' issue observed during inspection.' : null,
            ]);
        }
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
