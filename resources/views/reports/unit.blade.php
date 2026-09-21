@extends('layouts.app')

@section('title', 'Defects Report: ' . $unit->unit_reference)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $unit->project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $unit->project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.units.index', $unit->project) }}" class="hover:text-gray-800 transition">Houses</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">{{ $unit->unit_reference }}</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('units.edit', $unit) }}"
       class="flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition min-h-[44px]">
        <span class="material-symbols-outlined text-lg">edit</span>
        Edit
    </a>
    <x-pdf-export-button :href="route('units.report.pdf', $unit)" :filename="$unit->unit_reference"
       class="flex items-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
        Export Defects Report PDF &rarr;
    </x-pdf-export-button>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- House / Owner header --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-1">Defects Inspection Report — {{ $unit->unit_reference }}</h1>
        <div class="text-sm text-gray-500 font-medium mb-5">{{ $unit->project->project_name }}</div>

        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">House Address</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->owner_address ?: $unit->project->location ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">House Type</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->house_type ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Report Date</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->report_date?->format('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Owner</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->owner_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Owner Contact Number</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->owner_phone ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Rectification Deadline</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->rectification_deadline?->format('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Handover Date</dt>
                <dd class="text-gray-900 font-semibold">{{ $unit->handover_date?->format('d M Y') ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Defects List --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-red-500 text-lg">warning</span>
                Defects List
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-6 py-3.5 text-center w-16">Item</th>
                        <th class="px-4 py-3.5 text-left w-48">Area</th>
                        <th class="px-6 py-3.5 text-left">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($defects as $area => $areaDefects)
                        <tr class="align-top">
                            <td class="px-6 py-4 text-center font-bold text-gray-900">{{ $loop->iteration }}</td>
                            <td class="px-4 py-4 font-bold text-gray-900">{{ $area }}</td>
                            <td class="px-6 py-4 text-gray-700">
                                @foreach($areaDefects as $defect)
                                    <div class="mb-1.5 last:mb-0">
                                        <span class="font-semibold text-gray-800">{{ $defect->component_name }}</span>
                                        — {{ $defect->defect_description }}
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No defects recorded for this house.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 text-xs text-amber-800 font-medium italic">
        Note: every defect noted above is general in nature — the same defect must be rectified wherever it
        appears on the property, even in areas not individually listed here.
    </div>

    {{-- Photo Annex --}}
    @php
        $photoCards = $defects->flatMap(fn ($areaDefects, $area) => $areaDefects->flatMap(
            fn ($defect) => $defect->photo_urls->map(fn ($url) => ['defect' => $defect, 'area' => $area, 'url' => $url])
        ))->values();
    @endphp
    @if($photoCards->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">photo_library</span>
                    Defect Photographic Evidence ({{ $photoCards->count() }})
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($photoCards as $card)
                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                        <img src="{{ $card['url'] }}" class="w-full h-40 object-cover bg-gray-50">
                        <div class="p-3">
                            <div class="text-xs font-bold text-gray-900 mb-1">{{ $card['area'] }} &middot; {{ $card['defect']->component_name }}</div>
                            <div class="text-xs text-gray-500">{{ Str::limit($card['defect']->defect_description, 90) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Sign-off --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
        <h2 class="font-extrabold text-gray-900 text-sm mb-6">Sign-off</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <div>
                <div class="h-12"></div>
                <div class="border-t border-gray-300 pt-2 text-xs text-gray-500 font-bold">Prepared By — Name &amp; Date</div>
            </div>
            <div>
                <div class="h-12"></div>
                <div class="border-t border-gray-300 pt-2 text-xs text-gray-500 font-bold">Received By — Name &amp; Date</div>
            </div>
            <div>
                <div class="h-12"></div>
                <div class="border-t border-gray-300 pt-2 text-xs text-gray-500 font-bold">Rectification Work Confirmed Complete By</div>
            </div>
        </div>
    </div>
</div>
@endsection
