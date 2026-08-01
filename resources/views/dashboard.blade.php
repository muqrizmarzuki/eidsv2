@extends('layouts.app')

@section('title', 'Dashboard')

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.create') }}"
           class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition shadow-xs">
            <span class="material-symbols-outlined text-base">add</span>
            New Project
        </a>
    @endif
@endsection

@section('content')

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="Total Projects"   value="{{ $total }}"      icon="folder_open"     color="blue" />
        <x-stat-card label="In Inspection"    value="{{ $active }}"     icon="pending_actions" color="amber" />
        <x-stat-card label="Completed"        value="{{ $completed }}"  icon="task_alt"        color="emerald" />
        <x-stat-card label="Draft"            value="{{ $draft }}"      icon="draft"           color="gray" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent projects table --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-gray-900 text-sm">Recent Inspection Projects</h2>
                <a href="{{ route('projects.index') }}" class="text-xs text-eids-accent hover:underline font-semibold">View All Projects &rarr;</a>
            </div>

            @if($recent->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <span class="material-symbols-outlined text-gray-300 text-6xl mb-3">domain</span>
                    <p class="text-sm text-gray-500 font-semibold">No inspection projects yet</p>
                    <p class="text-xs text-gray-400 mt-1">Start by registering your first G-IDS residential project.</p>
                    @if(auth()->user()->canInspect())
                        <a href="{{ route('projects.create') }}"
                           class="mt-4 min-h-[44px] px-5 py-2.5 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition shadow-md inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">add</span> Register Project
                        </a>
                    @endif
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80 text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium">Project</th>
                            <th class="px-4 py-3 text-left font-medium hidden md:table-cell">Ref No.</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium hidden lg:table-cell">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recent as $project)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="font-bold text-gray-800 hover:text-eids-primary transition">
                                        {{ $project->project_name }}
                                    </a>
                                    <div class="text-xs text-gray-400 mt-0.5 md:hidden font-mono">{{ $project->project_no }}</div>
                                </td>
                                <td class="px-4 py-3.5 hidden md:table-cell text-gray-500 text-xs font-mono">{{ $project->project_no }}</td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $map = [
                                            'draf'              => ['Draft',         'bg-gray-100 text-gray-500 border-gray-200'],
                                            'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-800 border-amber-200'],
                                            'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-800 border-emerald-200'],
                                        ];
                                        [$lbl, $cls] = $map[$project->status] ?? ['—', 'bg-gray-100 text-gray-400 border-gray-200'];
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 border rounded-full text-xs font-semibold {{ $cls }}">{{ $lbl }}</span>
                                </td>
                                <td class="px-4 py-3.5 hidden lg:table-cell">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-eids-accent rounded-full" style="width: {{ $project->inspection_progress }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 font-bold w-10 text-right">{{ $project->inspection_progress }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Right panel --}}
        <div class="space-y-4">
            <div class="bg-eids-primary rounded-2xl p-5 text-white shadow-sm">
                <div class="text-xs uppercase tracking-wider text-white/60 font-bold mb-3">Average G-IDS Performance</div>
                @if($avgScore > 0)
                    <div class="text-4xl font-extrabold mb-1">{{ number_format($avgScore, 1) }}<span class="text-xl text-white/50">%</span></div>
                    @php
                        [$rtg, $rtgCls] = $avgScore >= $ratingBaik ? ['GOOD RATING', 'bg-emerald-500 text-white'] : ($avgScore >= $ratingMod ? ['MODERATE RATING', 'bg-amber-400 text-amber-950'] : ['WEAK RATING', 'bg-red-500 text-white']);
                        $archRatio = min(100, round(($avgScore / max(1, $ratingBaik)) * 100));
                    @endphp
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wider uppercase {{ $rtgCls }}">{{ $rtg }}</span>
                    <div class="mt-4 space-y-3">
                        <div>
                            <div class="flex justify-between text-xs text-white/70 mb-1">
                                <span>Architectural Score</span>
                                <span class="font-semibold">{{ number_format($avgScore, 1) }} / {{ number_format($ratingBaik, 0) }}% target</span>
                            </div>
                            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full bg-eids-accent rounded-full transition-all" style="width: {{ $archRatio }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-white/70 mb-1">
                                <span>M&amp;E Fixed Component</span>
                                <span class="font-semibold">{{ number_format($meScore, 2) }} pts</span>
                            </div>
                            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full bg-eids-accent rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-white/50 text-sm py-4">No inspection data recorded yet.</div>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-3">Global Defect Overview</div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-red-700">{{ $openDefects }}</div>
                        <div class="text-xs font-semibold text-red-500 mt-0.5">Open</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-emerald-700">{{ $resolvedDefects }}</div>
                        <div class="text-xs font-semibold text-emerald-500 mt-0.5">Resolved</div>
                    </div>
                </div>
                <a href="{{ route('defects.index') }}"
                   class="mt-4 flex items-center justify-center gap-1.5 text-xs text-eids-accent font-bold hover:underline min-h-[38px] bg-emerald-50/50 rounded-xl border border-emerald-100">
                    View Defect Register <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>

@endsection
