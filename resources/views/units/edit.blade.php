@extends('layouts.app')

@section('title', 'Edit House')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.units.index', $project) }}" class="hover:text-gray-800 transition">Houses</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">{{ $unit->unit_reference }}</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('units.report', $unit) }}"
       class="flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition min-h-[44px]">
        <span class="material-symbols-outlined text-lg">description</span>
        View Defects Report
    </a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('units.update', $unit) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-eids-accent text-lg">home_work</span>
                House &amp; Owner Details
            </h2>
            <div class="space-y-5">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">House / Unit No. *</label>
                    <input type="text" name="unit_reference" value="{{ old('unit_reference', $unit->unit_reference) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('unit_reference')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Owner Name</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name', $unit->owner_name) }}"
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        @error('owner_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">House Type</label>
                        <input type="text" name="house_type" value="{{ old('house_type', $unit->house_type) }}" placeholder="e.g. Double Storey Terrace"
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        @error('house_type')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Owner Contact Number</label>
                    <input type="text" name="owner_phone" value="{{ old('owner_phone', $unit->owner_phone) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('owner_phone')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">House Address</label>
                    <textarea name="owner_address" rows="2"
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent resize-none font-medium">{{ old('owner_address', $unit->owner_address) }}</textarea>
                    @error('owner_address')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-eids-accent text-lg">event</span>
                Defects Report Dates
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Report Date</label>
                    <input type="date" name="report_date" value="{{ old('report_date', $unit->report_date?->format('Y-m-d')) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('report_date')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Rectification Deadline</label>
                    <input type="date" name="rectification_deadline" value="{{ old('rectification_deadline', $unit->rectification_deadline?->format('Y-m-d')) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('rectification_deadline')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Handover Date</label>
                    <input type="date" name="handover_date" value="{{ old('handover_date', $unit->handover_date?->format('Y-m-d')) }}"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    @error('handover_date')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
            <button type="button"
                    @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('units.destroy', $unit) }}', method: 'DELETE' })"
                    class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-red-700 border border-red-200 rounded-xl hover:bg-red-50 transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">delete</span>
                Remove House
            </button>
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.units.index', $project) }}"
                   class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">
                    Cancel
                </a>
                <button type="submit"
                        class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                    <span class="material-symbols-outlined text-base">save</span>
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
