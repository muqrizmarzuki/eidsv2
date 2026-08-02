@extends('layouts.app')

@section('title', 'New User')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('users.index') }}" class="hover:text-gray-800 transition">Users</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">New User Account</span>
@endsection

@section('content')
<div class="max-w-lg mx-auto">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-eids-accent text-lg">person_add</span>
                User Profile &amp; Role Credentials
            </h2>
            <div class="space-y-5">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Ahmad Razali"
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="user@domain.com"
                           class="w-full min-h-[44px] px-4 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}">
                    @error('email')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Employee ID</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. EMP-042"
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        @error('employee_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Role Assignment *</label>
                        <select name="role" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                            <option value="inspector"    {{ old('role','inspector') === 'inspector'    ? 'selected' : '' }}>Inspector</option>
                            <option value="lead_auditor" {{ old('role') === 'lead_auditor' ? 'selected' : '' }}>Lead Auditor</option>
                            <option value="supervisor"   {{ old('role') === 'supervisor'   ? 'selected' : '' }}>Supervisor</option>
                            <option value="contractor"   {{ old('role') === 'contractor'   ? 'selected' : '' }}>Contractor</option>
                            <option value="admin"        {{ old('role') === 'admin'        ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div x-data="{ show: false }">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password *</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required placeholder="Min. 8 characters"
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent pr-12 font-medium {{ $errors->has('password') ? 'border-red-400 bg-red-50' : '' }}">
                        <button type="button" @click="show = !show"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined text-lg" x-text="show ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                    @error('password')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required placeholder="Repeat password"
                           class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <a href="{{ route('users.index') }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">Cancel</a>
            <button type="submit"
                    class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                <span class="material-symbols-outlined text-base">save</span>
                Create User Account
            </button>
        </div>
    </form>
</div>
@endsection
