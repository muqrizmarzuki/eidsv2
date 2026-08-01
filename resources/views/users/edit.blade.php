@extends('layouts.app')

@section('title', 'Edit User')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('users.index') }}" class="hover:text-gray-600">Users</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">{{ $user->name }}</span>
@endsection

@section('content')
<div class="max-w-lg mx-auto">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
            <h2 class="font-semibold text-gray-800 mb-5 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent">manage_accounts</span>
                Edit User
            </h2>
            <div class="space-y-4">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3.5 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Employee ID</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                               class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                        @error('employee_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Role *</label>
                        <select name="role" required
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                            <option value="inspector"    {{ old('role', $user->role) === 'inspector'    ? 'selected' : '' }}>Inspector</option>
                            <option value="lead_auditor" {{ old('role', $user->role) === 'lead_auditor' ? 'selected' : '' }}>Lead Auditor</option>
                            <option value="supervisor"   {{ old('role', $user->role) === 'supervisor'   ? 'selected' : '' }}>Supervisor</option>
                            <option value="admin"        {{ old('role', $user->role) === 'admin'        ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Change Password (optional)</div>
                    <div class="space-y-3" x-data="{ show: false }">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1.5">New Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="password" placeholder="Leave blank to keep current"
                                       class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent pr-10">
                                <button type="button" @click="show = !show"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-sm" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1.5">Confirm Password</label>
                            <input :type="show ? 'text' : 'password'" name="password_confirmation" placeholder="Repeat new password"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('users.index') }}"
               class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Cancel</a>
            <button type="submit"
                    class="px-6 py-2.5 bg-eids-primary text-white text-sm font-semibold rounded-lg hover:bg-eids-dark transition flex items-center gap-2">
                <span class="material-symbols-outlined text-base">save</span>
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
