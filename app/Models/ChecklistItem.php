<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $fillable = [
        'applies_to', 'defect_group', 'sort_order', 'question_text',
        'method_tool', 'tolerance_text', 'input_type', 'tolerance_max_mm',
        'guide_tools', 'guide_procedure', 'guide_result_thresholds',
    ];

    protected $casts = [
        'sort_order'       => 'integer',
        'tolerance_max_mm' => 'decimal:2',
    ];

    /**
     * guide_tools/guide_procedure/guide_result_thresholds hold rich-text
     * editor HTML (bold, lists, inline images) — "empty" means Quill's own
     * empty-document markup, not just a blank string.
     */
    private function isBlankHtml(?string $html): bool
    {
        if (blank($html)) return true;
        return trim(strip_tags($html)) === '' && !str_contains($html, '<img');
    }

    public function getHasGuideAttribute(): bool
    {
        return !$this->isBlankHtml($this->guide_tools)
            || !$this->isBlankHtml($this->guide_procedure)
            || !$this->isBlankHtml($this->guide_result_thresholds);
    }

    public function scopeForComponent($query, string $componentCode)
    {
        return $query->where('applies_to', $componentCode)->orderBy('sort_order');
    }
}
