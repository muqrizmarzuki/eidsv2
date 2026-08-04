@extends('layouts.app')

@section('title', 'New Project')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">New Project</span>
@endsection

@php
    $samplingByCategory = \App\Models\SamplingRule::all()->keyBy('building_category')->map(fn ($r) => [
        'divisor' => (float) $r->gfa_divisor, 'min' => $r->min_samples, 'max' => $r->max_samples,
    ]);
@endphp

@section('content')
<div class="max-w-3xl mx-auto" x-data="{
    gfa: '{{ old('floor_area_sqm') }}',
    category: '{{ old('building_category', 'A') }}',
    rules: {{ Js::from($samplingByCategory) }},
    get samples() {
        const n = parseFloat(this.gfa);
        const r = this.rules[this.category] ?? { divisor: 60, min: 1, max: 999999 };
        if (isNaN(n) || n <= 0) return 0;
        return Math.max(r.min, Math.min(r.max, Math.ceil(n / r.divisor)));
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
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">CIS 7:2021 Building Category *</label>
                    <select name="building_category" required x-model="category"
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="A" {{ old('building_category', 'A') === 'A' ? 'selected' : '' }}>Category A — Landed Housing</option>
                        <option value="B" {{ old('building_category') === 'B' ? 'selected' : '' }}>Category B — Stratified Housing</option>
                        <option value="C" {{ old('building_category') === 'C' ? 'selected' : '' }}>Category C — Commercial/Industrial (no CCS)</option>
                        <option value="D" {{ old('building_category') === 'D' ? 'selected' : '' }}>Category D — Commercial/Industrial (with CCS)</option>
                    </select>
                    @error('building_category')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Initial Status *</label>
                    <select name="status" required
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="draf"              {{ old('status','dalam_pemeriksaan') === 'draf'              ? 'selected' : '' }}>Draft</option>
                        <option value="dalam_pemeriksaan" {{ old('status','dalam_pemeriksaan') === 'dalam_pemeriksaan' ? 'selected' : '' }}>In Inspection</option>
                    </select>
                </div>

                @if(auth()->user()->isAdmin())
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
                        Only an Admin can assign it to someone else.
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
            <p class="text-xs text-gray-500 font-medium mb-5">Formula (Table 3, per building category): N = clamp(ceil(GFA &divide; divisor), min, max)</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Total Project GFA (m²) *</label>
                    <input type="number" name="floor_area_sqm" step="0.01" min="1"
                           value="{{ old('floor_area_sqm') }}" placeholder="e.g. 10500.00" required
                           x-model="gfa"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-mono font-medium">
                    <p class="mt-1.5 text-xs text-gray-500 font-medium">The <strong>whole project's</strong> combined floor area (all units together) — per CIS 7:2021 §1.7, not a single unit's size.</p>
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
