<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalSample extends Model
{
    protected $fillable = ['project_id', 'element_code', 'sample_index', 'label'];

    protected $appends = ['pass_rate', 'location_name'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessments()
    {
        return $this->hasMany(ComponentAssessment::class, 'external_sample_id');
    }

    /**
     * Accessor aliases so the shared inspection Blade view (built for
     * ProjectSample) works unmodified for external units too.
     */
    public function getLocationNameAttribute(): string
    {
        return $this->label;
    }

    public function getPassRateAttribute(): ?float
    {
        if ($this->assessments->isEmpty()) return null;
        $pass  = $this->assessments->where('overall_sample_status', 'PASS')->count();
        $total = $this->assessments->count();
        return round(($pass / $total) * 100, 1);
    }
}
