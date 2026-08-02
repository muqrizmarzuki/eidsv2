<?php

namespace Database\Factories;

use App\Models\Defect;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Defect>
 */
class DefectFactory extends Factory
{
    protected $model = Defect::class;

    public function definition(): array
    {
        return [
            'project_id'         => Project::factory(),
            'component_name'     => 'D1',
            'location'           => 'Living Room',
            'defect_description' => 'Sample defect',
            'severity'           => 'low',
            'status'             => 'OPEN',
        ];
    }
}
