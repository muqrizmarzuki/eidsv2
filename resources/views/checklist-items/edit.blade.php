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

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    /* Theme Quill to match the E-IDS design system instead of its default look. */
    .eids-editor .ql-toolbar.ql-snow {
        border-color: #e5e7eb; border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem;
        background: #f9fafb;
    }
    .eids-editor .ql-container.ql-snow {
        border-color: #e5e7eb; border-bottom-left-radius: 0.75rem; border-bottom-right-radius: 0.75rem;
        font-family: inherit; font-size: 0.875rem;
    }
    .eids-editor .ql-editor { min-height: 7rem; }
    .eids-editor .ql-editor img { border-radius: 0.5rem; max-width: 100%; margin: 0.25rem 0; }
    .eids-editor.eids-editor-sm .ql-editor { min-height: 3.5rem; }
    .eids-editor .ql-snow.ql-toolbar button:hover,
    .eids-editor .ql-snow .ql-toolbar button:hover,
    .eids-editor .ql-snow.ql-toolbar button.ql-active,
    .eids-editor .ql-snow .ql-toolbar button.ql-active { color: #059669; }
    .eids-editor .ql-snow.ql-toolbar button:hover .ql-stroke,
    .eids-editor .ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke: #059669; }
    .eids-editor .ql-snow.ql-toolbar button:hover .ql-fill,
    .eids-editor .ql-snow.ql-toolbar button.ql-active .ql-fill { fill: #059669; }
    .eids-editor .ql-container.ql-snow:focus-within { box-shadow: 0 0 0 2px #059669; border-color: transparent; }
</style>

<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('checklist-items.update', $item) }}" id="question-form">
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
            <p class="text-xs text-gray-500 -mt-3">
                Format freely — bold, lists, and inline images all render exactly as shown here in the inspector's Guide popup.
            </p>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tools Needed</label>
                <div id="editor-guide_tools" class="eids-editor eids-editor-sm"></div>
                <textarea name="guide_tools" id="input-guide_tools" class="hidden">{{ old('guide_tools', $item->guide_tools) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Inspection Procedure</label>
                <div id="editor-guide_procedure" class="eids-editor"></div>
                <textarea name="guide_procedure" id="input-guide_procedure" class="hidden">{{ old('guide_procedure', $item->guide_procedure) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Result Thresholds</label>
                <div id="editor-guide_result_thresholds" class="eids-editor eids-editor-sm"></div>
                <textarea name="guide_result_thresholds" id="input-guide_result_thresholds" class="hidden">{{ old('guide_result_thresholds', $item->guide_result_thresholds) }}</textarea>
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

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadUrl = @json(route('checklist-items.upload-image'));
        const csrfToken = @json(csrf_token());

        function imageHandler() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = () => {
                const file = input.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                fetch(uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                })
                    .then(r => r.json())
                    .then(data => {
                        const range = this.quill.getSelection(true);
                        this.quill.insertEmbed(range.index, 'image', data.url);
                        this.quill.setSelection(range.index + 1);
                    });
            };
        }

        function mountEditor(name, { toolbar }) {
            const hidden = document.getElementById(`input-${name}`);
            const quill = new Quill(`#editor-${name}`, {
                theme: 'snow',
                placeholder: 'Optional — leave blank to hide the Guide icon for this question.',
                modules: {
                    toolbar: {
                        container: toolbar,
                        handlers: { image: imageHandler },
                    },
                },
            });
            quill.root.innerHTML = hidden.value || '';
            quill.on('text-change', () => {
                const html = quill.getSemanticHTML().trim();
                hidden.value = (html === '<p></p>' || html === '') ? '' : quill.root.innerHTML;
            });
        }

        const fullToolbar  = [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'image'], ['clean']];
        const smallToolbar = [['bold', 'italic', 'underline'], ['link', 'image'], ['clean']];

        mountEditor('guide_tools', { toolbar: smallToolbar });
        mountEditor('guide_procedure', { toolbar: fullToolbar });
        mountEditor('guide_result_thresholds', { toolbar: smallToolbar });
    });
</script>
@endsection
