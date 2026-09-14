@extends('layouts.app')

@section('title', 'Inspection Report: ' . $project->project_no)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">E-IDS Report</span>
@endsection

@section('topbar-actions')
    <x-pdf-export-button :href="route('reports.pdf', $project)" :filename="$project->project_no"
       class="flex items-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
        Export Signed PDF Certificate &rarr;
    </x-pdf-export-button>
@endsection

@section('content')
    @php
        $ratingMap  = ['GOOD' => 'Good Rating', 'MODERATE' => 'Moderate Rating', 'WEAK' => 'Weak Rating'];
        $statusMap  = ['draf' => 'Draft', 'dalam_pemeriksaan' => 'In Inspection', 'selesai' => 'Completed'];
        $typeMap    = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
        $ratingCls  = ['GOOD' => 'bg-emerald-500 text-white', 'MODERATE' => 'bg-amber-400 text-amber-950', 'WEAK' => 'bg-red-500 text-white'];
    @endphp

    <div class="max-w-5xl mx-auto">

        {{-- Formal Report Header --}}
        <div class="bg-eids-primary rounded-2xl p-6 sm:p-8 mb-6 text-white shadow-md border border-white/10 relative overflow-hidden">
            <div class="flex items-start justify-between gap-6 flex-wrap relative z-10">
                <div>
                    <div class="text-xs text-eids-light uppercase tracking-widest font-extrabold mb-1.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">verified</span>
                        Formal E-IDS Inspection Certificate
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $project->project_name }}</h1>
                    <div class="text-white/80 text-sm mt-1.5 font-semibold">{{ $project->project_no }} &nbsp;·&nbsp; {{ $typeMap[$project->building_type] ?? $project->building_type }}</div>
                    @if($project->location)
                        <div class="text-white/60 text-xs mt-1 flex items-center gap-1 font-medium">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            {{ $project->location }}
                        </div>
                    @endif
                </div>
                <div class="text-center bg-white/10 rounded-2xl px-6 py-4 backdrop-blur-xs border border-white/10 shrink-0">
                    <div class="text-xs text-white/70 font-bold mb-1 uppercase tracking-wider">Final E-IDS Score</div>
                    <div class="text-4xl lg:text-5xl font-extrabold text-white font-mono leading-none">{{ number_format($totalScore, 2) }}</div>
                    <div class="mt-2.5 px-3 py-1 rounded-full text-xs font-extrabold inline-block {{ $ratingCls[$rating] ?? 'bg-gray-500 text-white' }} uppercase shadow-xs">
                        {{ $ratingMap[$rating] ?? $rating }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-white/10 text-center relative z-10">
                <div>
                    <div class="text-2xl font-extrabold text-white font-mono">{{ number_format($sArch, 2) }}</div>
                    <div class="text-xs text-white/70 mt-1 font-medium">Architectural Subtotal</div>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-eids-light font-mono">{{ number_format($meScore, 2) }}</div>
                    <div class="text-xs text-white/70 mt-1 font-medium">M&amp;E Fittings ({{ $meRow['passRate'] }}% pass)</div>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-white font-mono">{{ number_format($extScore, 2) }}</div>
                    <div class="text-xs text-white/70 mt-1 font-medium">External Works ({{ $externalRow['passRate'] }}% pass)</div>
                </div>
                <div>
                    <div class="text-2xl font-extrabold text-white font-mono">{{ $openDefects + $resolvedDefects }}</div>
                    <div class="text-xs text-white/70 mt-1 font-medium">Total Defects Logged</div>
                </div>
            </div>
        </div>

        {{-- Project Metadata --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 text-sm mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">domain</span>
                Project Information &amp; Audit Metadata
            </h2>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-5 text-sm">
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Developer</dt>
                    <dd class="text-gray-900 font-semibold">{{ $project->developer_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Contractor</dt>
                    <dd class="text-gray-900 font-semibold">{{ $project->contractor_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Total Units</dt>
                    <dd class="text-gray-900 font-semibold">{{ number_format($project->total_units) }} units</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Gross Floor Area (GFA)</dt>
                    <dd class="text-gray-900 font-semibold font-mono">{{ number_format($project->floor_area_sqm, 2) }} m²</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Lead Inspector</dt>
                    <dd class="text-gray-900 font-semibold">{{ $project->creator?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Audit Completion Date</dt>
                    <dd class="text-gray-900 font-semibold">{{ $project->updated_at ? $project->updated_at->format('d M Y') : now()->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">CIS 7:2021 Category</dt>
                    <dd class="text-gray-900 font-semibold">Category {{ $project->building_category }}</dd>
                </div>
            </dl>
        </div>

        {{-- QP Declarations --}}
        @php
            $qp = $project->qpDeclarations->keyBy('item_code');
            $qpItems = ['QP_SKIM_COAT' => 'Skim Coat or Prepacked Plaster', 'QP_WATER_TIGHTNESS' => 'Wet-area Water-tightness Test'];
        @endphp
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 text-sm mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">fact_check</span>
                QP Declarations (Material &amp; Functional Test)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($qpItems as $code => $label)
                    @php $decl = $qp->get($code); @endphp
                    <div class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-xl">
                        <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                        <span class="text-xs font-extrabold {{ $decl?->is_earned ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $decl?->is_earned ? 'Declared' : 'Not Declared' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Component Scores Breakdown Table --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center flex-wrap gap-2">
                <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">analytics</span>
                    Architectural Component Score Breakdown
                </h2>
                <span class="text-xs text-gray-500 font-mono font-semibold bg-gray-100 px-3 py-1 rounded-lg">S_comp = (Pass / Total) &times; Weightage</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-6 py-4 text-left">Component</th>
                            <th class="px-4 py-4 text-center">Weightage</th>
                            <th class="px-4 py-4 text-center">Pass</th>
                            <th class="px-4 py-4 text-center">Fail</th>
                            <th class="px-4 py-4 text-center">Pass Rate</th>
                            <th class="px-6 py-4 text-right">S_comp Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rows as $code => $row)
                            <tr class="{{ $row['fail'] > 0 ? 'bg-red-50/40' : 'hover:bg-gray-50/50' }} transition">
                                <td class="px-6 py-3.5">
                                    <span class="font-mono text-xs font-extrabold text-eids-primary bg-eids-primary/10 px-2 py-0.5 rounded-md inline-block mr-1.5">{{ $code }}</span>
                                    <span class="text-xs text-gray-800 font-semibold">{{ $row['name'] }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs font-mono text-gray-600 font-bold">{{ $row['weightage'] }}%</td>
                                <td class="px-4 py-3.5 text-center text-xs font-extrabold text-emerald-800">{{ $row['pass'] }}</td>
                                <td class="px-4 py-3.5 text-center text-xs font-extrabold {{ $row['fail'] > 0 ? 'text-red-800' : 'text-gray-400' }}">{{ $row['fail'] }}</td>
                                <td class="px-4 py-3.5 text-center text-xs font-bold text-gray-800">
                                    {{ number_format($row['passRate'], 1) }}%
                                </td>
                                <td class="px-6 py-3.5 text-right text-xs font-extrabold text-eids-primary font-mono text-base">
                                    {{ number_format($row['sComp'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-extrabold border-t-2 border-gray-200">
                            <td colspan="5" class="px-6 py-4 text-gray-900 text-sm">Architectural Subtotal (S_arch)</td>
                            <td class="px-6 py-4 text-right text-eids-primary text-base font-mono">{{ number_format($sArch, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detailed Findings: every FAILED checklist question --}}
        @if(!empty($findings))
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-500 text-lg">fact_check</span>
                        Detailed Findings: Failed Checklist Items ({{ count($findings) }})
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Component</th>
                                <th class="px-4 py-3.5 text-left">Location</th>
                                <th class="px-4 py-3.5 text-left">Question</th>
                                <th class="px-6 py-3.5 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($findings as $f)
                                <tr class="hover:bg-red-50/30">
                                    <td class="px-6 py-3.5 font-bold text-gray-900">{{ $f['component'] }}</td>
                                    <td class="px-4 py-3.5 text-gray-600">{{ $f['location'] }}</td>
                                    <td class="px-4 py-3.5 text-gray-800">
                                        {{ $f['question'] }}
                                        @if($f['value'] !== null)<span class="font-mono font-bold text-red-700">({{ $f['value'] }} mm)</span>@endif
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-500">{{ $f['remarks'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Defects Register Table --}}
        @if($project->defects->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-500 text-lg">warning</span>
                        Recorded Defects Register
                    </h2>
                    <span class="text-xs text-gray-600 font-bold">{{ $openDefects }} Open / {{ $resolvedDefects }} Resolved</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Component &amp; Location</th>
                                <th class="px-4 py-3.5 text-left">Description</th>
                                <th class="px-4 py-3.5 text-center">Severity</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($project->defects as $defect)
                                @php
                                    $sevCls = ['low' => 'bg-gray-100 text-gray-700 border-gray-300', 'medium' => 'bg-amber-100 text-amber-900 border-amber-300', 'high' => 'bg-red-100 text-red-900 border-red-300'];
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-3.5">
                                        <div class="font-bold text-gray-900 text-sm">{{ $defect->component_name }}</div>
                                        <div class="text-gray-500 text-xs mt-0.5 font-medium">{{ $defect->location }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 text-gray-800 leading-relaxed font-medium">{{ $defect->defect_description }}</td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex px-2.5 py-1 border rounded-full font-extrabold uppercase tracking-wider text-[10px] {{ $sevCls[$defect->severity] ?? '' }}">
                                            {{ $defect->severity }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span class="inline-flex px-2.5 py-1 border rounded-full font-extrabold uppercase tracking-wider text-[10px] {{ $defect->status_badge_class }}">
                                            {{ $defect->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Annex: Defect Photographic Evidence Gallery --}}
        @php
            $defectsWithPhotos = $project->defects->filter(fn($d) => !empty($d->photo_url));
        @endphp
        @if($defectsWithPhotos->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-eids-accent text-lg">photo_library</span>
                        Annex: Defect Photographic Evidence ({{ $defectsWithPhotos->count() }})
                    </h2>
                    <span class="text-xs text-gray-500 font-semibold">Photo Annex</span>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                    @foreach($defectsWithPhotos as $i => $defect)
                        @php
                            $sevCls = ['low' => 'bg-gray-100 text-gray-700 border-gray-300', 'medium' => 'bg-amber-100 text-amber-900 border-amber-300', 'high' => 'bg-red-100 text-red-900 border-red-300'];
                        @endphp
                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50/30 flex flex-col group">
                            <div class="relative aspect-4/3 bg-gray-100 overflow-hidden border-b border-gray-200">
                                <a href="{{ $defect->photo_url }}" target="_blank" title="Click to view full photo" class="block w-full h-full">
                                    <img src="{{ $defect->photo_url }}" alt="Defect photo for {{ $defect->component_name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" />
                                </a>
                                <span class="absolute top-2 left-2 bg-black/70 backdrop-blur-xs text-white text-[10px] font-extrabold px-2 py-0.5 rounded-md">
                                    Defect #{{ $loop->iteration }}
                                </span>
                            </div>
                            <div class="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="font-extrabold text-gray-900 text-xs mb-0.5">{{ $defect->component_name }}</div>
                                    <div class="text-gray-500 text-[11px] font-medium mb-2 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">location_on</span>
                                        {{ $defect->location }}
                                    </div>
                                    <p class="text-gray-700 text-xs line-clamp-3 leading-relaxed">{{ $defect->defect_description }}</p>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-200/60 flex items-center justify-between gap-1">
                                    <span class="inline-flex px-2 py-0.5 border rounded-full font-extrabold uppercase tracking-wider text-[9px] {{ $sevCls[$defect->severity] ?? '' }}">
                                        {{ $defect->severity }}
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 border rounded-full font-extrabold uppercase tracking-wider text-[9px] {{ $defect->status_badge_class }}">
                                        {{ $defect->status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bottom Action Bar --}}
        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
            <a href="{{ route('projects.score', $project) }}" class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
                &larr; Score Summary
            </a>
            <x-pdf-export-button :href="route('reports.pdf', $project)" :filename="$project->project_no" class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                Export Signed PDF Certificate &rarr;
            </x-pdf-export-button>
        </div>

    </div>
@endsection
