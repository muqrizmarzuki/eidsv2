@extends('layouts.app')

@section('title', 'Projects')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Projects</span>
@endsection

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

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-6 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
        <div class="relative flex-1 min-w-56">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">search</span>
            <input name="search" value="{{ request('search') }}" placeholder="Search project name, number or developer..."
                   class="w-full pl-10 pr-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-gray-50/50 focus:bg-white transition">
        </div>
        <select name="status" class="px-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
            <option value="">All Statuses</option>
            <option value="draf"              {{ request('status') === 'draf'              ? 'selected' : '' }}>Draft</option>
            <option value="dalam_pemeriksaan" {{ request('status') === 'dalam_pemeriksaan' ? 'selected' : '' }}>In Inspection</option>
            <option value="selesai"           {{ request('status') === 'selesai'           ? 'selected' : '' }}>Completed</option>
        </select>
        <select name="type" class="px-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
            <option value="">All Building Types</option>
            <option value="teres"  {{ request('type') === 'teres'  ? 'selected' : '' }}>Terrace</option>
            <option value="semi_d" {{ request('type') === 'semi_d' ? 'selected' : '' }}>Semi-D</option>
            <option value="banglo" {{ request('type') === 'banglo' ? 'selected' : '' }}>Bungalow</option>
        </select>
        <select name="assigned_to" class="px-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
            <option value="">All Inspectors</option>
            @foreach($inspectors as $inspector)
                <option value="{{ $inspector->id }}" {{ request('assigned_to') == $inspector->id ? 'selected' : '' }}>
                    {{ $inspector->name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="px-5 py-2.5 min-h-[44px] bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">filter_list</span> Filter
        </button>
        @if(request()->hasAny(['search','status','type','assigned_to']))
            <a href="{{ route('projects.index') }}" class="px-4 py-2.5 min-h-[44px] text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-1">
                <span class="material-symbols-outlined text-base">close</span> Clear
            </a>
        @endif
    </form>

    {{-- Projects Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        @if($projects->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-3 text-gray-400">
                    <span class="material-symbols-outlined text-4xl">folder_open</span>
                </div>
                <p class="text-base text-gray-800 font-bold">No projects found</p>
                <p class="text-xs text-gray-500 mt-1 max-w-sm">No inspection projects match your search criteria. Try adjusting filters or create a new project.</p>
                @if(auth()->user()->canInspect())
                    <a href="{{ route('projects.create') }}"
                       class="mt-5 min-h-[44px] px-5 py-2.5 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition shadow-xs inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">add</span> Register Project
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-6 py-4 text-left">Project</th>
                            <th class="px-4 py-4 text-left hidden sm:table-cell">Ref No.</th>
                            <th class="px-4 py-4 text-left hidden md:table-cell">Type</th>
                            <th class="px-4 py-4 text-left">Status</th>
                            <th class="px-4 py-4 text-left hidden lg:table-cell">Progress</th>
                            <th class="px-4 py-4 text-left hidden lg:table-cell">Score</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($projects as $project)
                            @php
                                $statusMap = [
                                    'draf'              => ['Draft',         'bg-gray-100 text-gray-700 border-gray-300'],
                                    'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-900 border-amber-300'],
                                    'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-900 border-emerald-300'],
                                ];
                                [$sLabel, $sCls] = $statusMap[$project->status] ?? ['—', 'bg-gray-100 text-gray-500 border-gray-200'];
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition group">
                                <td class="px-6 py-4">
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="font-bold text-gray-900 group-hover:text-eids-accent transition block text-sm">
                                        {{ $project->project_name }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-0.5 font-medium">{{ $project->developer_name }}</div>
                                </td>
                                <td class="px-4 py-4 hidden sm:table-cell">
                                    <span class="font-mono text-xs font-semibold text-gray-700">{{ $project->project_no }}</span>
                                </td>
                                <td class="px-4 py-4 hidden md:table-cell text-gray-700 text-xs font-semibold capitalize">
                                    {{ $project->building_type_label }}
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex px-3 py-1 border rounded-full text-xs font-bold {{ $sCls }}">{{ $sLabel }}</span>
                                </td>
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    <div class="flex items-center gap-3 min-w-28">
                                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden border border-gray-200/50">
                                            <div class="h-full bg-eids-accent rounded-full transition-all" style="width: {{ $project->inspection_progress }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-700 font-extrabold w-8 text-right shrink-0">{{ $project->inspection_progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    @if($project->overall_score > 0)
                                        @php
                                            $sc = $project->overall_score;
                                            $scCls = $sc >= 85 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($sc >= 70 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-red-700 bg-red-50 border-red-200');
                                        @endphp
                                        <span class="inline-flex px-2.5 py-1 border rounded-lg text-xs font-extrabold {{ $scCls }}">{{ number_format($sc, 1) }}%</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('projects.show', $project) }}"
                                           class="p-2 text-gray-500 hover:text-eids-primary hover:bg-gray-100 rounded-xl transition min-h-[36px] flex items-center justify-center" title="View Project Details">
                                            <span class="material-symbols-outlined text-lg">visibility</span>
                                        </a>
                                        @if(auth()->user()->canInspect())
                                            <a href="{{ route('projects.edit', $project) }}"
                                               class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Edit Project">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <button
                                                @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('projects.destroy', $project) }}', method: 'DELETE' })"
                                                class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Delete Project">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($projects->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">{{ $projects->links() }}</div>
            @endif
        @endif
    </div>

@endsection
