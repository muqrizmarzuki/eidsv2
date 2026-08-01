@extends('layouts.app')

@section('title', 'Users')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Users</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('users.create') }}"
       class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition">
        <span class="material-symbols-outlined text-base">person_add</span>
        New User
    </a>
@endsection

@section('content')

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        @if($users->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <span class="material-symbols-outlined text-gray-200 text-6xl mb-3">group</span>
                <p class="text-sm text-gray-400 font-medium">No users yet</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-medium">User</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden sm:table-cell">Employee ID</th>
                        <th class="px-4 py-3.5 text-left font-medium">Role</th>
                        <th class="px-4 py-3.5 text-left font-medium hidden md:table-cell">Joined</th>
                        <th class="px-4 py-3.5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($users as $user)
                        @php
                            $roleCls = [
                                'admin'        => 'bg-purple-100 text-purple-700',
                                'lead_auditor' => 'bg-blue-100 text-blue-700',
                                'inspector'    => 'bg-emerald-100 text-emerald-700',
                                'supervisor'   => 'bg-amber-100 text-amber-700',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition {{ $user->id === auth()->id() ? 'bg-eids-primary/5' : '' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-eids-primary flex items-center justify-center shrink-0">
                                        <span class="text-white text-xs font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                                <span class="text-xs text-eids-accent ml-1">(you)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell">
                                <span class="font-mono text-xs text-gray-500">{{ $user->employee_id ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $roleCls[$user->role] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $user->getRoleLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell text-xs text-gray-400">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit">
                                        <span class="material-symbols-outlined text-base">edit</span>
                                    </a>
                                    @if($user->id !== auth()->id())
                                        <button
                                            @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('users.destroy', $user) }}', method: 'DELETE' })"
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
        @endif
    </div>

@endsection
