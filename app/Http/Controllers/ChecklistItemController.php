<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\WeightageArchitecturalElement;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    public function index(Request $request)
    {
        $components    = WeightageArchitecturalElement::ordered()
            ->reject(fn ($el) => $el->scoring_mode === 'declaration');
        $activeCode    = $request->get('component', $components->keys()->first());
        $items         = ChecklistItem::forComponent($activeCode)->get();

        return view('checklist-items.index', compact('components', 'activeCode', 'items'));
    }

    public function edit(ChecklistItem $checklistItem)
    {
        return view('checklist-items.edit', ['item' => $checklistItem]);
    }

    public function update(Request $request, ChecklistItem $checklistItem)
    {
        $data = $request->validate([
            'question_text'   => 'required|string',
            'defect_group'    => 'required|string|max:255',
            'method_tool'     => 'nullable|string|max:255',
            'tolerance_text'  => 'nullable|string|max:255',
            'input_type'      => 'required|in:pass_fail,numeric_with_tolerance',
            'tolerance_max_mm' => 'nullable|numeric|min:0',
            'sort_order'      => 'required|integer|min:0',
            'guide_tools'             => 'nullable|string',
            'guide_procedure_text'    => 'nullable|string',
            'guide_result_thresholds' => 'nullable|string|max:255',
        ]);

        $data['guide_procedure'] = filled($data['guide_procedure_text'] ?? null)
            ? array_values(array_filter(array_map('trim', explode("\n", $data['guide_procedure_text']))))
            : null;
        unset($data['guide_procedure_text']);

        $checklistItem->update($data);

        return redirect()
            ->route('checklist-items.index', ['component' => $checklistItem->applies_to])
            ->with('success', 'Question updated successfully.');
    }
}
