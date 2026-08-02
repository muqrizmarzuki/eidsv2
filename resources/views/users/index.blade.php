@extends('layouts.app')

@section('title', 'User Management')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">User Management</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('users.create') }}"
       class="flex items-center gap-2 px-4 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
        <span class="material-symbols-outlined text-lg">person_add</span>
        New User Account
    </a>
@endsection

@section('content')

    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        @if($users->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-3 text-gray-400">
                    <span class="material-symbols-outlined text-4xl">group</span>
                </div>
                <p class="text-base text-gray-900 font-bold">No user accounts found</p>
                <p class="text-xs text-gray-500 mt-1">Create user accounts to grant access to inspectors, auditors, and supervisors.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-6 py-4 text-left">User</th>
                            <th class="px-4 py-4 text-left hidden sm:table-cell">Employee ID</th>
                            <th class="px-4 py-4 text-left">Role</th>
                            <th class="px-4 py-4 text-left hidden md:table-cell">Joined Date</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $user)
                            @php
                                $roleCls = [
                                    'admin'        => 'bg-purple-100 text-purple-900 border-purple-300',
                                    'lead_auditor' => 'bg-blue-100 text-blue-900 border-blue-300',
                                    'inspector'    => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                                    'supervisor'   => 'bg-amber-100 text-amber-900 border-amber-300',
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition {{ $user->id === auth()->id() ? 'bg-eids-primary/5' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-eids-primary text-white flex items-center justify-center font-extrabold text-xs shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 flex items-center gap-1.5 text-sm">
                                                {{ $user->name }}
                                                @if($user->id === auth()->id())
                                                    <span class="text-[10px] text-eids-accent font-extrabold bg-eids-accent/15 px-2 py-0.5 rounded-full">(You)</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 font-medium mt-0.5">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 hidden sm:table-cell">
                                    <span class="font-mono text-xs font-semibold text-gray-700">{{ $user->employee_id ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex px-3 py-1 border rounded-full text-xs font-extrabold {{ $roleCls[$user->role] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $user->getRoleLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 hidden md:table-cell text-xs text-gray-500 font-medium">
                                    {{ $user->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="p-2 text-gray-500 hover:text-amber-700 hover:bg-amber-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Edit User">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </a>
                                        @if($user->id !== auth()->id())
                                            <button
                                                @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('users.destroy', $user) }}', method: 'DELETE' })"
                                                class="p-2 text-gray-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition min-h-[36px] flex items-center justify-center" title="Delete User">
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
        @endif
    </div>

@endsection
