<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'project_id', 'unit_reference', 'owner_name', 'house_type',
        'owner_phone', 'owner_address', 'report_date',
        'rectification_deadline', 'handover_date',
    ];

    protected $casts = [
        'report_date'             => 'date',
        'rectification_deadline'  => 'date',
        'handover_date'           => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function defects()
    {
        return $this->hasMany(Defect::class);
    }
}
