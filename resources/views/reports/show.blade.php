@extends('layouts.app')

@section('title', 'Inspection Report — ' . $project->project_no)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">G-IDS Report</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('reports.pdf', $project) }}"
       class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition shadow-xs">
        <span class="material-symbols-outlined text-base">picture_as_pdf</span>
        Download PDF Report &rarr;
    </a>
@endsection

@section('content')
    @php
        $ratingMap  = ['GOOD' => 'Good Rating', 'MODERATE' => 'Moderate Rating', 'WEAK' => 'Weak Rating'];
        $statusMap  = ['draf' => 'Draft', 'dalam_pemeriksaan' => 'In Inspection', 'selesai' => 'Completed'];
        $typeMap    = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
        $ratingCls  = ['GOOD' => 'bg-emerald-500', 'MODERATE' => 'bg-amber-400', 'WEAK' => 'bg-red-500'];
    @endphp

    <div class="max-w-4xl mx-auto">

        {{-- Report Header --}}
        <div class="bg-eids-primary rounded-2xl p-6 mb-6 text-white shadow-sm">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="text-xs text-white/50 uppercase tracking-widest font-bold mb-1">Formal G-IDS Inspection Report</div>
                    <h1 class="text-2xl font-bold text-white">{{ $project->project_name }}</h1>
                    <div class="text-white/70 text-sm mt-1 font-medium">{{ $project->project_no }} &nbsp;·&nbsp; {{ $typeMap[$project->building_type] ?? $project->building_type }}</div>
                    @if($project->location)
                        <div class="text-white/50 text-xs mt-0.5">{{ $project->location }}</div>
                    @endif
                </div>
                <div class="text-center bg-white/10 rounded-2xl px-6 py-4 backdrop-blur-xs">
                    <div class="text-xs text-white/60 font-semibold mb-1 uppercase tracking-wider">Final G-IDS Score</div>
                    <div class="text-4xl font-extrabold text-white">{{ number_format($totalScore, 2) }}</div>
                    <div class="mt-2 px-3 py-1 rounded-full text-xs font-extrabold inline-block {{ $ratingCls[$rating] ?? 'bg-gray-500' }} text-white uppercase">
                        {{ $ratingMap[$rating] ?? $rating }}
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-5 border-t border-white/10 text-center">
                <div>
                    <div class="text-xl font-bold text-white">{{ number_format($sArch, 2) }}</div>
                    <div class="text-xs text-white/50 mt-0.5">Architectural Subtotal</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-eids-light">{{ number_format($meScore, 2) }}</div>
                    <div class="text-xs text-white/50 mt-0.5">M&amp;E Work (Fixed)</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-white">{{ number_format($extScore, 2) }}</div>
                    <div class="text-xs text-white/50 mt-0.5">External Work (Fixed)</div>
                </div>
                <div>
                    <div class="text-xl font-bold text-white">{{ $openDefects + $resolvedDefects }}</div>
                    <div class="text-xs text-white/50 mt-0.5">Total Defects Logged</div>
                </div>
            </div>
        </div>

        {{-- Project Details --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
            <h2 class="font-bold text-gray-900 text-sm mb-4">Project Information &amp; Audit Metadata</h2>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Developer</dt>
                    <dd class="text-gray-800 font-semibold">{{ $project->developer_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Contractor</dt>
                    <dd class="text-gray-800 font-semibold">{{ $project->contractor_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Total Units</dt>
                    <dd class="text-gray-800 font-semibold">{{ number_format($project->total_units) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Floor Area (GFA)</dt>
                    <dd class="text-gray-800 font-semibold">{{ number_format($project->floor_area_sqm, 2) }} m²</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Lead Inspector</dt>
                    <dd class="text-gray-800 font-semibold">{{ $project->creator?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Inspection Date</dt>
                    <dd class="text-gray-800 font-semibold">{{ $project->updated_at ? $project->updated_at->format('d M Y') : now()->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Component Scores --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-gray-50 flex justify-between items-center">
                <h2 class="font-bold text-gray-900 text-sm">Architectural Component Score Breakdown</h2>
                <span class="text-xs text-gray-400 font-mono">S_comp = (Pass / Total) &times; Weightage</span>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left font-medium">Component</th>
                        <th class="px-4 py-3 text-center font-medium">Weightage</th>
                        <th class="px-4 py-3 text-center font-medium">Pass</th>
                        <th class="px-4 py-3 text-center font-medium">Fail</th>
                        <th class="px-4 py-3 text-center font-medium">Pass Rate</th>
                        <th class="px-4 py-3 text-right font-medium">S_comp Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($rows as $code => $row)
                        <tr class="{{ $row['fail'] > 0 ? 'bg-red-50/30' : '' }}">
                            <td class="px-5 py-3">
                                <span class="font-mono text-xs font-bold text-gray-800">{{ $code }}</span>
                                <span class="text-xs text-gray-600 ml-1.5">— {{ $row['name'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-xs font-mono text-gray-500">{{ $row['weightage'] }}%</td>
                            <td class="px-4 py-3 text-center text-xs font-bold text-emerald-700">{{ $row['pass'] }}</td>
                            <td class="px-4 py-3 text-center text-xs font-bold {{ $row['fail'] > 0 ? 'text-red-700' : 'text-gray-400' }}">{{ $row['fail'] }}</td>
                            <td class="px-4 py-3 text-center text-xs font-semibold text-gray-700">
                                {{ number_format($row['passRate'], 1) }}%
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-bold text-eids-primary">
                                {{ number_format($row['sComp'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 font-bold border-t border-gray-200">
                        <td colspan="5" class="px-5 py-3 text-gray-800 text-xs">Architectural Subtotal (S_arch)</td>
                        <td class="px-4 py-3 text-right text-eids-primary text-sm">{{ number_format($sArch, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Defects --}}
        @if($project->defects->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 text-sm">Recorded Defects Register</h2>
                    <span class="text-xs text-gray-500 font-semibold">{{ $openDefects }} Open / {{ $resolvedDefects }} Resolved</span>
                </div>
                <table class="w-full text-xs">
                    <thead class="bg-gray-50/80 text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Component &amp; Location</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                            <th class="px-4 py-3 text-center font-medium">Severity</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($project->defects as $defect)
                            @php
                                $sevCls = ['low' => 'bg-gray-100 text-gray-700', 'medium' => 'bg-amber-100 text-amber-800', 'high' => 'bg-red-100 text-red-800'];
                                $stCls  = ['OPEN' => 'bg-red-100 text-red-700', 'IN_PROGRESS' => 'bg-amber-100 text-amber-700', 'RESOLVED' => 'bg-emerald-100 text-emerald-700'];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <div class="font-bold text-gray-800">{{ $defect->component_name }}</div>
                                    <div class="text-gray-400 text-[10px] mt-0.5">{{ $defect->location }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 leading-relaxed">{{ $defect->defect_description }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full font-bold uppercase tracking-wider text-[10px] {{ $sevCls[$defect->severity] ?? '' }}">
                                        {{ $defect->severity }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full font-bold uppercase tracking-wider text-[10px] {{ $stCls[$defect->status] ?? '' }}">
                                        {{ $defect->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Bottom PDF action --}}
        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <a href="{{ route('projects.score', $project) }}" class="min-h-[44px] px-4 py-2.5 text-xs font-bold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-1.5">
                &larr; Score Summary
            </a>
            <a href="{{ route('reports.pdf', $project) }}" class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                Export Signed PDF Certificate &rarr;
            </a>
        </div>

    </div>
@endsection
