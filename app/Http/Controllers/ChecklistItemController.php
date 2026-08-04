<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\WeightageArchitecturalElement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'guide_procedure'         => 'nullable|string',
            'guide_result_thresholds' => 'nullable|string',
        ]);

        $checklistItem->update($data);

        return redirect()
            ->route('checklist-items.index', ['component' => $checklistItem->applies_to])
            ->with('success', 'Question updated successfully.');
    }

    /**
     * Image upload target for the Guide content rich-text editor — the editor
     * POSTs the file here on insert and embeds the returned URL as an <img>,
     * rather than bloating the HTML field with a base64 blob.
     */
    public function uploadGuideImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $request->file('image')->store('guide-images', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }
}
