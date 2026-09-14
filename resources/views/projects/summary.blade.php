@extends('layouts.app')

@section('title', 'Inspection Summary')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Summary</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('reports.show', $project) }}"
       class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition">
        <span class="material-symbols-outlined text-base">description</span>
        Generate Report
    </a>
@endsection

@section('content')
    @php
        $ratingMap = ['GOOD' => 'Good', 'MODERATE' => 'Moderate', 'WEAK' => 'Weak'];
        $statusMap = [
            'draf'              => 'Draft',
            'dalam_pemeriksaan' => 'In Inspection',
            'selesai'           => 'Completed',
        ];
        $typeMap = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">

            {{-- Project Info Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 text-sm mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-base">domain</span>
                    Project Information
                </h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Ref No.</dt>
                        <dd class="font-mono text-gray-700 mt-0.5">{{ $project->project_no }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Status</dt>
                        <dd class="mt-0.5 text-gray-700">{{ $statusMap[$project->status] ?? $project->status }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Project Name</dt>
                        <dd class="font-semibold text-gray-800 mt-0.5">{{ $project->project_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Developer</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $project->developer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Contractor</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $project->contractor_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Building Type</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $typeMap[$project->building_type] ?? $project->building_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">GFA</dt>
                        <dd class="text-gray-700 mt-0.5">{{ number_format($project->floor_area_sqm, 2) }} m²</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Inspected By</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $project->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-medium">Last Updated</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $project->updated_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Score Table --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-semibold text-gray-800 text-sm">Architectural Score Breakdown</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Component</th>
                            <th class="px-4 py-3 text-center font-medium">Max</th>
                            <th class="px-4 py-3 text-center font-medium">Pass Rate</th>
                            <th class="px-4 py-3 text-center font-medium">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($rows as $code => $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-gray-800 text-xs">{{ $code }}</div>
                                    <div class="text-xs text-gray-400">{{ $row['name'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $row['weightage'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($row['total'] > 0)
                                        <span class="{{ $row['passRate'] >= 80 ? 'text-emerald-600' : ($row['passRate'] >= 60 ? 'text-amber-600' : 'text-red-600') }} font-semibold text-xs">
                                            {{ number_format($row['passRate'], 1) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-sm text-gray-800">
                                    {{ number_format($row['sComp'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 border-t-2 border-gray-200">
                            <td class="px-5 py-2.5 font-semibold text-gray-600 text-xs" colspan="3">S_arch (Architectural)</td>
                            <td class="px-4 py-2.5 text-center font-bold text-eids-primary">{{ number_format($sArch, 2) }}</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td class="px-5 py-2 text-gray-400 text-xs" colspan="3">M&amp;E Fittings ({{ $meRow['passRate'] }}% pass, {{ $meRow['pass'] }}/{{ $meRow['total'] }})</td>
                            <td class="px-4 py-2 text-center text-blue-600 font-semibold">{{ number_format($meScore, 2) }}</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td class="px-5 py-2 text-gray-400 text-xs" colspan="3">External Works ({{ $externalRow['passRate'] }}% pass, {{ $externalRow['pass'] }}/{{ $externalRow['total'] }})</td>
                            <td class="px-4 py-2 text-center text-purple-600 font-semibold">{{ number_format($extScore, 2) }}</td>
                        </tr>
                        <tr class="bg-eids-primary text-white">
                            <td class="px-5 py-3.5 font-bold text-sm" colspan="3">E-IDS Total Score</td>
                            <td class="px-4 py-3.5 text-center font-bold text-xl">{{ number_format($totalScore, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Sample Results --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-semibold text-gray-800 text-sm">Sample Unit Results</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">#</th>
                            <th class="px-4 py-3 text-left font-medium">Location</th>
                            <th class="px-4 py-3 text-center font-medium">Assessed</th>
                            <th class="px-4 py-3 text-center font-medium">Pass</th>
                            <th class="px-4 py-3 text-center font-medium">Fail</th>
                            <th class="px-4 py-3 text-center font-medium">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($project->samples as $sample)
                            @php
                                $total = $sample->assessments->count();
                                $pass  = $sample->assessments->where('overall_sample_status', 'PASS')->count();
                                $fail  = $total - $pass;
                                $pr    = $total > 0 ? round(($pass/$total)*100, 1) : null;
                            @endphp
                            <tr>
                                <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $sample->sample_index }}</td>
                                <td class="px-4 py-3.5 font-medium text-gray-700">{{ $sample->location_name }}</td>
                                <td class="px-4 py-3.5 text-center text-gray-600">{{ $total }}/{{ count($rows) }}</td>
                                <td class="px-4 py-3.5 text-center text-emerald-600 font-semibold">{{ $pass }}</td>
                                <td class="px-4 py-3.5 text-center {{ $fail > 0 ? 'text-red-500 font-semibold' : 'text-gray-300' }}">{{ $fail }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    @if(!is_null($pr))
                                        <span class="{{ $pr >= 80 ? 'text-emerald-600' : ($pr >= 60 ? 'text-amber-600' : 'text-red-600') }} font-bold text-sm">
                                            {{ $pr }}%
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Detailed Findings --}}
            @if(!empty($findings))
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-50">
                        <h2 class="font-semibold text-gray-800 text-sm">Detailed Findings: Failed Items ({{ count($findings) }})</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium">Component</th>
                                <th class="px-4 py-3 text-left font-medium">Location</th>
                                <th class="px-4 py-3 text-left font-medium">Question</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($findings as $f)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-700 text-xs">{{ $f['component'] }}</td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $f['location'] }}</td>
                                    <td class="px-4 py-3 text-gray-700 text-xs">
                                        {{ $f['question'] }}
                                        @if($f['value'] !== null)<span class="font-mono font-bold text-red-600">({{ $f['value'] }} mm)</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="space-y-5">
            {{-- Rating badge --}}
            <div class="bg-eids-primary rounded-2xl p-6 text-white text-center">
                <div class="text-xs text-white/50 uppercase tracking-wider mb-3">E-IDS Rating</div>
                <div class="text-5xl font-bold mb-2">{{ number_format($totalScore, 2) }}</div>
                <div class="px-5 py-1.5 rounded-full text-sm font-bold inline-block
                    @if($rating === 'GOOD') bg-emerald-500
                    @elseif($rating === 'MODERATE') bg-amber-400
                    @else bg-red-500 @endif text-white">
                    {{ $ratingMap[$rating] ?? $rating }}
                </div>
                <div class="mt-4 pt-4 border-t border-white/10 grid grid-cols-3 gap-2 text-center text-xs">
                    <div>
                        <div class="{{ $totalScore >= $ratingBaik ? 'text-eids-light font-bold' : 'text-white/30' }}">≥ {{ $ratingBaik }}</div>
                        <div class="text-white/40 mt-0.5">Good</div>
                    </div>
                    <div>
                        <div class="{{ $totalScore >= $ratingMod && $totalScore < $ratingBaik ? 'text-amber-300 font-bold' : 'text-white/30' }}">≥ {{ $ratingMod }}</div>
                        <div class="text-white/40 mt-0.5">Moderate</div>
                    </div>
                    <div>
                        <div class="{{ $totalScore < $ratingMod ? 'text-red-400 font-bold' : 'text-white/30' }}">&lt; {{ $ratingMod }}</div>
                        <div class="text-white/40 mt-0.5">Weak</div>
                    </div>
                </div>
            </div>

            {{-- Defects --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-3">Defect Summary</div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $openDefects }}</div>
                        <div class="text-xs text-red-400 mt-0.5">Open</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-center">
                        <div class="text-2xl font-bold text-emerald-600">{{ $resolvedDefects }}</div>
                        <div class="text-xs text-emerald-400 mt-0.5">Resolved</div>
                    </div>
                </div>

                @foreach($project->defects->take(6) as $defect)
                    @php $sevCls = ['low' => 'bg-gray-100 text-gray-500', 'medium' => 'bg-amber-100 text-amber-700', 'high' => 'bg-red-100 text-red-600']; @endphp
                    <div class="flex items-center gap-2 py-1.5 border-b border-gray-50 last:border-0 text-xs">
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $sevCls[$defect->severity] ?? '' }}">
                            {{ ucfirst($defect->severity) }}
                        </span>
                        <span class="flex-1 truncate text-gray-600">{{ $defect->component_name }}</span>
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium {{ $defect->status_badge_class }}">
                            {{ $defect->status_label }}
                        </span>
                    </div>
                @endforeach

                <a href="{{ route('defects.index', ['project_id' => $project->id]) }}"
                   class="mt-3 flex items-center justify-center gap-1 text-xs text-eids-accent font-medium hover:underline">
                    View All Defects <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-2">
                <div class="text-xs uppercase tracking-wider text-gray-400 font-medium mb-3">Actions</div>
                <a href="{{ route('projects.score', $project) }}"
                   class="flex items-center gap-2 w-full px-4 py-2.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-base text-eids-accent">score</span>
                    Detailed Score
                </a>
                <a href="{{ route('reports.show', $project) }}"
                   class="flex items-center gap-2 w-full px-4 py-2.5 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition">
                    <span class="material-symbols-outlined text-base">description</span>
                    Generate Report
                </a>
                <x-pdf-export-button :href="route('reports.pdf', $project)" :filename="$project->project_no"
                   class="flex items-center gap-2 w-full px-4 py-2.5 border border-eids-accent/30 text-eids-primary text-sm font-medium rounded-lg hover:bg-eids-accent/5 transition">
                    <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                    Download PDF
                </x-pdf-export-button>
            </div>
        </div>
    </div>
@endsection
