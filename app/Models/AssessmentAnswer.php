<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'component_assessment_id', 'checklist_item_id', 'result', 'numeric_value', 'remarks',
    ];

    protected $casts = [
        'numeric_value' => 'decimal:2',
    ];

    public function componentAssessment()
    {
        return $this->belongsTo(ComponentAssessment::class);
    }

    public function checklistItem()
    {
        return $this->belongsTo(ChecklistItem::class);
    }
}
