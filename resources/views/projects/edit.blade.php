@extends('layouts.app')

@section('title', 'Edit Project')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-600">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-32">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Edit</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <form method="POST" action="{{ route('projects.update', $project) }}">
        @csrf
        @method('PUT')

        {{-- Project Info --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
            <h2 class="font-semibold text-gray-800 mb-5 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent">info</span>
                Project Information
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Project Reference No. *</label>
                    <input type="text" name="project_no" value="{{ old('project_no', $project->project_no) }}" required
                           class="w-full px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('project_no') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('project_no')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Project Name *</label>
                    <input type="text" name="project_name" value="{{ old('project_name', $project->project_name) }}" required
                           class="w-full px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('project_name') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('project_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Location</label>
                    <input type="text" name="location" value="{{ old('location', $project->location) }}"
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Developer *</label>
                    <input type="text" name="developer_name" value="{{ old('developer_name', $project->developer_name) }}" required
                           class="w-full px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('developer_name') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('developer_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Contractor *</label>
                    <input type="text" name="contractor_name" value="{{ old('contractor_name', $project->contractor_name) }}" required
                           class="w-full px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('contractor_name') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('contractor_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Building Type *</label>
                    <select name="building_type" required
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                        <option value="teres"  {{ old('building_type', $project->building_type) === 'teres'  ? 'selected' : '' }}>Terrace</option>
                        <option value="semi_d" {{ old('building_type', $project->building_type) === 'semi_d' ? 'selected' : '' }}>Semi-D</option>
                        <option value="banglo" {{ old('building_type', $project->building_type) === 'banglo' ? 'selected' : '' }}>Bungalow</option>
                    </select>
                    @error('building_type')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status *</label>
                    <select name="status" required
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                        <option value="draf"              {{ old('status', $project->status) === 'draf'              ? 'selected' : '' }}>Draft</option>
                        <option value="dalam_pemeriksaan" {{ old('status', $project->status) === 'dalam_pemeriksaan' ? 'selected' : '' }}>In Inspection</option>
                        <option value="selesai"           {{ old('status', $project->status) === 'selesai'           ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Total Units *</label>
                    <input type="number" name="total_units" value="{{ old('total_units', $project->total_units) }}" min="1" required
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                    @error('total_units')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- GFA (read-only note) --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
            <h2 class="font-semibold text-gray-800 mb-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent">calculate</span>
                Sample Configuration
            </h2>
            <p class="text-xs text-gray-400 mb-5">Changing GFA will update the score calculation but will NOT add or remove existing sample units.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Gross Floor Area (m²) *</label>
                    <input type="number" name="floor_area_sqm" step="0.01" min="1"
                           value="{{ old('floor_area_sqm', $project->floor_area_sqm) }}" required
                           class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                    @error('floor_area_sqm')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="bg-eids-primary/5 border border-eids-primary/10 rounded-xl p-4">
                    <div class="text-xs text-gray-500 mb-1">Current Sample Count</div>
                    <div class="text-3xl font-bold text-eids-primary">{{ $project->samples->count() }}</div>
                    <div class="text-xs text-gray-400 mt-1">
                        Calculated: {{ $project->calculated_samples }} required
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('projects.show', $project) }}"
               class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-eids-primary text-white text-sm font-semibold rounded-lg hover:bg-eids-dark transition flex items-center gap-2">
                <span class="material-symbols-outlined text-base">save</span>
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
