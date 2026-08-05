@extends('layouts.app')

@section('title', 'New Defect')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('defects.index') }}" class="hover:text-gray-800 transition">Defects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">New Defect</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('defects.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-5 flex items-center gap-2 text-sm border-b border-gray-100 pb-3">
                <span class="material-symbols-outlined text-red-500 text-lg">warning</span>
                Defect Information &amp; Photo Documentation
            </h2>
            <div class="space-y-5">

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Target Project *</label>
                    <select name="project_id" required
                            class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                        <option value="">Select project...</option>
                        @foreach($projects as $proj)
                            <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                {{ $proj->project_no }} &middot; {{ $proj->project_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Component *</label>
                        <select name="component_name" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium {{ $errors->has('component_name') ? 'border-red-400 bg-red-50' : '' }}">
                            <option value="">Select component...</option>
                            @foreach(\App\Models\WeightageArchitecturalElement::ordered() as $code => $comp)
                                <option value="{{ $comp->name }}" {{ old('component_name') === $comp->name ? 'selected' : '' }}>
                                    {{ $code }} &middot; {{ $comp->name }}
                                </option>
                            @endforeach
                            <option value="Other" {{ old('component_name') === 'Other' ? 'selected' : '' }}>Other Site Observation</option>
                        </select>
                        @error('component_name')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Location / Room Name *</label>
                        <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Master Bedroom" required
                               class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium {{ $errors->has('location') ? 'border-red-400 bg-red-50' : '' }}">
                        @error('location')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Defect Description *</label>
                    <textarea name="defect_description" rows="3" required
                              placeholder="Describe the defect observation in detail..."
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent resize-none font-medium {{ $errors->has('defect_description') ? 'border-red-400 bg-red-50' : '' }}">{{ old('defect_description') }}</textarea>
                    @error('defect_description')<p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Severity Rating *</label>
                        <select name="severity" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                            <option value="low"    {{ old('severity') === 'low'    ? 'selected' : '' }}>Low Severity</option>
                            <option value="medium" {{ old('severity', 'medium') === 'medium' ? 'selected' : '' }}>Medium Severity</option>
                            <option value="high"   {{ old('severity') === 'high'   ? 'selected' : '' }}>High Severity</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Initial Defect Status *</label>
                        <select name="status" required
                                class="w-full min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                            <option value="OPEN"        {{ old('status', 'OPEN') === 'OPEN'        ? 'selected' : '' }}>Open</option>
                            <option value="IN_PROGRESS" {{ old('status') === 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                            <option value="PENDING_VERIFICATION" {{ old('status') === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Pending Verification</option>
                            <option value="RESOLVED"    {{ old('status') === 'RESOLVED'    ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>
                </div>

                <div x-data="{ src: null }">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Defect Photo Evidence (Optional)</label>
                    <div class="relative border-2 border-dashed border-gray-300 rounded-2xl hover:border-eids-accent transition bg-gray-50/50"
                         :class="src ? 'border-eids-accent bg-emerald-50/30' : ''">
                        <input type="file" name="photo" accept="image/*"
                               @change="src = URL.createObjectURL($event.target.files[0])"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <div x-show="!src" class="flex flex-col items-center justify-center py-8 text-center px-4">
                            <span class="material-symbols-outlined text-gray-400 text-3xl mb-1">add_a_photo</span>
                            <div class="text-xs font-bold text-gray-800">Tap to upload defect photo</div>
                            <div class="text-[11px] text-gray-500 mt-1">Supports JPG, PNG up to 5MB</div>
                        </div>
                        <div x-show="src" x-cloak class="p-2">
                            <img :src="src" class="w-full rounded-xl object-cover max-h-48">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <a href="{{ route('defects.index') }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center justify-center">
                Cancel
            </a>
            <button type="submit"
                    class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
                <span class="material-symbols-outlined text-base">save</span>
                Record Defect Log
            </button>
        </div>
    </form>
</div>
@endsection
