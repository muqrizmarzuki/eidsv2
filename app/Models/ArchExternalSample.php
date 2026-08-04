<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A building-level sample unit for Table 2's "External finishes" architectural
 * components (Roof, External Wall, Apron & Perimeter Drain, Car Park) — these
 * are sampled per Table 3 as building sections/lengths, never tied to a
 * specific internal room, unlike Floor/Wall/Ceiling/etc.
 */
class ArchExternalSample extends Model
{
    protected $fillable = ['project_id', 'component_code', 'sample_index', 'label'];

    protected $appends = ['pass_rate', 'location_name'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessments()
    {
        return $this->hasMany(ComponentAssessment::class, 'arch_sample_id');
    }

    /**
     * Accessor alias so the shared inspection Blade view (built for
     * ProjectSample) works unmodified for these units too.
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
