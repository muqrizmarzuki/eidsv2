<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectExternalElement extends Model
{
    protected $fillable = ['project_id', 'element_code', 'present'];

    protected $casts = ['present' => 'boolean'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
