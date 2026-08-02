@extends('layouts.app')

@section('title', 'New Project')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">New Project</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto" x-data="{
    gfa: '{{ old('floor_area_sqm') }}',
    divisor: {{ setting('sample_divisor', 60) }},
    get samples() {
        const n = parseFloat(this.gfa);
        return isNaN(n) || n <= 0 ? 0 : Math.max(1, Math.ceil(n / this.divisor));
    }
}">
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf

        {{-- Project Info --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-eids-accent text-lg">info</span>
                Project Specifications & Metadata
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Project Reference No. *</label>
                    <input type="text" name="project_no" value="{{ old('project_no') }}" placeholder="e.g. PRJ-2026-001" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('project_no') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('project_no')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Project Name *</label>
                    <input type="text" name="project_name" value="{{ old('project_name') }}" placeholder="e.g. Taman Merlimau Perdana" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('project_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('project_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Site Location / Address</label>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Merlimau, Melaka"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Developer Name *</label>
                    <input type="text" name="developer_name" value="{{ old('developer_name') }}" placeholder="Developer company name" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('developer_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('developer_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Contractor Name *</label>
                    <input type="text" name="contractor_name" value="{{ old('contractor_name') }}" placeholder="Contractor company name" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('contractor_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('contractor_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Building Type *</label>
                    <select name="building_type" required
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Select building type...</option>
                        <option value="teres"  {{ old('building_type') === 'teres'  ? 'selected' : '' }}>Terrace House</option>
                        <option value="semi_d" {{ old('building_type') === 'semi_d' ? 'selected' : '' }}>Semi-Detached (Semi-D)</option>
                        <option value="banglo" {{ old('building_type') === 'banglo' ? 'selected' : '' }}>Bungalow</option>
                    </select>
                    @error('building_type')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Initial Status *</label>
                    <select name="status" required
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="draf"              {{ old('status','dalam_pemeriksaan') === 'draf'              ? 'selected' : '' }}>Draft</option>
                        <option value="dalam_pemeriksaan" {{ old('status','dalam_pemeriksaan') === 'dalam_pemeriksaan' ? 'selected' : '' }}>In Inspection</option>
                    </select>
                </div>

                @if(auth()->user()->isAdmin() || auth()->user()->isLeadAuditor())
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assigned Inspector</label>
                    <select name="assigned_to"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Unassigned (Open for all inspectors)</option>
                        @foreach($inspectors as $inspector)
                            <option value="{{ $inspector->id }}" {{ old('assigned_to') == $inspector->id ? 'selected' : '' }}>
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
                        This project will be assigned to you ({{ auth()->user()->name }}) as the inspector.
                        Only an Admin or Lead Auditor can assign it to someone else.
                    </p>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Assigned Contractor</label>
                    <select name="assigned_contractor_id"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Unassigned</option>
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}" {{ old('assigned_contractor_id') == $contractor->id ? 'selected' : '' }}>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_contractor_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                @if(auth()->user()->isAdmin() || auth()->user()->isLeadAuditor())
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Supervisors / Client Viewers</label>
                    <div class="flex flex-wrap gap-3 p-3 border border-gray-200 rounded-xl">
                        <input type="hidden" name="supervisors_submitted" value="1">
                        @forelse($supervisors as $supervisor)
                            <label class="flex items-center gap-1.5 text-sm font-medium">
                                <input type="checkbox" name="supervisor_ids[]" value="{{ $supervisor->id }}"
                                       {{ collect(old('supervisor_ids', []))->contains($supervisor->id) ? 'checked' : '' }}>
                                {{ $supervisor->name }}
                            </label>
                        @empty
                            <span class="text-xs text-gray-400 italic">No supervisor accounts yet.</span>
                        @endforelse
                    </div>
                </div>
                @endif

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Total Project Units *</label>
                    <input type="number" name="total_units" value="{{ old('total_units', 1) }}" min="1" required
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('total_units')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- Sample Calculation --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-8">
            <h2 class="font-extrabold text-gray-900 mb-1 flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined text-eids-accent text-lg">calculate</span>
                Sample Unit Calculation
            </h2>
            <p class="text-xs text-gray-500 font-medium mb-5">Formula: N = max(1, ceil(GFA ÷ {{ setting('sample_divisor', 60) }}))</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Gross Floor Area (GFA m²) *</label>
                    <input type="number" name="floor_area_sqm" step="0.01" min="1"
                           value="{{ old('floor_area_sqm') }}" placeholder="e.g. 210.00" required
                           x-model="gfa"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-mono font-medium">
                    @error('floor_area_sqm')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-xl p-4.5">
                    <div class="text-xs text-gray-600 font-bold uppercase tracking-wider mb-1">Calculated Sample Units (N)</div>
                    <div class="text-4xl font-extrabold text-eids-primary font-mono" x-text="samples || '0'"></div>
                    <div class="text-xs text-gray-600 mt-1 font-medium" x-show="samples > 0">
                        <strong x-text="samples"></strong> sample unit<span x-show="samples !== 1">s</span> will be automatically generated.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <a href="{{ route('projects.index') }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">
                Cancel
            </a>
            <button type="submit"
                    class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                <span class="material-symbols-outlined text-base">save</span>
                Create Project &amp; Initialize Samples
            </button>
        </div>
    </form>
</div>
@endsection
