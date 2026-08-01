@extends('layouts.app')

@section('title', 'Defects')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Defects</span>
@endsection

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('defects.create') }}"
           class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition">
            <span class="material-symbols-outlined text-base">add</span>
            New Defect
        </a>
    @endif
@endsection

@section('content')

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <div class="relative flex-1 min-w-48">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">search</span>
            <input name="search" value="{{ request('search') }}" placeholder="Search component, location or description..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
        </div>
        <select name="project_id" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
            <option value="">All Projects</option>
            @foreach($projects as $proj)
                <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                    {{ $proj->project_no }} — {{ $proj->project_name }}
                </option>
            @endforeach
        </select>
        <select name="severity" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
            <option value="">All Severity</option>
            <option value="low"    {{ request('severity') === 'low'    ? 'selected' : '' }}>Low</option>
            <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="high"   {{ request('severity') === 'high'   ? 'selected' : '' }}>High</option>
        </select>
        <select name="status" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
            <option value="">All Status</option>
            <option value="OPEN"        {{ request('status') === 'OPEN'        ? 'selected' : '' }}>Open</option>
            <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
            <option value="RESOLVED"    {{ request('status') === 'RESOLVED'    ? 'selected' : '' }}>Resolved</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-eids-primary text-white text-sm rounded-lg hover:bg-eids-dark transition">Filter</button>
        @if(request()->hasAny(['search','project_id','severity','status']))
            <a href="{{ route('defects.index') }}" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        @if($defects->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <span class="material-symbols-outlined text-gray-200 text-6xl mb-3">warning</span>
                <p class="text-sm text-gray-400 font-medium">No defects found</p>
                <p class="text-xs text-gray-300 mt-1">Defects are auto-generated when a component is marked FAIL during inspection.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-medium">Defect</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden md:table-cell">Project</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden sm:table-cell">Severity</th>
                        <th class="px-4 py-3.5 text-left font-medium">Status</th>
                        <th class="px-4 py-3.5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($defects as $defect)
                        @php
                            $sevCls = [
                                'low'    => 'bg-gray-100 text-gray-500',
                                'medium' => 'bg-amber-100 text-amber-700',
                                'high'   => 'bg-red-100 text-red-600',
                            ];
                            $stCls  = [
                                'OPEN'        => 'bg-red-100 text-red-700',
                                'IN_PROGRESS' => 'bg-amber-100 text-amber-700',
                                'RESOLVED'    => 'bg-emerald-100 text-emerald-700',
                            ];
                            $stLabel = [
                                'OPEN' => 'Open', 'IN_PROGRESS' => 'In Progress', 'RESOLVED' => 'Resolved'
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-800 text-sm">{{ $defect->component_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">location_on</span>
                                    {{ $defect->location }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1 line-clamp-1 max-w-xs">{{ $defect->defect_description }}</div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                @if($defect->project)
                                    <a href="{{ route('projects.show', $defect->project) }}"
                                       class="text-xs text-eids-accent hover:underline font-medium">
                                        {{ $defect->project->project_no }}
                                    </a>
                                    <div class="text-xs text-gray-400 mt-0.5 truncate max-w-36">{{ $defect->project->project_name }}</div>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $sevCls[$defect->severity] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $defect->severity_label }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $stCls[$defect->status] ?? '' }}">
                                    {{ $stLabel[$defect->status] ?? $defect->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->canInspect())
                                        {{-- Toggle status --}}
                                        <form method="POST" action="{{ route('defects.toggle', $defect) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                                    title="Advance Status">
                                                <span class="material-symbols-outlined text-base">update</span>
                                            </button>
                                        </form>
                                        <a href="{{ route('defects.edit', $defect) }}"
                                           class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </a>
                                        <button
                                            @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('defects.destroy', $defect) }}', method: 'DELETE' })"
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
            @if($defects->hasPages())
                <div class="px-5 py-4 border-t border-gray-50">{{ $defects->links() }}</div>
            @endif
        @endif
    </div>

@endsection
