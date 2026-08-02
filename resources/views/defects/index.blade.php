@extends('layouts.app')

@section('title', auth()->user()->role === 'contractor' ? 'My Defects' : 'Defects Register')

@section('breadcrumb')
    @unless(auth()->user()->role === 'contractor')
        <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
        <span class="material-symbols-outlined text-sm">chevron_right</span>
    @endunless
    <span class="text-gray-900 font-bold">{{ auth()->user()->role === 'contractor' ? 'My Defects' : 'Defects Register' }}</span>
@endsection

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('defects.create') }}"
           class="flex items-center gap-2 px-4 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
            <span class="material-symbols-outlined text-lg">add</span>
            New Defect Log
        </a>
    @endif
@endsection

@section('content')

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-6 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
        <div class="relative flex-1 min-w-56">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">search</span>
            <input name="search" value="{{ request('search') }}" placeholder="Search component, location or defect notes..."
                   class="w-full pl-10 pr-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-gray-50/50 focus:bg-white transition">
        </div>
        <select name="project_id" class="px-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium max-w-xs">
            <option value="">All Projects</option>
            @foreach($projects as $proj)
                <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                    {{ $proj->project_no }} — {{ $proj->project_name }}
                </option>
            @endforeach
        </select>
        <select name="severity" class="px-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
            <option value="">All Severities</option>
            <option value="low"    {{ request('severity') === 'low'    ? 'selected' : '' }}>Low</option>
            <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="high"   {{ request('severity') === 'high'   ? 'selected' : '' }}>High</option>
        </select>
        <select name="status" class="px-4 py-2.5 min-h-[44px] text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
            <option value="">All Statuses</option>
            <option value="OPEN"        {{ request('status') === 'OPEN'        ? 'selected' : '' }}>Open</option>
            <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
            <option value="PENDING_VERIFICATION" {{ request('status') === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Pending Verification</option>
            <option value="RESOLVED"    {{ request('status') === 'RESOLVED'    ? 'selected' : '' }}>Resolved</option>
        </select>
        <button type="submit" class="px-5 py-2.5 min-h-[44px] bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">filter_list</span> Filter
        </button>
        @if(request()->hasAny(['search','project_id','severity','status']))
            <a href="{{ route('defects.index') }}" class="px-4 py-2.5 min-h-[44px] text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-1">
                <span class="material-symbols-outlined text-base">close</span> Clear
            </a>
        @endif
    </form>

    {{-- Defects Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        @if($defects->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center mb-3 text-amber-700">
                    <span class="material-symbols-outlined text-4xl">warning</span>
                </div>
                <p class="text-base text-gray-900 font-bold">No defects logged</p>
                <p class="text-xs text-gray-500 mt-1 max-w-sm">Defects are auto-created when a component check is marked FAIL during site inspection or added manually.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-6 py-4 text-left">Defect / Location</th>
                            <th class="px-4 py-4 text-left hidden md:table-cell">Project</th>
                            <th class="px-4 py-4 text-left hidden sm:table-cell">Severity</th>
                            <th class="px-4 py-4 text-left">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($defects as $defect)
                            @php
                                $sevCls = [
                                    'low'    => 'bg-gray-100 text-gray-700 border-gray-300',
                                    'medium' => 'bg-amber-100 text-amber-900 border-amber-300',
                                    'high'   => 'bg-red-100 text-red-900 border-red-300',
                                ];
                                $stCls  = [
                                    'OPEN'                 => 'bg-red-100 text-red-900 border-red-300',
                                    'IN_PROGRESS'          => 'bg-amber-100 text-amber-900 border-amber-300',
                                    'PENDING_VERIFICATION' => 'bg-blue-100 text-blue-900 border-blue-300',
                                    'RESOLVED'             => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                ];
                                $stLabel = [
                                    'OPEN' => 'Open', 'IN_PROGRESS' => 'In Progress',
                                    'PENDING_VERIFICATION' => 'Pending Verification', 'RESOLVED' => 'Resolved',
                                ];
                                $role = auth()->user()->role;
                                $canAdvance = in_array($role, ['admin', 'lead_auditor', 'inspector', 'contractor']);
                                $canConfirmOrReject = in_array($role, ['admin', 'lead_auditor', 'inspector']);
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition group">
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 text-sm">{{ $defect->component_name }}</div>
                                    <div class="text-xs text-gray-600 mt-0.5 flex items-center gap-1 font-medium">
                                        <span class="material-symbols-outlined text-xs text-eids-accent">location_on</span>
                                        {{ $defect->location }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 line-clamp-1 max-w-xs font-medium">{{ $defect->defect_description }}</div>
                                </td>
                                <td class="px-4 py-4 hidden md:table-cell">
                                    @if($defect->project)
                                        <a href="{{ route('projects.show', $defect->project) }}"
                                           class="text-xs font-bold text-eids-accent hover:text-eids-primary transition">
                                            {{ $defect->project->project_no }}
                                        </a>
                                        <div class="text-xs text-gray-600 mt-0.5 truncate max-w-36 font-semibold">{{ $defect->project->project_name }}</div>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 hidden sm:table-cell">
                                    <span class="inline-flex px-3 py-1 border rounded-full text-xs font-extrabold {{ $sevCls[$defect->severity] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $defect->severity_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex px-3 py-1 border rounded-full text-xs font-extrabold {{ $stCls[$defect->status] ?? '' }}">
                                        {{ $stLabel[$defect->status] ?? $defect->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        @if($defect->status === 'OPEN' && $canAdvance)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="IN_PROGRESS">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition min-h-[36px]">
                                                    Start Repair
                                                </button>
                                            </form>
                                        @elseif($defect->status === 'IN_PROGRESS' && $canAdvance)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="PENDING_VERIFICATION">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-blue-800 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition min-h-[36px]">
                                                    Mark Settled
                                                </button>
                                            </form>
                                        @elseif($defect->status === 'PENDING_VERIFICATION' && $canConfirmOrReject)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="RESOLVED">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition min-h-[36px]">
                                                    Confirm Resolved
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="IN_PROGRESS">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-red-800 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition min-h-[36px]">
                                                    Reject – Not Fixed
                                                </button>
                                            </form>
                                        @elseif($defect->status === 'PENDING_VERIFICATION')
                                            <span class="px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg">
                                                Awaiting Verification
                                            </span>
                                        @elseif($defect->status === 'RESOLVED' && $canConfirmOrReject)
                                            <form method="POST" action="{{ route('defects.advance', $defect) }}">
                                                @csrf
                                                <input type="hidden" name="to" value="OPEN">
                                                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition min-h-[36px]">
                                                    Reopen
                                                </button>
                                            </form>
                                        @endif
                                        @if(auth()->user()->canInspect())
                                            <a href="{{ route('defects.edit', $defect) }}"
                                               class="p-2 text-gray-500 hover:text-amber-700 hover:bg-amber-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Edit Defect">
                                                <span class="material-symbols-outlined text-lg">edit</span>
                                            </a>
                                            <button
                                                @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('defects.destroy', $defect) }}', method: 'DELETE' })"
                                                class="p-2 text-gray-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Delete Defect">
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
            @if($defects->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">{{ $defects->links() }}</div>
            @endif
        @endif
    </div>

@endsection
