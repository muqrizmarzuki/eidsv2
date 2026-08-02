<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_no'         => 'PRJ-' . fake()->unique()->numerify('####'),
            'project_name'       => fake()->streetName() . ' Residence',
            'developer_name'     => fake()->company(),
            'contractor_name'    => fake()->company(),
            'building_type'      => 'teres',
            'total_units'        => 10,
            'floor_area_sqm'     => 100,
            'calculated_samples' => 2,
            'overall_score'      => 0,
            'status'             => 'dalam_pemeriksaan',
            'created_by'         => User::factory(),
        ];
    }
}
