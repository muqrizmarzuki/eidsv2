@extends('layouts.app')

@section('title', 'Edit Defect')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('defects.index') }}" class="hover:text-gray-800 transition">Defects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Edit</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('defects.update', $defect) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-red-500 text-lg">warning</span>
                Defect Information &amp; Photo Documentation
            </h2>
            <div class="space-y-5" x-data="{ projectId: '{{ old('project_id', $defect->project_id) }}' }">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Target Project *</label>
                    <select name="project_id" required x-model="projectId" @change="$refs.unitSelect.value = ''"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        @foreach($projects as $proj)
                            <option value="{{ $proj->id }}" {{ old('project_id', $defect->project_id) == $proj->id ? 'selected' : '' }}>
                                {{ $proj->project_no }} &middot; {{ $proj->project_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">House / Unit (Optional)</label>
                    <select name="unit_id" x-ref="unitSelect"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Not linked to a specific house</option>
                        @foreach($units->groupBy('project_id') as $projectId2 => $projectUnits)
                            <optgroup label="{{ $projectUnits->first()->project->project_name }}" x-show="!projectId || projectId == '{{ $projectId2 }}'">
                                @foreach($projectUnits as $u)
                                    <option value="{{ $u->id }}" data-project-id="{{ $u->project_id }}"
                                            {{ old('unit_id', $defect->unit_id) == $u->id ? 'selected' : '' }}>
                                        {{ $u->unit_reference }}{{ $u->owner_name ? " — {$u->owner_name}" : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Pick the house this defect belongs to, so it can appear on that house's defects report. Only houses under the selected project are shown.</p>
                    @error('unit_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Component *</label>
                        <input type="text" name="component_name" value="{{ old('component_name', $defect->component_name) }}" required
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        @error('component_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Location / Room Name *</label>
                        <input type="text" name="location" value="{{ old('location', $defect->location) }}" required
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        @error('location')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Defect Description *</label>
                    <textarea name="defect_description" rows="3" required
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent resize-none font-medium">{{ old('defect_description', $defect->defect_description) }}</textarea>
                    @error('defect_description')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Severity Rating *</label>
                        <select name="severity" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                            <option value="low"    {{ old('severity', $defect->severity) === 'low'    ? 'selected' : '' }}>Low Severity</option>
                            <option value="medium" {{ old('severity', $defect->severity) === 'medium' ? 'selected' : '' }}>Medium Severity</option>
                            <option value="high"   {{ old('severity', $defect->severity) === 'high'   ? 'selected' : '' }}>High Severity</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Defect Status</label>
                        <div class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 flex items-center">
                            <span class="inline-flex px-3 py-1 border rounded-full text-xs font-extrabold {{ $defect->status_badge_class }}">
                                {{ $defect->status_label }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 font-medium mt-1.5">
                            Status is advanced through the defect workflow, not this form.
                        </p>
                    </div>
                </div>

                <x-photo-uploader label="Defect Photo Evidence" :photos="$defect->getMedia('photos')" />
            </div>
        </div>

        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
            @if(auth()->user()->canInspect())
                <button type="button"
                        @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('defects.destroy', $defect) }}', method: 'DELETE' })"
                        class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-red-700 border border-red-200 rounded-xl hover:bg-red-50 transition flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">delete</span>
                    Delete Defect
                </button>
            @else
                <div></div>
            @endif
            <div class="flex items-center gap-3">
                <a href="{{ route('defects.index') }}"
                   class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">
                    Cancel
                </a>
                <button type="submit"
                        class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                    <span class="material-symbols-outlined text-base">save</span>
                    Save Defect Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
