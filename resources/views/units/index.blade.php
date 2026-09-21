@extends('layouts.app')

@section('title', 'Houses — ' . $project->project_name)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Houses</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('projects.units.create', $project) }}"
       class="flex items-center gap-1.5 px-4 py-2.5 bg-eids-primary text-white font-bold text-sm rounded-xl hover:bg-eids-dark transition min-h-[44px] shadow-md">
        <span class="material-symbols-outlined text-lg">add</span>
        Add House
    </a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">home_work</span>
                Houses ({{ $units->count() }})
            </h2>
        </div>

        @if($units->isEmpty())
            <div class="py-12 text-center text-sm text-gray-500 font-medium">
                No houses added yet. Add a house to generate its own owner-specific defects report.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-6 py-3.5 text-left">House / Unit No.</th>
                            <th class="px-4 py-3.5 text-left">Owner</th>
                            <th class="px-4 py-3.5 text-left hidden sm:table-cell">Contact</th>
                            <th class="px-4 py-3.5 text-center">Defects</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($units as $unit)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ $unit->unit_reference }}</td>
                                <td class="px-4 py-4 text-gray-700 font-medium">{{ $unit->owner_name ?? '—' }}</td>
                                <td class="px-4 py-4 text-gray-500 hidden sm:table-cell">{{ $unit->owner_phone ?? '—' }}</td>
                                <td class="px-4 py-4 text-center font-mono font-bold text-gray-700">{{ $unit->defects_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('units.report', $unit) }}" class="text-xs font-bold text-eids-accent hover:text-eids-primary hover:underline">Report</a>
                                        <a href="{{ route('units.edit', $unit) }}" class="text-xs font-bold text-gray-500 hover:text-gray-800 hover:underline">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
