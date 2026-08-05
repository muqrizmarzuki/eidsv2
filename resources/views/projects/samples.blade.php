@extends('layouts.app')

@section('title', 'Configure Samples')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Configure Samples</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto flex flex-col w-full min-h-[calc(100dvh-6rem)] sm:min-h-[calc(100dvh-7rem)] lg:min-h-[calc(100dvh-8rem)]" x-data="{ open: false }">

    {{-- Setup Wizard Step Indicator --}}
    <x-setup-progress step="2" />

    {{-- Info Banner --}}
    <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-2xl p-5 mb-6 flex items-start gap-3.5 shadow-2xs">
        <span class="material-symbols-outlined text-eids-accent text-2xl shrink-0 mt-0.5">info</span>
        <div>
            <div class="font-extrabold text-gray-900 text-sm">Sample Units Configuration</div>
            <div class="text-xs text-gray-700 mt-1 leading-relaxed font-medium">
                Formula (Table 3): N = clamp(ceil(Total GFA &divide; {{ $divisor }}), {{ $minSamples }}, {{ $maxSamples }}) = <strong class="text-eids-primary font-bold">{{ $project->calculated_samples }} sample units</strong>, distributed across {{ $project->total_units }} unit(s).
                Assign a room or location name to each sample unit, then save to finalize project setup.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('projects.samples.store', $project) }}" class="flex-1 flex flex-col" id="save-project-form">
        @csrf

        {{-- Optional Element Presence (Table 2's Car Park / Apron & Perimeter Drain) --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-1 flex items-center gap-2 text-sm">
                <span class="material-symbols-outlined text-eids-accent text-lg">tune</span>
                Optional Elements Present in This Project
            </h2>
            <p class="text-xs text-gray-500 font-medium mb-4">If an element doesn't exist on this project, its Table 2 weightage is automatically redistributed across the rest.</p>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="car_park_present" value="1" {{ old('car_park_present', $project->car_park_present) ? 'checked' : '' }}>
                    Car Park / Car Porch
                </label>
                <label class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="apron_drain_present" value="1" {{ old('apron_drain_present', $project->apron_drain_present) ? 'checked' : '' }}>
                    Apron and Perimeter Drain
                </label>
            </div>
        </div>

        {{-- Sample units card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">home_work</span>
                    Sample Units Location Assignment ({{ $project->samples->count() }})
                </h2>
                <span class="text-xs text-gray-500 font-mono font-semibold">Total GFA: {{ number_format($project->floor_area_sqm, 2) }} m²</span>
            </div>

            @if($project->samples->isEmpty())
                <div class="py-16 text-center text-sm text-gray-500 font-medium">
                    No samples have been generated. Please recreate this project.
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($project->samples as $index => $sample)
                        @php
                            $current    = old("locations.{$sample->id}", $sample->location_name);
                            $inDefaults = in_array($current, $defaultLocations);
                            $initMode   = ($inDefaults || empty($current)) ? 'select' : 'custom';
                            $initSelect = $inDefaults ? $current : ($defaultLocations[0] ?? '');
                            $initCustom = $inDefaults ? '' : $current;
                        @endphp
                        <div class="flex items-center gap-4 px-6 py-4.5"
                             x-data="{
                                 mode: '{{ $initMode }}',
                                 sel: '{{ addslashes($initSelect) }}',
                                 txt: '{{ addslashes($initCustom) }}',
                                 get val() { return this.mode === 'select' ? this.sel : this.txt; },
                                 switchCustom() { this.mode = 'custom'; this.txt = ''; this.$nextTick(() => this.$refs.txt.focus()); },
                                 switchSelect() { this.mode = 'select'; this.sel = '{{ addslashes($initSelect ?: ($defaultLocations[0] ?? '')) }}'; }
                             }">

                            {{-- Index badge --}}
                            <div class="w-10 h-10 rounded-full bg-eids-primary/10 text-eids-primary border border-eids-primary/20 flex items-center justify-center text-xs font-extrabold shrink-0 shadow-2xs">
                                #{{ $sample->sample_index }}
                            </div>

                            {{-- Unit reference — which physical unit this sample belongs to --}}
                            @if($sample->unit_reference)
                                <span class="shrink-0 text-xs font-bold text-gray-600 bg-gray-100 border border-gray-200 px-2.5 py-1.5 rounded-lg">
                                    {{ $sample->unit_reference }}
                                </span>
                            @endif

                            {{-- Location picker --}}
                            <div class="flex-1 flex items-center gap-2">
                                {{-- Select mode --}}
                                <template x-if="mode === 'select'">
                                    <div class="flex flex-1 gap-2">
                                        <select x-model="sel"
                                                @change="if(sel === '__custom__') switchCustom()"
                                                class="flex-1 min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white font-medium">
                                            @foreach($defaultLocations as $loc)
                                                <option value="{{ $loc }}">{{ $loc }}</option>
                                            @endforeach
                                            <option value="__custom__">+ Custom Location Name...</option>
                                        </select>
                                    </div>
                                </template>

                                {{-- Custom text mode --}}
                                <template x-if="mode === 'custom'">
                                    <div class="flex flex-1 gap-2">
                                        <input x-ref="txt" type="text" x-model="txt"
                                               placeholder="Enter custom location name..."
                                               class="flex-1 min-h-[44px] px-4 py-2.5 border border-eids-accent rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                                        @if(!empty($defaultLocations))
                                            <button type="button" @click="switchSelect()"
                                                    class="min-h-[44px] px-4 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition whitespace-nowrap">
                                                Use Default List
                                            </button>
                                        @endif
                                    </div>
                                </template>

                                {{-- Hidden input submitting the location value --}}
                                <input type="hidden" name="locations[{{ $sample->id }}]" :value="val">
                            </div>

                            {{-- Table 4 location type (Principal/Service/Circulation) --}}
                            <select name="location_types[{{ $sample->id }}]"
                                    class="min-h-[44px] px-3 py-2.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-eids-accent shrink-0">
                                @foreach(['principal' => 'Principal', 'service' => 'Service', 'circulation' => 'Circulation'] as $val => $label)
                                    <option value="{{ $val }}" {{ old("location_types.{$sample->id}", $sample->location_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>

                            {{-- Status badge --}}
                            @if(!is_null($sample->pass_rate))
                                <span class="text-xs font-extrabold text-emerald-800 bg-emerald-100 border border-emerald-300 px-3 py-1.5 rounded-full shrink-0">
                                    Inspected
                                </span>
                            @else
                                <span class="text-xs font-bold text-gray-500 bg-gray-100 border border-gray-200 px-3 py-1.5 rounded-full shrink-0">
                                    Pending
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Component Preview Card --}}
        <div class="mb-6 bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">checklist</span>
                    Architectural Components Framework ({{ count($components) }} Components)
                </h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($components as $code => $comp)
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xs font-extrabold text-eids-primary bg-eids-primary/10 px-2.5 py-1 rounded-md">{{ $code }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ $comp->name }}</span>
                            @if($comp->optional)
                                <span class="text-[10px] text-amber-700 font-bold uppercase">optional</span>
                            @endif
                        </div>
                        <span class="text-xs font-extrabold text-eids-primary bg-eids-accent/15 border border-eids-accent/30 px-3 py-1 rounded-full">
                            {{ $comp->breakdown_pct }}% Weightage
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Primary Action buttons --}}
        <div class="flex justify-between items-center gap-4 bg-white rounded-2xl border border-gray-200 shadow-md p-4 mt-auto">
            <a href="{{ route('projects.show', $project) }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
                &larr; Return to Project
            </a>
            <button type="button" @click="open = true"
                    class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md focus:outline-none focus:ring-2 focus:ring-eids-accent">
                <span class="material-symbols-outlined text-lg">save</span>
                Save Project
            </button>
        </div>
    </form>

    {{-- Save Project confirmation --}}
    <div x-data="{ open: false }" @keydown.escape.window="open = false" x-show="open" x-cloak
         role="dialog" aria-modal="true" aria-labelledby="save-project-title"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="open = false" class="absolute inset-0 bg-black/50 backdrop-blur-xs"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10 border border-gray-100">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-eids-primary/10 text-eids-primary">
                    <span class="material-symbols-outlined filled text-xl">task_alt</span>
                </div>
                <div>
                    <h3 id="save-project-title" class="font-bold text-gray-900 text-base leading-tight">Save Project?</h3>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        {{ $project->samples->count() }} sample unit(s) configured. Saving will finalize project setup,
                        and the assigned inspector can then begin the Components Grid inspection.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="open = false"
                        class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Cancel
                </button>
                <button type="button" @click="document.getElementById('save-project-form').submit()"
                        class="min-h-[44px] px-6 py-2.5 text-xs font-bold text-white rounded-xl shadow-sm transition focus:outline-none focus:ring-2 focus:ring-eids-accent bg-eids-primary hover:bg-eids-dark">
                    Yes, Save Project
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
