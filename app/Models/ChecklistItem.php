<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $fillable = [
        'applies_to', 'defect_group', 'sort_order', 'question_text',
        'method_tool', 'tolerance_text', 'input_type', 'tolerance_max_mm',
        'guide_tools', 'guide_procedure', 'guide_result_thresholds', 'guide_photos',
    ];

    protected $casts = [
        'sort_order'       => 'integer',
        'tolerance_max_mm' => 'decimal:2',
        'guide_procedure'  => 'array',
        'guide_photos'     => 'array',
    ];

    public function getHasGuideAttribute(): bool
    {
        return filled($this->guide_tools)
            || filled($this->guide_procedure)
            || filled($this->guide_result_thresholds)
            || filled($this->guide_photos);
    }

    public function scopeForComponent($query, string $componentCode)
    {
        return $query->where('applies_to', $componentCode)->orderBy('sort_order');
    }
}
