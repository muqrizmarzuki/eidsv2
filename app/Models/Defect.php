<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    protected $fillable = [
        'project_id', 'assessment_id', 'component_name', 'location',
        'defect_description', 'photo_path', 'severity', 'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessment()
    {
        return $this->belongsTo(ComponentAssessment::class, 'assessment_id');
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            default  => $this->severity,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'OPEN'        => 'bg-red-100 text-red-700',
            'IN_PROGRESS' => 'bg-amber-100 text-amber-700',
            'RESOLVED'    => 'bg-emerald-100 text-emerald-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
