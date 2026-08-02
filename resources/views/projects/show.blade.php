@extends('layouts.app')

@section('title', $project->project_name)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold truncate max-w-48">{{ $project->project_name }}</span>
@endsection

    @php
        $nextAction = $project->nextActionFor(auth()->user());
    @endphp

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.edit', $project) }}"
           class="flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition min-h-[44px]">
            <span class="material-symbols-outlined text-lg">edit</span>
            Edit
        </a>
    @endif
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="1" :project="$project" :role="auth()->user()->role" />

    {{-- Next Action Banner --}}
    <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-2xl p-4 mb-6 flex items-center gap-3">
        <span class="material-symbols-outlined text-eids-accent text-2xl shrink-0">{{ $nextAction['icon'] }}</span>
        <span class="text-sm font-bold text-gray-900">{{ $nextAction['text'] }}</span>
    </div>

    @php
        $statusMap = [
            'draf'              => ['Draft',         'bg-gray-100 text-gray-700 border-gray-300'],
            'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-900 border-amber-300'],
            'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-900 border-emerald-300'],
        ];
        [$sLabel, $sCls] = $statusMap[$project->status] ?? ['—', 'bg-gray-100 text-gray-500'];
        $typeMap = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Header Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex px-3 py-1 border rounded-full text-xs font-bold {{ $sCls }}">{{ $sLabel }}</span>
                            <span class="text-xs text-gray-500 font-mono font-semibold">{{ $project->project_no }}</span>
                        </div>
                        <h1 class="text-2xl font-extrabold text-gray-900 leading-tight tracking-tight">{{ $project->project_name }}</h1>
                        @if($project->location)
                            <div class="flex items-center gap-1.5 mt-1.5 text-sm text-gray-600 font-medium">
                                <span class="material-symbols-outlined text-base text-eids-accent">location_on</span>
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
                        <div class="text-center border rounded-2xl px-6 py-3.5 {{ $scBg }} shadow-xs">
                            <div class="text-3xl lg:text-4xl font-extrabold {{ $scColor }}">{{ number_format($sc, 1) }}<span class="text-lg">%</span></div>
                            <div class="text-xs font-extrabold tracking-wider uppercase {{ $scColor }} mt-0.5">{{ $rating }} RATING</div>
                        </div>
                    @endif
                </div>

                {{-- Progress Bar --}}
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <div class="flex justify-between text-xs text-gray-600 font-bold mb-2">
                        <span>Inspection Progress</span>
                        <span>{{ $project->inspection_progress }}%</span>
                    </div>
                    <div class="h-3 bg-gray-100 rounded-full overflow-hidden border border-gray-200/50">
                        <div class="h-full bg-eids-accent rounded-full transition-all duration-300"
                             style="width: {{ $project->inspection_progress }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 flex justify-between items-center font-medium">
                        <span>{{ $project->samples->whereNotNull('pass_rate')->count() }} of {{ $project->samples->count() }} sample unit(s) inspected</span>
                        <a href="{{ route('projects.components', $project) }}" class="text-eids-accent hover:text-eids-primary font-bold flex items-center gap-1">
                            Inspect Component Grid <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Operational Role Handoff & Phase Status Card --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                    <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-eids-accent text-lg">schema</span>
                        Operational Role Handoff &amp; Project Phase
                    </h2>
                    <span class="text-xs font-extrabold text-eids-primary bg-eids-primary/10 px-3 py-1 rounded-full">
                        Current: {{ $project->status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Phase 1 --}}
                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/60 relative">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-6 h-6 rounded-full bg-eids-primary text-white text-[11px] font-extrabold flex items-center justify-center">1</span>
                            <span class="text-xs font-extrabold text-gray-900">Setup &amp; Assignment</span>
                        </div>
                        <p class="text-[11px] text-gray-600 font-medium">Created by: <strong>{{ $project->creator?->name ?? 'Admin' }}</strong></p>
                        <p class="text-[11px] text-gray-600 font-medium mt-0.5">Assigned to: <strong>{{ $project->assignedInspector?->name ?? 'Unassigned' }}</strong></p>
                        @if(auth()->user()->canInspect())
                            <a href="{{ route('projects.samples', $project) }}" class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-bold text-eids-accent hover:underline">
                                Configure Rooms &rarr;
                            </a>
                        @endif
                    </div>

                    {{-- Phase 2 --}}
                    <div class="p-4 rounded-xl border {{ $project->inspection_progress > 0 ? 'border-emerald-200 bg-emerald-50/40' : 'border-gray-200 bg-gray-50/60' }}">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-6 h-6 rounded-full {{ $project->inspection_progress > 0 ? 'bg-emerald-600' : 'bg-gray-400' }} text-white text-[11px] font-extrabold flex items-center justify-center">2</span>
                            <span class="text-xs font-extrabold text-gray-900">Field Inspection</span>
                        </div>
                        <p class="text-[11px] text-gray-600 font-medium">Progress: <strong>{{ $project->inspection_progress }}%</strong></p>
                        <p class="text-[11px] text-gray-600 font-medium mt-0.5">Assessed: <strong>{{ $project->assessments->count() }} checks</strong></p>
                        @if(auth()->user()->canInspect())
                            <a href="{{ route('projects.components', $project) }}" class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:underline">
                                Open Components Grid &rarr;
                            </a>
                        @endif
                    </div>

                    {{-- Phase 3 --}}
                    <div class="p-4 rounded-xl border {{ $openDefects > 0 ? 'border-red-200 bg-red-50/40' : ($resolvedDefects > 0 ? 'border-emerald-200 bg-emerald-50/40' : 'border-gray-200 bg-gray-50/60') }}">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-6 h-6 rounded-full {{ $openDefects > 0 ? 'bg-red-600' : ($resolvedDefects > 0 ? 'bg-emerald-600' : 'bg-gray-400') }} text-white text-[11px] font-extrabold flex items-center justify-center">3</span>
                            <span class="text-xs font-extrabold text-gray-900">Defect Rectification</span>
                        </div>
                        <p class="text-[11px] text-gray-600 font-medium">Open: <strong class="text-red-700">{{ $openDefects }}</strong> · Resolved: <strong class="text-emerald-700">{{ $resolvedDefects }}</strong></p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Contractor QC Repair Phase</p>
                        <a href="{{ route('defects.index', ['project_id' => $project->id]) }}" class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-bold text-gray-800 hover:underline">
                            View Defect Register &rarr;
                        </a>
                    </div>

                    {{-- Phase 4 --}}
                    <div class="p-4 rounded-xl border {{ $project->status === 'selesai' ? 'border-emerald-300 bg-emerald-50/60' : 'border-gray-200 bg-gray-50/60' }}">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-6 h-6 rounded-full {{ $project->status === 'selesai' ? 'bg-emerald-700' : 'bg-gray-400' }} text-white text-[11px] font-extrabold flex items-center justify-center">4</span>
                            <span class="text-xs font-extrabold text-gray-900">Final Certificate</span>
                        </div>
                        <p class="text-[11px] text-gray-600 font-medium">G-IDS Score: <strong>{{ number_format($project->overall_score, 1) }}%</strong></p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Rating: <strong>{{ $project->rating }}</strong></p>
                        <a href="{{ route('reports.show', $project) }}" class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-bold text-eids-primary hover:underline">
                            Official PDF Certificate &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Project Specifications --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm">
                    <span class="material-symbols-outlined text-eids-accent text-lg">info</span>
                    Project Specifications & G-IDS Parameters
                </h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-5 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Developer</dt>
                        <dd class="text-gray-900 font-semibold">{{ $project->developer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Contractor</dt>
                        <dd class="text-gray-900 font-semibold">{{ $project->contractor_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Building Type</dt>
                        <dd class="text-gray-900 font-semibold">{{ $typeMap[$project->building_type] ?? $project->building_type }}</dd>
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
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Calculated Sample Count (N)</dt>
                        <dd class="text-gray-900 font-semibold text-eids-accent font-mono">{{ $project->calculated_samples }} sample units</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Created By</dt>
                        <dd class="text-gray-900 font-semibold">{{ $project->creator?->name ?? 'System Admin' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Assigned Inspector</dt>
                        <dd class="text-gray-900 font-semibold flex items-center gap-1.5">
                            @if($project->assignedInspector)
                                <span class="material-symbols-outlined text-xs text-eids-accent">person</span>
                                {{ $project->assignedInspector->name }}
                            @else
                                <span class="text-gray-400 italic">Unassigned (All Inspectors)</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Created Date</dt>
                        <dd class="text-gray-900 font-semibold">{{ $project->created_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Sample Units List --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-eids-accent text-lg">home_work</span>
                        Sample Units List ({{ $project->samples->count() }})
                    </h2>
                    @if(auth()->user()->canInspect())
                        <a href="{{ route('projects.samples', $project) }}"
                           class="text-xs text-eids-accent hover:text-eids-primary font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">tune</span> Configure Location Names
                        </a>
                    @endif
                </div>

                @if($project->samples->isEmpty())
                    <div class="py-12 text-center text-sm text-gray-500 font-medium">No sample units defined yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200 font-bold">
                                <tr>
                                    <th class="px-6 py-3.5 text-left">Sample #</th>
                                    <th class="px-4 py-3.5 text-left">Location Name</th>
                                    <th class="px-4 py-3.5 text-left hidden sm:table-cell">Pass Rate</th>
                                    <th class="px-6 py-3.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($project->samples as $sample)
                                    @php
                                        $pr = $sample->pass_rate;
                                        $prCls = is_null($pr) ? 'text-gray-400' : ($pr >= 80 ? 'text-emerald-700 font-bold' : ($pr >= 60 ? 'text-amber-700 font-bold' : 'text-red-700 font-bold'));
                                    @endphp
                                    <tr class="hover:bg-gray-50/80 transition">
                                        <td class="px-6 py-4 text-gray-600 text-xs font-mono font-bold">#{{ $sample->sample_index }}</td>
                                        <td class="px-4 py-4 text-gray-900 font-semibold">{{ $sample->location_name }}</td>
                                        <td class="px-4 py-4 hidden sm:table-cell {{ $prCls }}">
                                            {{ is_null($pr) ? 'Pending Inspection' : number_format($pr, 1) . '%' }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if(auth()->user()->canInspect())
                                                <a href="{{ route('projects.inspect', [$project, $sample]) }}"
                                                   class="min-h-[44px] px-4 py-2 border border-gray-200 rounded-xl text-xs font-bold text-eids-primary hover:bg-eids-primary hover:text-white transition inline-flex items-center gap-1.5 shadow-2xs">
                                                    <span class="material-symbols-outlined text-base">edit_note</span>
                                                    {{ is_null($pr) ? 'Inspect' : 'Review' }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">

            {{-- Sequential Quick Actions --}}
            @if(auth()->user()->canInspect())
                <div class="bg-eids-primary rounded-2xl p-6 shadow-md text-white border border-white/10">
                    <div class="text-eids-light text-xs uppercase tracking-wider font-bold mb-4 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">alt_route</span>
                        Inspection Workflow Steps
                    </div>
                    <div class="space-y-3">
                        <a href="{{ route('projects.samples', $project) }}"
                           class="flex items-center gap-3 w-full p-3.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition text-sm font-semibold border border-white/10 min-h-[44px]">
                            <span class="w-7 h-7 rounded-full bg-white/20 text-white text-xs font-extrabold flex items-center justify-center shrink-0">1</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold">1. Configure Samples</div>
                                <div class="text-[11px] text-white/70 font-normal">Assign room &amp; location names</div>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-base">chevron_right</span>
                        </a>

                        <a href="{{ route('projects.components', $project) }}"
                           class="flex items-center gap-3 w-full p-3.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition text-sm font-semibold border border-white/10 min-h-[44px]">
                            <span class="w-7 h-7 rounded-full bg-white/20 text-white text-xs font-extrabold flex items-center justify-center shrink-0">2</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold">2. Components Grid</div>
                                <div class="text-[11px] text-white/70 font-normal">Inspect 5 checks per component</div>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-base">chevron_right</span>
                        </a>

                        <a href="{{ route('projects.score', $project) }}"
                           class="flex items-center gap-3 w-full p-3.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition text-sm font-semibold border border-white/10 min-h-[44px]">
                            <span class="w-7 h-7 rounded-full bg-white/20 text-white text-xs font-extrabold flex items-center justify-center shrink-0">3</span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold">3. G-IDS Score Breakdown</div>
                                <div class="text-[11px] text-white/70 font-normal">Review S_comp &amp; final rating</div>
                            </div>
                            <span class="material-symbols-outlined text-white/50 text-base">chevron_right</span>
                        </a>

                        <a href="{{ route('reports.show', $project) }}"
                           class="flex items-center gap-3 w-full p-3.5 bg-eids-accent/30 hover:bg-eids-accent/40 text-eids-light rounded-xl transition text-sm font-bold border border-eids-accent/40 min-h-[44px]">
                            <span class="w-7 h-7 rounded-full bg-eids-light/20 text-eids-light text-xs font-extrabold flex items-center justify-center shrink-0">4</span>
                            <div class="min-w-0 flex-1">
                                <div>4. Download PDF Report</div>
                                <div class="text-[11px] text-eids-light/80 font-normal">Signed G-IDS inspection certificate</div>
                            </div>
                            <span class="material-symbols-outlined text-eids-light text-base">description</span>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Defect Summary --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-xs uppercase tracking-wider text-gray-500 font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-red-500 text-base">warning</span>
                        Defects Breakdown
                    </div>
                    <a href="{{ route('defects.index', ['project_id' => $project->id]) }}"
                       class="text-xs text-eids-accent hover:text-eids-primary font-bold">View All</a>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-2">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 text-center">
                        <div class="text-2xl font-extrabold text-red-700">{{ $openDefects }}</div>
                        <div class="text-xs font-bold text-red-600 mt-0.5">Open Defects</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 text-center">
                        <div class="text-2xl font-extrabold text-emerald-700">{{ $resolvedDefects }}</div>
                        <div class="text-xs font-bold text-emerald-600 mt-0.5">Resolved</div>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            @if(auth()->user()->isAdmin())
                <div class="bg-white rounded-2xl border border-red-200 shadow-xs p-6">
                    <div class="text-xs uppercase tracking-wider text-red-600 font-extrabold mb-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">delete_forever</span>
                        Danger Zone
                    </div>
                    <p class="text-xs text-gray-600 mb-4 leading-relaxed">Permanently delete this project and all associated sample assessments and defect logs.</p>
                    <button
                        @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('projects.destroy', $project) }}', method: 'DELETE' })"
                        class="w-full min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 border border-red-300 text-red-700 text-xs font-extrabold rounded-xl hover:bg-red-50 transition shadow-2xs">
                        <span class="material-symbols-outlined text-base">delete</span>
                        Delete Project Permanently
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
