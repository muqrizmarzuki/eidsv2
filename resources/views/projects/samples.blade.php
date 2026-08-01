@extends('layouts.app')

@section('title', 'Configure Samples')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-600">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Samples</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('projects.components', $project) }}"
       class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition shadow-xs">
        <span class="material-symbols-outlined text-base">checklist</span>
        Next: Components Grid &rarr;
    </a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="2" :project="$project" />

    {{-- Info Banner --}}
    <div class="bg-eids-primary/5 border border-eids-primary/10 rounded-2xl p-4 mb-5 flex items-start gap-3">
        <span class="material-symbols-outlined text-eids-accent text-xl shrink-0 mt-0.5">info</span>
        <div>
            <div class="font-bold text-gray-800 text-sm">Sample Units Configuration</div>
            <div class="text-xs text-gray-600 mt-1 leading-relaxed">
                Formula: N = max(1, ceil(GFA &divide; {{ $divisor }})) = <strong>{{ $project->calculated_samples }} sample units</strong>.
                Assign a room or location name to each sample unit before proceeding to the inspection grid.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('projects.samples.store', $project) }}">
        @csrf

        {{-- Sample units card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-base">home_work</span>
                    Sample Units ({{ $project->samples->count() }})
                </h2>
                <span class="text-xs text-gray-400 font-mono">GFA: {{ number_format($project->floor_area_sqm, 2) }} m²</span>
            </div>

            @if($project->samples->isEmpty())
                <div class="py-16 text-center text-sm text-gray-400">
                    No samples have been generated. Please recreate this project.
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($project->samples as $index => $sample)
                        @php
                            $current    = old("locations.{$sample->id}", $sample->location_name);
                            $inDefaults = in_array($current, $defaultLocations);
                            $initMode   = ($inDefaults || empty($current)) ? 'select' : 'custom';
                            $initSelect = $inDefaults ? $current : ($defaultLocations[0] ?? '');
                            $initCustom = $inDefaults ? '' : $current;
                        @endphp
                        <div class="flex items-center gap-4 px-5 py-4"
                             x-data="{
                                 mode: '{{ $initMode }}',
                                 sel: '{{ addslashes($initSelect) }}',
                                 txt: '{{ addslashes($initCustom) }}',
                                 get val() { return this.mode === 'select' ? this.sel : this.txt; },
                                 switchCustom() { this.mode = 'custom'; this.txt = ''; this.$nextTick(() => this.$refs.txt.focus()); },
                                 switchSelect() { this.mode = 'select'; this.sel = '{{ addslashes($initSelect ?: ($defaultLocations[0] ?? '')) }}'; }
                             }">

                            {{-- Index badge --}}
                            <div class="w-9 h-9 rounded-full bg-eids-primary/10 text-eids-primary flex items-center justify-center text-xs font-bold shrink-0">
                                #{{ $sample->sample_index }}
                            </div>

                            {{-- Location picker --}}
                            <div class="flex-1 flex items-center gap-2">
                                {{-- Select mode --}}
                                <template x-if="mode === 'select'">
                                    <div class="flex flex-1 gap-2">
                                        <select x-model="sel"
                                                @change="if(sel === '__custom__') switchCustom()"
                                                class="flex-1 min-h-[44px] px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent bg-white">
                                            @foreach($defaultLocations as $loc)
                                                <option value="{{ $loc }}">{{ $loc }}</option>
                                            @endforeach
                                            <option value="__custom__">Custom Location Name...</option>
                                        </select>
                                    </div>
                                </template>

                                {{-- Custom text mode --}}
                                <template x-if="mode === 'custom'">
                                    <div class="flex flex-1 gap-2">
                                        <input x-ref="txt" type="text" x-model="txt"
                                               placeholder="Enter custom location name"
                                               class="flex-1 min-h-[44px] px-3.5 py-2.5 border border-eids-accent/50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                                        @if(!empty($defaultLocations))
                                            <button type="button" @click="switchSelect()"
                                                    class="min-h-[44px] px-3.5 py-2 text-xs font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition whitespace-nowrap">
                                                Use Default
                                            </button>
                                        @endif
                                    </div>
                                </template>

                                {{-- Hidden input that always submits the actual value --}}
                                <input type="hidden" name="locations[{{ $sample->id }}]" :value="val">
                            </div>

                            {{-- Status badge --}}
                            @if(!is_null($sample->pass_rate))
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full shrink-0">
                                    Inspected
                                </span>
                            @else
                                <span class="text-xs font-medium text-gray-400 bg-gray-50 border border-gray-100 px-3 py-1 rounded-full shrink-0">
                                    Pending
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Component Preview (placed above primary actions) --}}
        <div class="mb-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-base">checklist</span>
                    Architectural Components to Inspect ({{ count($components) }})
                </h2>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($components as $code => $comp)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xs font-bold text-gray-500">{{ $code }}</span>
                            <span class="text-sm font-medium text-gray-700">{{ $comp['name'] }}</span>
                        </div>
                        <span class="text-xs font-bold text-eids-primary bg-eids-primary/5 px-2.5 py-0.5 rounded-full">
                            {{ $comp['weightage'] }}%
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Primary Action buttons --}}
        <div class="flex justify-between items-center gap-3 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sticky bottom-4 z-10">
            <a href="{{ route('projects.show', $project) }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-1.5">
                &larr; Back to Project
            </a>
            <button type="submit"
                    class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md focus:outline-none focus:ring-2 focus:ring-eids-accent">
                <span class="material-symbols-outlined text-base">save</span>
                Save &amp; Continue to Grid &rarr;
            </button>
        </div>
    </form>

</div>
@endsection
