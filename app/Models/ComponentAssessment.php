<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentAssessment extends Model
{
    protected $fillable = [
        'project_id', 'sample_id', 'component_code', 'component_name', 'weightage',
        'finishing_status', 'hollow_status',
        'levelling_mm', 'levelling_status',
        'joint_mm', 'joint_status',
        'crack_status', 'overall_sample_status',
        'photo_path', 'remarks',
    ];

    protected $casts = [
        'levelling_mm' => 'decimal:2',
        'joint_mm'     => 'decimal:2',
        'weightage'    => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sample()
    {
        return $this->belongsTo(ProjectSample::class, 'sample_id');
    }

    public function defect()
    {
        return $this->hasOne(Defect::class, 'assessment_id');
    }
}
