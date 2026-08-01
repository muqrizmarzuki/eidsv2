@extends('layouts.app')

@section('title', $project->project_name)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-600">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium truncate max-w-48">{{ $project->project_name }}</span>
@endsection

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.edit', $project) }}"
           class="flex items-center gap-1.5 px-3.5 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">
            <span class="material-symbols-outlined text-base">edit</span>
            Edit
        </a>
        <a href="{{ route('projects.samples', $project) }}"
           class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition shadow-xs">
            <span class="material-symbols-outlined text-base">search</span>
            Start Inspection
        </a>
    @endif
@endsection

@section('content')

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="1" :project="$project" />

    @php
        $statusMap = [
            'draf'              => ['Draft',         'bg-gray-100 text-gray-500 border-gray-200'],
            'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-800 border-amber-200'],
            'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-800 border-emerald-200'],
        ];
        [$sLabel, $sCls] = $statusMap[$project->status] ?? ['—', 'bg-gray-100 text-gray-400'];
        $typeMap = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Header card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex px-2.5 py-0.5 border rounded-full text-xs font-semibold {{ $sCls }}">{{ $sLabel }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $project->project_no }}</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 leading-tight">{{ $project->project_name }}</h1>
                        @if($project->location)
                            <div class="flex items-center gap-1 mt-1 text-sm text-gray-500">
                                <span class="material-symbols-outlined text-sm">location_on</span>
                                {{ $project->location }}
                            </div>
                        @endif
                    </div>
                    @if($project->overall_score > 0)
                        @php
                            $sc = $project->overall_score;
                            $scColor = $sc >= 85 ? 'text-emerald-700' : ($sc >= 70 ? 'text-amber-700' : 'text-red-700');
                            $scBg    = $sc >= 85 ? 'bg-emerald-50 border-emerald-200' : ($sc >= 70 ? 'bg-amber-50 border-amber-200' : 'bg-red-50 border-red-200');
                            $rating  = $sc >= 85 ? 'GOOD' : ($sc >= 70 ? 'MODERATE' : 'WEAK');
                        @endphp
                        <div class="text-center border rounded-2xl px-6 py-3 {{ $scBg }} shadow-2xs">
                            <div class="text-3xl font-extrabold {{ $scColor }}">{{ number_format($sc, 1) }}<span class="text-lg">%</span></div>
                            <div class="text-xs font-extrabold tracking-wider uppercase {{ $scColor }} mt-0.5">{{ $rating }}</div>
                        </div>
                    @endif
                </div>

                {{-- Progress bar --}}
                <div class="mt-5">
                    <div class="flex justify-between text-xs text-gray-500 font-medium mb-1.5">
                        <span>Inspection Progress</span>
                        <span>{{ $project->inspection_progress }}%</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-eids-accent rounded-full transition-all"
                             style="width: {{ $project->inspection_progress }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1.5 flex justify-between">
                        <span>{{ $project->samples->whereNotNull('pass_rate')->count() }} of {{ $project->samples->count() }} sample unit(s) inspected</span>
                        <a href="{{ route('projects.components', $project) }}" class="text-eids-accent hover:underline font-semibold">Inspect Grid &rarr;</a>
                    </div>
                </div>
            </div>

            {{-- Project Details --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-eids-accent text-base">info</span>
                    Project Specifications
                </h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Developer</dt>
                        <dd class="text-gray-800 font-semibold">{{ $project->developer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Contractor</dt>
                        <dd class="text-gray-800 font-semibold">{{ $project->contractor_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Building Type</dt>
                        <dd class="text-gray-800 font-semibold">{{ $typeMap[$project->building_type] ?? $project->building_type }}</dd>
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
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Calculated Samples (N)</dt>
                        <dd class="text-gray-800 font-semibold">{{ $project->calculated_samples }} units</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Created By</dt>
                        <dd class="text-gray-800 font-semibold">{{ $project->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Created At</dt>
                        <dd class="text-gray-800 font-semibold">{{ $project->created_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Sample List --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-eids-accent text-base">home_work</span>
                        Sample Units ({{ $project->samples->count() }})
                    </h2>
                    @if(auth()->user()->canInspect())
                        <a href="{{ route('projects.samples', $project) }}"
                           class="text-xs text-eids-accent hover:underline font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">tune</span> Configure Samples
                        </a>
                    @endif
                </div>

                @if($project->samples->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-400">No samples defined yet.</div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/80 text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium">#</th>
                                <th class="px-4 py-3 text-left font-medium">Location</th>
                                <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">Pass Rate</th>
                                <th class="px-4 py-3 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($project->samples as $sample)
                                @php
                                    $pr = $sample->pass_rate;
                                    $prCls = is_null($pr) ? 'text-gray-300' : ($pr >= 80 ? 'text-emerald-700 font-bold' : ($pr >= 60 ? 'text-amber-700 font-bold' : 'text-red-700 font-bold'));
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-3.5 text-gray-400 text-xs font-mono">#{{ $sample->sample_index }}</td>
                                    <td class="px-4 py-3.5 text-gray-800 font-semibold">{{ $sample->location_name }}</td>
                                    <td class="px-4 py-3.5 hidden sm:table-cell {{ $prCls }}">
                                        {{ is_null($pr) ? 'Pending' : number_format($pr, 1) . '%' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        @if(auth()->user()->canInspect())
                                            <a href="{{ route('projects.inspect', [$project, $sample]) }}"
                                               class="min-h-[36px] px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold text-eids-primary hover:bg-eids-primary hover:text-white transition inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">edit_note</span>
                                                {{ is_null($pr) ? 'Inspect' : 'Review' }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-5">

            {{-- Sequential Quick Actions --}}
            @if(auth()->user()->canInspect())
                <div class="bg-eids-primary rounded-2xl p-5 shadow-sm text-white">
                    <div class="text-white/60 text-xs uppercase tracking-wider font-bold mb-3 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-eids-light">alt_route</span>
                        Sequential Workflow Steps
                    </div>
                    <div class="space-y-2.5">
                        <a href="{{ route('projects.samples', $project) }}"
                           class="flex items-center gap-3 w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition text-sm font-semibold border border-white/5">
                            <span class="w-6 h-6 rounded-full bg-white/20 text-white text-xs font-extrabold flex items-center justify-center shrink-0">1</span>
                            <div class="min-w-0 flex-1">
                                <div>Configure Samples</div>
                                <div class="text-[10px] text-white/50 font-normal">Assign room &amp; location names</div>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-base">chevron_right</span>
                        </a>

                        <a href="{{ route('projects.components', $project) }}"
                           class="flex items-center gap-3 w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition text-sm font-semibold border border-white/5">
                            <span class="w-6 h-6 rounded-full bg-white/20 text-white text-xs font-extrabold flex items-center justify-center shrink-0">2</span>
                            <div class="min-w-0 flex-1">
                                <div>Component Grid</div>
                                <div class="text-[10px] text-white/50 font-normal">Inspect 5 checks per component</div>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-base">chevron_right</span>
                        </a>

                        <a href="{{ route('projects.score', $project) }}"
                           class="flex items-center gap-3 w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition text-sm font-semibold border border-white/5">
                            <span class="w-6 h-6 rounded-full bg-white/20 text-white text-xs font-extrabold flex items-center justify-center shrink-0">3</span>
                            <div class="min-w-0 flex-1">
                                <div>G-IDS Score Breakdown</div>
                                <div class="text-[10px] text-white/50 font-normal">Review S_comp &amp; final rating</div>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-base">chevron_right</span>
                        </a>

                        <a href="{{ route('reports.show', $project) }}"
                           class="flex items-center gap-3 w-full px-4 py-3 bg-eids-accent/25 hover:bg-eids-accent/40 text-eids-light rounded-xl transition text-sm font-bold border border-eids-accent/30">
                            <span class="w-6 h-6 rounded-full bg-eids-light/20 text-eids-light text-xs font-extrabold flex items-center justify-center shrink-0">4</span>
                            <div class="min-w-0 flex-1">
                                <div>Download Formal PDF Report</div>
                                <div class="text-[10px] text-eids-light/70 font-normal">Signed G-IDS inspection certificate</div>
                            </div>
                            <span class="material-symbols-outlined text-eids-light text-base">description</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Defect Summary --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-xs uppercase tracking-wider text-gray-400 font-bold">Defects Breakdown</div>
                    <a href="{{ route('defects.index', ['project_id' => $project->id]) }}"
                       class="text-xs text-eids-accent hover:underline font-semibold">View All</a>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-red-700">{{ $openDefects }}</div>
                        <div class="text-xs font-semibold text-red-500 mt-0.5">Open Defects</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-emerald-700">{{ $resolvedDefects }}</div>
                        <div class="text-xs font-semibold text-emerald-500 mt-0.5">Resolved</div>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            @if(auth()->user()->isAdmin())
                <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-5">
                    <div class="text-xs uppercase tracking-wider text-red-500 font-bold mb-2">Danger Zone</div>
                    <p class="text-xs text-gray-500 mb-3 leading-relaxed">Permanently delete this project and all associated sample assessments and defect logs.</p>
                    <button
                        @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('projects.destroy', $project) }}', method: 'DELETE' })"
                        class="w-full min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 border border-red-200 text-red-600 text-xs font-bold rounded-xl hover:bg-red-50 transition">
                        <span class="material-symbols-outlined text-base">delete_forever</span>
                        Delete Project Permanently
                    </button>
                </div>
            @endif
        </div>
    </div>

@endsection
