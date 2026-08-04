<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectExternalElement extends Model
{
    protected $fillable = ['project_id', 'element_code', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
