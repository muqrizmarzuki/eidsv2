<?php

namespace Database\Seeders;

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

        $admin = User::create([
            'name'        => 'Admin E-IDS',
            'email'       => 'admin@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'admin',
            'employee_id' => 'ADMIN-001',
        ]);

        $inspector = User::create([
            'name'        => 'Ahmad Inspector',
            'email'       => 'inspector@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'inspector',
            'employee_id' => 'INSP-2024-001',
        ]);

        User::create([
            'name'        => 'Ikhwan Azmi',
            'email'       => 'auditor@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'lead_auditor',
            'employee_id' => 'AUDT-2024-001',
        ]);

        User::create([
            'name'        => 'Siti Supervisor',
            'email'       => 'supervisor@eids.gov.my',
            'password'    => Hash::make('password'),
            'role'        => 'supervisor',
            'employee_id' => 'SUPV-2024-001',
        ]);

        $project = Project::create([
            'project_no'         => 'PRJ-2026-001',
            'project_name'       => 'Taman Merlimau Perdana',
            'location'           => 'Merlimau, Melaka',
            'developer_name'     => 'Mutiara Development Sdn Bhd',
            'contractor_name'    => 'Bina Jaya Construction Sdn Bhd',
            'building_type'      => 'teres',
            'total_units'        => 50,
            'floor_area_sqm'     => 210.00,
            'calculated_samples' => 4,
            'overall_score'      => 0.00,
            'status'             => 'dalam_pemeriksaan',
            'created_by'         => $inspector->id,
        ]);

        $locations = ['Living Room', 'Service Area', 'Passageway', 'Bedroom 1'];
        foreach ($locations as $index => $location) {
            ProjectSample::create([
                'project_id'    => $project->id,
                'sample_index'  => $index + 1,
                'location_name' => $location,
            ]);
        }
    }
}
