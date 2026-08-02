@extends('layouts.app')

@section('title', 'Edit Project')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-32">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Edit</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ route('projects.update', $project) }}">
        @csrf
        @method('PUT')

        {{-- Project Info --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-eids-accent text-lg">info</span>
                Project Specifications &amp; Details
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Project Reference No. *</label>
                    <input type="text" name="project_no" value="{{ old('project_no', $project->project_no) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('project_no') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('project_no')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Project Name *</label>
                    <input type="text" name="project_name" value="{{ old('project_name', $project->project_name) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('project_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('project_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Site Location / Address</label>
                    <input type="text" name="location" value="{{ old('location', $project->location) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Developer Name *</label>
                    <input type="text" name="developer_name" value="{{ old('developer_name', $project->developer_name) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('developer_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('developer_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Contractor Name *</label>
                    <input type="text" name="contractor_name" value="{{ old('contractor_name', $project->contractor_name) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('contractor_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('contractor_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Building Type *</label>
                    <select name="building_type" required
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="teres"  {{ old('building_type', $project->building_type) === 'teres'  ? 'selected' : '' }}>Terrace House</option>
                        <option value="semi_d" {{ old('building_type', $project->building_type) === 'semi_d' ? 'selected' : '' }}>Semi-Detached (Semi-D)</option>
                        <option value="banglo" {{ old('building_type', $project->building_type) === 'banglo' ? 'selected' : '' }}>Bungalow</option>
                    </select>
                    @error('building_type')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                @if($project->status === 'selesai')
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Project Status</label>
                        <div class="w-full min-h-[44px] px-4 py-2.5 border border-emerald-200 bg-emerald-50 rounded-xl text-sm font-bold text-emerald-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">verified</span>
                            Completed
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500 font-medium">This project is complete and locked. Status can't be changed here.</p>
                        <input type="hidden" name="status" value="selesai">
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Project Status *</label>
                        <select name="status" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                            <option value="draf"              {{ old('status', $project->status) === 'draf'              ? 'selected' : '' }}>Draft</option>
                            <option value="dalam_pemeriksaan" {{ old('status', $project->status) === 'dalam_pemeriksaan' ? 'selected' : '' }}>In Inspection</option>
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500 font-medium">Marking a project "Completed" happens via the sign-off button once inspection is finished and all defects are resolved.</p>
                    </div>
                @endif

                @if(auth()->user()->isAdmin())
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assigned Inspector</label>
                    <select name="assigned_to"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Unassigned (Open for all inspectors)</option>
                        @foreach($inspectors as $inspector)
                            <option value="{{ $inspector->id }}" {{ old('assigned_to', $project->assigned_to) == $inspector->id ? 'selected' : '' }}>
                                {{ $inspector->name }} ({{ $inspector->getRoleLabel() }})
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>
                @else
                <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-xl p-4 flex items-start gap-3">
                    <span class="material-symbols-outlined text-eids-accent text-lg shrink-0">info</span>
                    <p class="text-xs text-gray-700 font-medium">
                        Assigned Inspector: <strong>{{ $project->assignedInspector?->name ?? 'Unassigned' }}</strong>.
                        Only an Admin can reassign it.
                    </p>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assigned Contractor</label>
                    <select name="assigned_contractor_id"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Unassigned</option>
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}" {{ old('assigned_contractor_id', $project->assigned_contractor_id) == $contractor->id ? 'selected' : '' }}>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_contractor_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Total Project Units *</label>
                    <input type="number" name="total_units" value="{{ old('total_units', $project->total_units) }}" min="1" required
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('total_units')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- GFA & Sample configuration note --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-8">
            <h2 class="font-extrabold text-gray-900 mb-1 flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined text-eids-accent text-lg">calculate</span>
                Sample Unit Area Configuration
            </h2>
            <p class="text-xs text-gray-500 font-medium mb-5">Updating Gross Floor Area recalculates target samples but preserves existing sample unit inspections.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Gross Floor Area (GFA m²) *</label>
                    <input type="number" name="floor_area_sqm" step="0.01" min="1"
                           value="{{ old('floor_area_sqm', $project->floor_area_sqm) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-mono font-medium">
                    @error('floor_area_sqm')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-xl p-4.5">
                    <div class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Current Active Sample Count</div>
                    <div class="text-3xl font-extrabold text-eids-primary font-mono">{{ $project->samples->count() }}</div>
                    <div class="text-xs text-gray-600 mt-1 font-medium">
                        Calculated required: {{ $project->calculated_samples }} sample units
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
            @if(auth()->user()->isAdmin())
                <button type="button"
                        @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('projects.destroy', $project) }}', method: 'DELETE' })"
                        class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-red-700 border border-red-200 rounded-xl hover:bg-red-50 transition flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">delete</span>
                    Delete Project
                </button>
            @else
                <div></div>
            @endif
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.show', $project) }}"
                   class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">
                    Cancel
                </a>
                <button type="submit"
                        class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                    <span class="material-symbols-outlined text-base">save</span>
                    Save Project Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
