<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QpDeclaration extends Model
{
    protected $fillable = ['project_id', 'item_code', 'declared', 'evidence_path', 'declared_at'];

    protected $casts = [
        'declared'    => 'boolean',
        'declared_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        return $this->evidence_path ? Storage::url($this->evidence_path) : null;
    }

    public function getIsEarnedAttribute(): bool
    {
        return $this->declared && filled($this->evidence_path);
    }
}
