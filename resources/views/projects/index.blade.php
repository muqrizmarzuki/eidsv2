@extends('layouts.app')

@section('title', 'Projects')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Projects</span>
@endsection

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.create') }}"
           class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition">
            <span class="material-symbols-outlined text-base">add</span>
            New Project
        </a>
    @endif
@endsection

@section('content')

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <div class="relative flex-1 min-w-48">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">search</span>
            <input name="search" value="{{ request('search') }}" placeholder="Search project name, number or developer..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
        </div>
        <select name="status" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
            <option value="">All Status</option>
            <option value="draf"              {{ request('status') === 'draf'              ? 'selected' : '' }}>Draft</option>
            <option value="dalam_pemeriksaan" {{ request('status') === 'dalam_pemeriksaan' ? 'selected' : '' }}>In Inspection</option>
            <option value="selesai"           {{ request('status') === 'selesai'           ? 'selected' : '' }}>Completed</option>
        </select>
        <select name="type" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
            <option value="">All Types</option>
            <option value="teres"  {{ request('type') === 'teres'  ? 'selected' : '' }}>Terrace</option>
            <option value="semi_d" {{ request('type') === 'semi_d' ? 'selected' : '' }}>Semi-D</option>
            <option value="banglo" {{ request('type') === 'banglo' ? 'selected' : '' }}>Bungalow</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-eids-primary text-white text-sm rounded-lg hover:bg-eids-dark transition">Filter</button>
        @if(request()->hasAny(['search','status','type']))
            <a href="{{ route('projects.index') }}" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        @if($projects->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <span class="material-symbols-outlined text-gray-200 text-6xl mb-3">folder_open</span>
                <p class="text-sm text-gray-400 font-medium">No projects found</p>
                @if(auth()->user()->canInspect())
                    <a href="{{ route('projects.create') }}"
                       class="mt-4 px-4 py-2 bg-eids-primary text-white text-xs font-medium rounded-lg hover:bg-eids-dark transition">
                        + Create First Project
                    </a>
                @endif
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-medium">Project</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden sm:table-cell">Ref No.</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden md:table-cell">Type</th>
                        <th class="px-4 py-3.5 text-left font-medium">Status</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden lg:table-cell">Progress</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden lg:table-cell">Score</th>
                        <th class="px-4 py-3.5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($projects as $project)
                        @php
                            $statusMap = [
                                'draf'              => ['Draft',         'bg-gray-100 text-gray-500'],
                                'dalam_pemeriksaan' => ['In Inspection', 'bg-amber-100 text-amber-700'],
                                'selesai'           => ['Completed',     'bg-emerald-100 text-emerald-700'],
                            ];
                            [$sLabel, $sCls] = $statusMap[$project->status] ?? ['—', 'bg-gray-100'];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-4">
                                <a href="{{ route('projects.show', $project) }}"
                                   class="font-semibold text-gray-800 hover:text-eids-primary transition block">
                                    {{ $project->project_name }}
                                </a>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $project->developer_name }}</div>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell">
                                <span class="font-mono text-xs text-gray-500">{{ $project->project_no }}</span>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell text-gray-600 text-xs capitalize">
                                {{ $project->building_type_label }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $sCls }}">{{ $sLabel }}</span>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell">
                                <div class="flex items-center gap-2 min-w-24">
                                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-eids-accent rounded-full" style="width: {{ $project->inspection_progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 w-8 text-right shrink-0">{{ $project->inspection_progress }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell">
                                @if($project->overall_score > 0)
                                    @php
                                        $sc = $project->overall_score;
                                        $scCls = $sc >= 85 ? 'text-emerald-600' : ($sc >= 70 ? 'text-amber-600' : 'text-red-600');
                                    @endphp
                                    <span class="font-bold text-sm {{ $scCls }}">{{ number_format($sc, 1) }}%</span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('projects.show', $project) }}"
                                       class="p-1.5 text-gray-400 hover:text-eids-primary hover:bg-gray-100 rounded-lg transition" title="View">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                    </a>
                                    @if(auth()->user()->canInspect())
                                        <a href="{{ route('projects.edit', $project) }}"
                                           class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </a>
                                        <button
                                            @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('projects.destroy', $project) }}', method: 'DELETE' })"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($projects->hasPages())
                <div class="px-5 py-4 border-t border-gray-50">{{ $projects->links() }}</div>
            @endif
        @endif
    </div>

@endsection
