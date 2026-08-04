@extends('layouts.app')

@section('title', 'Edit Question')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('settings.index') }}" class="hover:text-gray-800 transition">Settings</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('checklist-items.index', ['component' => $item->applies_to]) }}" class="hover:text-gray-800 transition">{{ $item->applies_to }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Edit Question</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('checklist-items.update', $item) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6 space-y-5">
            <h2 class="font-extrabold text-gray-900 text-sm border-b border-gray-100 pb-3">Question · {{ $item->applies_to }}</h2>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Question Text *</label>
                <textarea name="question_text" rows="2" required
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">{{ old('question_text', $item->question_text) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Defect Group *</label>
                    <input type="text" name="defect_group" value="{{ old('defect_group', $item->defect_group) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Sort Order *</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}" min="0" required
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Method / Tool</label>
                    <input type="text" name="method_tool" value="{{ old('method_tool', $item->method_tool) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Input Type *</label>
                    <select name="input_type" class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-eids-accent">
                        <option value="pass_fail" {{ old('input_type', $item->input_type) === 'pass_fail' ? 'selected' : '' }}>Pass / Fail</option>
                        <option value="numeric_with_tolerance" {{ old('input_type', $item->input_type) === 'numeric_with_tolerance' ? 'selected' : '' }}>Numeric with Tolerance</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tolerance Text (display)</label>
                    <input type="text" name="tolerance_text" value="{{ old('tolerance_text', $item->tolerance_text) }}" placeholder="e.g. ≤ 3 mm / 1.2 m"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tolerance Max (mm, used for auto PASS/FAIL)</label>
                    <input type="number" step="0.01" min="0" name="tolerance_max_mm" value="{{ old('tolerance_max_mm', $item->tolerance_max_mm) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6 space-y-5">
            <h2 class="font-extrabold text-gray-900 text-sm border-b border-gray-100 pb-3 flex items-center justify-between">
                Guide Content (optional — the "?" icon only shows when this is filled in)
            </h2>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tools Needed</label>
                <textarea name="guide_tools" rows="2" placeholder="One per line, e.g. Spirit Level 1.2 m"
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">{{ old('guide_tools', $item->guide_tools) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Inspection Procedure (one step per line)</label>
                <textarea name="guide_procedure_text" rows="5" placeholder="1. Place the spirit level...&#10;2. Insert the steel wedge..."
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">{{ old('guide_procedure_text', $item->guide_procedure ? implode("\n", $item->guide_procedure) : '') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Result Thresholds (display text)</label>
                <input type="text" name="guide_result_thresholds" value="{{ old('guide_result_thresholds', $item->guide_result_thresholds) }}" placeholder="PASS: ≤ 3 mm · FAIL: > 3 mm"
                       class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
            </div>
        </div>

        <div class="flex justify-end gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <a href="{{ route('checklist-items.index', ['component' => $item->applies_to]) }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">
                Cancel
            </a>
            <button type="submit"
                    class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                <span class="material-symbols-outlined text-base">save</span>
                Save Question
            </button>
        </div>
    </form>
</div>
@endsection
