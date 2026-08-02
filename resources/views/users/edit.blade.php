@extends('layouts.app')

@section('title', 'Edit User')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('users.index') }}" class="hover:text-gray-800 transition">Users</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">{{ $user->name }}</span>
@endsection

@section('content')
<div class="max-w-lg mx-auto">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-eids-accent text-lg">manage_accounts</span>
                Edit User Account
            </h2>
            <div class="space-y-5">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('email')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Employee ID</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        @error('employee_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Role Assignment *</label>
                        <select name="role" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                            <option value="inspector"    {{ old('role', $user->role) === 'inspector'    ? 'selected' : '' }}>Inspector</option>
                            <option value="lead_auditor" {{ old('role', $user->role) === 'lead_auditor' ? 'selected' : '' }}>Lead Auditor</option>
                            <option value="supervisor"   {{ old('role', $user->role) === 'supervisor'   ? 'selected' : '' }}>Supervisor</option>
                            <option value="contractor"   {{ old('role', $user->role) === 'contractor'   ? 'selected' : '' }}>Contractor</option>
                            <option value="admin"        {{ old('role', $user->role) === 'admin'        ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <div class="text-xs font-extrabold text-gray-800 uppercase tracking-wider mb-3">Change Password (optional)</div>
                    <div class="space-y-4" x-data="{ show: false }">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">New Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" placeholder="Leave blank to keep current password"
                                       class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent pr-12 font-medium">
                                <button type="button" @click="show = !show"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-lg" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            @error('password')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Confirm New Password</label>
                            <input :type="show ? 'text' : 'password'" name="password_confirmation" placeholder="Repeat new password"
                                   class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
            @if($user->id !== auth()->id())
                <button type="button"
                        @click="$dispatch('open-confirm', { id: 'delete-confirm', action: '{{ route('users.destroy', $user) }}', method: 'DELETE' })"
                        class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-red-700 border border-red-200 rounded-xl hover:bg-red-50 transition flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">delete</span>
                    Delete User Account
                </button>
            @else
                <div></div>
            @endif
            <div class="flex items-center gap-3">
                <a href="{{ route('users.index') }}"
                   class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">Cancel</a>
                <button type="submit"
                        class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                    <span class="material-symbols-outlined text-base">save</span>
                    Save User Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
