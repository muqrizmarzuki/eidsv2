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

    {{-- Next Action Hero Card --}}
    <x-next-action-card :action="$nextAction" />

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="1" :project="$project" :role="auth()->user()->role" />

    @php
        $statusMap = [
            'draf'              => ['Draft',         'bg-gray-100 text-gray-700 border-gray-300'],
            'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-900 border-amber-300'],
            'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-900 border-emerald-300'],
        ];
        [$sLabel, $sCls] = $statusMap[$project->status] ?? ['—', 'bg-gray-100 text-gray-500'];
        $typeMap = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
    @endphp

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
                @if(auth()->user()->canInspect())
                    <a href="{{ route('projects.components', $project) }}" class="text-eids-accent hover:text-eids-primary font-bold flex items-center gap-1">
                        Inspect Component Grid <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Case Ledger: the single source of truth for phase / responsible party / status --}}
    @php
        $readyToComplete = $project->status !== 'selesai' && $project->inspection_progress >= 100 && $openDefects === 0;
        $canMarkComplete = auth()->user()->isAdmin();

        $ledger = [
            [
                'state' => 'done',
                'name'  => 'Setup & Assignment',
                'meta'  => 'Created by <strong class="text-gray-800">' . ($project->creator?->name ?? 'Admin') . '</strong> · Assigned to <strong class="text-gray-800">' . ($project->assignedInspector?->name ?? 'Unassigned') . '</strong>',
                'link'  => auth()->user()->canInspect() ? ['label' => 'Configure Rooms', 'route' => route('projects.samples', $project)] : null,
            ],
            [
                'state' => $project->inspection_progress >= 100 ? 'done' : ($project->inspection_progress > 0 ? 'active' : 'pending'),
                'name'  => 'Field Inspection',
                'meta'  => 'Progress <strong class="text-gray-800">' . $project->inspection_progress . '%</strong> · ' . $project->assessments->count() . ' checks assessed',
                'link'  => auth()->user()->canInspect() ? ['label' => 'Open Components Grid', 'route' => route('projects.components', $project)] : null,
            ],
            [
                'state' => $openDefects > 0 ? 'attention' : ($resolvedDefects > 0 ? 'done' : 'pending'),
                'name'  => 'Defect Rectification',
                'meta'  => 'Open <strong class="' . ($openDefects > 0 ? 'text-red-700' : 'text-gray-800') . '">' . $openDefects . '</strong> · Resolved <strong class="text-emerald-700">' . $resolvedDefects . '</strong>',
                'link'  => ['label' => 'View Defect Register', 'route' => route('defects.index', ['project_id' => $project->id])],
            ],
            [
                'state' => $project->status === 'selesai' ? 'done' : ($readyToComplete ? 'active' : 'pending'),
                'name'  => 'Final Certificate',
                'meta'  => 'Score <strong class="text-gray-800">' . number_format($project->overall_score, 1) . '%</strong> · Rating <strong class="text-gray-800">' . $project->rating . '</strong>',
                'link'  => null,
            ],
        ];
    @endphp
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
        <div class="flex items-center justify-between mb-1 pb-4 border-b border-gray-100">
            <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">schema</span>
                Case Ledger
            </h2>
            <span class="text-xs font-extrabold text-eids-primary bg-eids-primary/10 px-3 py-1 rounded-full">
                Current: {{ $project->status_label }}
            </span>
        </div>

        <div>
            @foreach($ledger as $i => $row)
                @php
                    $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                    $chipCls = match($row['state']) {
                        'done'      => 'bg-emerald-50 border-emerald-300 text-emerald-700',
                        'active'    => 'bg-eids-primary border-eids-primary text-white',
                        'attention' => 'bg-red-50 border-red-300 text-red-700',
                        default     => 'bg-white border-gray-200 text-gray-400',
                    };
                    $rowBg = match($row['state']) {
                        'active'    => 'bg-eids-primary/5',
                        'attention' => 'bg-red-50/40',
                        default     => '',
                    };
                @endphp
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 py-3.5 {{ $i > 0 ? 'border-t border-dashed border-gray-200' : '' }} {{ $rowBg }} -mx-6 px-6">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="relative shrink-0 font-mono text-[11px] font-extrabold tabular-nums rounded px-1.5 py-0.5 border {{ $chipCls }}">
                            {{ $num }}
                            @if($row['state'] === 'done')
                                <span class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white shadow-2xs flex items-center justify-center -rotate-12">
                                    <svg viewBox="0 0 10 10" class="w-2.5 h-2.5" fill="none">
                                        <path d="M2 5.2L4 7.2L8 2.8" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-extrabold text-gray-900">{{ $row['name'] }}</div>
                            <div class="text-xs text-gray-500 font-medium mt-0.5">{!! $row['meta'] !!}</div>
                        </div>
                    </div>

                    <div class="shrink-0 pl-9 sm:pl-0 sm:text-right">
                        @if($i === 3)
                            @if($project->status === 'selesai')
                                <a href="{{ route('reports.show', $project) }}" class="inline-flex items-center gap-1 text-xs font-bold text-eids-primary hover:underline">
                                    Official PDF Certificate &rarr;
                                </a>
                            @elseif($readyToComplete && $canMarkComplete)
                                <form method="POST" action="{{ route('projects.complete', $project) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 min-h-[36px] bg-eids-primary text-white text-xs font-extrabold rounded-lg hover:bg-eids-dark transition">
                                        <span class="material-symbols-outlined text-sm">verified</span>
                                        Mark as Completed
                                    </button>
                                </form>
                            @elseif($readyToComplete)
                                <span class="text-xs font-bold text-eids-primary">Awaiting Admin sign-off</span>
                            @else
                                <span class="text-xs text-gray-400 font-medium">Locked</span>
                            @endif
                        @elseif($row['link'])
                            <a href="{{ $row['link']['route'] }}" class="inline-flex items-center gap-1 text-xs font-bold text-eids-accent hover:text-eids-primary hover:underline">
                                {{ $row['link']['label'] }} &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="space-y-6">

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

    {{-- Danger Zone: deliberately quiet — a rare, destructive action, not a competing card --}}
    @if(auth()->user()->isAdmin())
        <div class="flex items-center justify-between gap-4 px-1 pt-2">
            <p class="text-xs text-gray-400">Deleting a project permanently removes all its sample assessments and defect logs.</p>
            <button
                @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('projects.destroy', $project) }}', method: 'DELETE' })"
                class="shrink-0 inline-flex items-center gap-1.5 text-xs font-bold text-red-600 hover:text-red-700 hover:underline">
                <span class="material-symbols-outlined text-base">delete</span>
                Delete this project
            </button>
        </div>
    @endif
</div>
@endsection
