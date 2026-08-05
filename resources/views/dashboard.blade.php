@extends('layouts.app')

@section('title', 'Dashboard')

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.create') }}"
           class="flex items-center gap-2 px-4 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
            <span class="material-symbols-outlined text-lg">add</span>
            New Project
        </a>
    @endif
@endsection

@section('content')

    {{-- Overview Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="Total Projects"   value="{{ $total }}"      icon="folder_open"     color="blue" sub="Registered E-IDS projects" />
        <x-stat-card label="In Inspection"    value="{{ $active }}"     icon="pending_actions" color="amber" sub="Active site inspections" />
        <x-stat-card label="Completed"        value="{{ $completed }}"  icon="task_alt"        color="emerald" sub="Fully scored & signed" />
        <x-stat-card label="Draft"            value="{{ $draft }}"      icon="draft"           color="gray" sub="Pending sample setup" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent projects table --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-xl">domain</span>
                    <h2 class="font-extrabold text-gray-900 text-sm">Recent Inspection Projects</h2>
                </div>
                <a href="{{ route('projects.index') }}" class="text-xs text-eids-accent hover:text-eids-primary font-bold flex items-center gap-1 transition">
                    View All Projects <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            @if($recent->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <div class="w-16 h-16 rounded-2xl bg-eids-primary/5 border border-eids-primary/10 flex items-center justify-center mb-3 text-eids-primary">
                        <span class="material-symbols-outlined text-4xl">domain</span>
                    </div>
                    <p class="text-base text-gray-900 font-bold">No inspection projects yet</p>
                    <p class="text-xs text-gray-500 mt-1 max-w-sm">Start by registering your first E-IDS residential project to begin site defect assessments.</p>
                    @if(auth()->user()->canInspect())
                        <a href="{{ route('projects.create') }}"
                           class="mt-5 min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition shadow-sm inline-flex items-center gap-2">
                            <span class="material-symbols-outlined text-base">add</span> Register First Project
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider border-b border-gray-200 font-bold">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Project Name</th>
                                <th class="px-4 py-3.5 text-left hidden md:table-cell">Ref No.</th>
                                <th class="px-4 py-3.5 text-left">Status</th>
                                <th class="px-4 py-3.5 text-left hidden lg:table-cell">Inspection Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recent as $project)
                                <tr class="hover:bg-gray-50/80 transition group">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('projects.show', $project) }}"
                                           class="font-bold text-gray-900 group-hover:text-eids-accent transition block text-sm">
                                            {{ $project->project_name }}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-0.5 md:hidden font-mono">{{ $project->project_no }}</div>
                                    </td>
                                    <td class="px-4 py-4 hidden md:table-cell text-gray-600 text-xs font-mono font-semibold">{{ $project->project_no }}</td>
                                    <td class="px-4 py-4">
                                        @php
                                            $map = [
                                                'draf'              => ['Draft',         'bg-gray-100 text-gray-700 border-gray-300'],
                                                'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-900 border-amber-300'],
                                                'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-900 border-emerald-300'],
                                            ];
                                            [$lbl, $cls] = $map[$project->status] ?? ['—', 'bg-gray-100 text-gray-500 border-gray-200'];
                                        @endphp
                                        <span class="inline-flex px-3 py-1 border rounded-full text-xs font-bold {{ $cls }}">{{ $lbl }}</span>
                                    </td>
                                    <td class="px-4 py-4 hidden lg:table-cell">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200/50">
                                                <div class="h-full bg-eids-accent rounded-full transition-all duration-300" style="width: {{ $project->inspection_progress }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-700 font-extrabold w-10 text-right">{{ $project->inspection_progress }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Right Panel: Performance & Defect Widgets --}}
        <div class="space-y-6">
            
            {{-- Average E-IDS Performance Hero Card --}}
            <div class="bg-eids-primary rounded-2xl p-6 text-white shadow-md border border-white/10 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none text-white">
                    <span class="material-symbols-outlined" style="font-size: 180px;">analytics</span>
                </div>
                
                <div class="relative z-10">
                    <div class="text-xs uppercase tracking-widest text-eids-light font-bold mb-3 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">monitoring</span>
                        Average E-IDS Performance
                    </div>
                    @if($avgScore > 0)
                        <div class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-2">
                            {{ number_format($avgScore, 1) }}<span class="text-2xl text-white/60">%</span>
                        </div>
                        @php
                            [$rtg, $rtgCls] = $avgScore >= $ratingBaik ? ['GOOD RATING', 'bg-emerald-500 text-white'] : ($avgScore >= $ratingMod ? ['MODERATE RATING', 'bg-amber-400 text-amber-950'] : ['WEAK RATING', 'bg-red-500 text-white']);
                            $archRatio = min(100, round(($avgScore / max(1, $ratingBaik)) * 100));
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-extrabold tracking-wider uppercase {{ $rtgCls }} shadow-xs">{{ $rtg }}</span>
                        
                        <div class="mt-6 space-y-4 pt-4 border-t border-white/10">
                            <div>
                                <div class="flex justify-between text-xs text-white/80 mb-1 font-semibold">
                                    <span>Architectural Score</span>
                                    <span>{{ number_format($avgScore, 1) }} / {{ number_format($ratingBaik, 0) }}% target</span>
                                </div>
                                <div class="h-2.5 bg-white/15 rounded-full overflow-hidden">
                                    <div class="h-full bg-eids-light rounded-full transition-all duration-500" style="width: {{ $archRatio }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs text-white/80 mb-1 font-semibold">
                                    <span>M&amp;E Fixed Component</span>
                                    <span>{{ number_format($meScore, 2) }} pts</span>
                                </div>
                                <div class="h-2.5 bg-white/15 rounded-full overflow-hidden">
                                    <div class="h-full bg-eids-light rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-white/60 text-sm py-4">No scored inspection data recorded yet.</div>
                    @endif
                </div>
            </div>

            {{-- Action Required Widget --}}
            @if($actionRequired->isNotEmpty())
                <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                    <div class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-4 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-amber-500">priority_high</span>
                        Action Required ({{ $actionRequired->count() }})
                    </div>
                    <div class="space-y-2">
                        @foreach($actionRequired as $project)
                            @php $action = $project->nextActionFor(auth()->user()); @endphp
                            <a href="{{ route('projects.show', $project) }}"
                               class="flex items-center gap-2 p-2.5 rounded-xl hover:bg-gray-50 transition text-xs">
                                <span class="material-symbols-outlined text-base text-eids-accent shrink-0">{{ $action['icon'] }}</span>
                                <span class="font-bold text-gray-900 shrink-0">{{ $project->project_name }}</span>
                                <span class="text-gray-500 truncate">&middot; {{ $action['text'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Global Defect Overview Widget --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6">
                <div class="text-xs uppercase tracking-wider text-gray-500 font-bold mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-red-500">warning</span>
                        Global Defect Overview
                    </span>
                    <span class="text-gray-400 font-mono text-[11px]">{{ $openDefects + $resolvedDefects }} Total</span>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 text-center">
                        <div class="text-3xl font-extrabold text-red-700">{{ $openDefects }}</div>
                        <div class="text-xs font-bold text-red-600 mt-0.5">Open Defects</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 text-center">
                        <div class="text-3xl font-extrabold text-emerald-700">{{ $resolvedDefects }}</div>
                        <div class="text-xs font-bold text-emerald-600 mt-0.5">Resolved</div>
                    </div>
                </div>
                <a href="{{ route('defects.index') }}"
                   class="flex items-center justify-center gap-2 text-xs text-eids-accent font-bold hover:text-eids-primary transition min-h-[44px] bg-emerald-50/60 rounded-xl border border-emerald-200 px-4">
                    View Defect Register <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>

@endsection
