<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'project_no', 'project_name', 'location', 'developer_name',
        'contractor_name', 'building_type', 'total_units', 'floor_area_sqm',
        'calculated_samples', 'overall_score', 'status', 'created_by',
    ];

    protected $casts = [
        'floor_area_sqm'     => 'decimal:2',
        'overall_score'      => 'decimal:2',
        'total_units'        => 'integer',
        'calculated_samples' => 'integer',
    ];

    public function samples()
    {
        return $this->hasMany(ProjectSample::class);
    }

    public function assessments()
    {
        return $this->hasMany(ComponentAssessment::class);
    }

    public function defects()
    {
        return $this->hasMany(Defect::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draf'               => 'Draft',
            'dalam_pemeriksaan'  => 'In Inspection',
            'selesai'            => 'Completed',
            default              => $this->status,
        };
    }

    public function getBuildingTypeLabelAttribute(): string
    {
        return match($this->building_type) {
            'teres'  => 'Terrace',
            'semi_d' => 'Semi-D',
            'banglo' => 'Bungalow',
            default  => $this->building_type,
        };
    }

    public function getRatingAttribute(): string
    {
        $score = (float) $this->overall_score;
        if ($score >= (float) setting('rating_baik', 85)) return 'GOOD';
        if ($score >= (float) setting('rating_sederhana', 70)) return 'MODERATE';
        return 'WEAK';
    }

    public function getInspectionProgressAttribute(): int
    {
        $total = $this->calculated_samples * count(config('eids.components'));
        if ($total === 0) return 0;
        $done = $this->assessments()->count();
        return (int) min(100, round(($done / $total) * 100));
    }
}
