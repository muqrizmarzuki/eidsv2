@extends('layouts.app')

@section('title', 'New Defect')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('defects.index') }}" class="hover:text-gray-600">Defects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">New Defect</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('defects.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
            <h2 class="font-semibold text-gray-800 mb-5 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent">warning</span>
                Defect Information
            </h2>
            <div class="space-y-4">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Project *</label>
                    <select name="project_id" required
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                        <option value="">Select project...</option>
                        @foreach($projects as $proj)
                            <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                {{ $proj->project_no }} — {{ $proj->project_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Component *</label>
                        <select name="component_name" required
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white {{ $errors->has('component_name') ? 'border-red-400' : '' }}">
                            <option value="">Select component...</option>
                            @foreach(config('eids.components') as $code => $comp)
                                <option value="{{ $comp['name'] }}" {{ old('component_name') === $comp['name'] ? 'selected' : '' }}>
                                    {{ $code }} — {{ $comp['name'] }}
                                </option>
                            @endforeach
                            <option value="Other" {{ old('component_name') === 'Other' ? 'selected' : '' }}>Other (site observation)</option>
                        </select>
                        @error('component_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
                        <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Living Room" required
                               class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $errors->has('location') ? 'border-red-400' : '' }}">
                        @error('location')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Description *</label>
                    <textarea name="defect_description" rows="3" required
                              placeholder="Describe the defect in detail..."
                              class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent resize-none {{ $errors->has('defect_description') ? 'border-red-400' : '' }}">{{ old('defect_description') }}</textarea>
                    @error('defect_description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Severity *</label>
                        <select name="severity" required
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                            <option value="low"    {{ old('severity') === 'low'    ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('severity', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high"   {{ old('severity') === 'high'   ? 'selected' : '' }}>High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status *</label>
                        <select name="status" required
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                            <option value="OPEN"        {{ old('status', 'OPEN') === 'OPEN'        ? 'selected' : '' }}>Open</option>
                            <option value="IN_PROGRESS" {{ old('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                            <option value="RESOLVED"    {{ old('status') === 'RESOLVED'    ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>
                </div>

                <div x-data="{ src: null }">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Photo (optional)</label>
                    <div class="relative border-2 border-dashed border-gray-200 rounded-xl hover:border-eids-accent transition"
                         :class="src ? 'border-eids-accent/50' : ''">
                        <input type="file" name="photo" accept="image/*"
                               @change="src = URL.createObjectURL($event.target.files[0])"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <div x-show="!src" class="flex flex-col items-center justify-center py-8 text-center">
                            <span class="material-symbols-outlined text-gray-300 text-3xl mb-1">add_a_photo</span>
                            <div class="text-xs text-gray-400">Click to upload photo evidence</div>
                        </div>
                        <div x-show="src" x-cloak class="p-2">
                            <img :src="src" class="w-full rounded-lg object-cover max-h-40">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('defects.index') }}"
               class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-eids-primary text-white text-sm font-semibold rounded-lg hover:bg-eids-dark transition flex items-center gap-2">
                <span class="material-symbols-outlined text-base">save</span>
                Record Defect
            </button>
        </div>
    </form>
</div>
@endsection
