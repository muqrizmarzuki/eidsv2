<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSample extends Model
{
    protected $fillable = [
        'project_id', 'sample_index', 'location_name',
    ];

    protected $appends = ['pass_rate'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessments()
    {
        return $this->hasMany(ComponentAssessment::class, 'sample_id');
    }

    public function getPassRateAttribute(): ?float
    {
        if ($this->assessments->isEmpty()) return null;
        $pass  = $this->assessments->where('overall_sample_status', 'PASS')->count();
        $total = $this->assessments->count();
        return round(($pass / $total) * 100, 1);
    }
}
