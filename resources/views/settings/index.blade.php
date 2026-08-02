@extends('layouts.app')

@section('title', 'System Settings')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">System Settings</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf

        {{-- Formula Parameters --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">calculate</span>
                <h2 class="font-extrabold text-gray-900 text-sm">G-IDS Formula &amp; Scoring Parameters</h2>
            </div>

            <div class="divide-y divide-gray-100">
                @php
                    $fields = [
                        'sample_divisor'   => ['step' => '1',    'min' => '1'],
                        'levelling_max_mm' => ['step' => '0.1',  'min' => '0.1'],
                        'joint_max_mm'     => ['step' => '0.1',  'min' => '0.1'],
                        'me_score'         => ['step' => '0.01', 'min' => '0'],
                        'external_score'   => ['step' => '0.01', 'min' => '0'],
                        'rating_baik'      => ['step' => '1',    'min' => '1'],
                        'rating_sederhana' => ['step' => '1',    'min' => '1'],
                    ];
                @endphp

                @foreach($settings as $key => $setting)
                    @if($setting->type === 'json') @continue @endif
                    @php $opts = $fields[$key] ?? []; @endphp
                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <div class="flex-1">
                            <label for="s_{{ $key }}"
                                   class="block text-sm font-extrabold text-gray-900 mb-0.5">
                                {{ $setting->label }}
                            </label>
                            @if($setting->description)
                                <p class="text-xs text-gray-500 font-medium leading-relaxed">{{ $setting->description }}</p>
                            @endif
                            @error("settings.{$key}")
                                <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center gap-2 shrink-0 mt-0.5">
                            <input id="s_{{ $key }}"
                                   type="{{ $setting->type === 'number' ? 'number' : 'text' }}"
                                   name="settings[{{ $key }}]"
                                   value="{{ old("settings.{$key}", $setting->value) }}"
                                   step="{{ $opts['step'] ?? '0.01' }}"
                                   min="{{ $opts['min'] ?? '0' }}"
                                   class="w-32 min-h-[44px] px-3.5 py-2 text-sm border border-gray-200 rounded-xl text-right focus:outline-none focus:ring-2 focus:ring-eids-accent font-mono font-bold text-gray-900 bg-white {{ $errors->has("settings.{$key}") ? 'border-red-400 bg-red-50' : '' }}">
                            @if($setting->unit)
                                <span class="text-xs text-gray-500 font-bold w-8 shrink-0">{{ $setting->unit }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Default Sample Locations Tag Editor --}}
        @php
            $locSetting  = $settings['default_locations'] ?? null;
            $locationsList = $locSetting ? (json_decode($locSetting->value, true) ?? []) : $locations;
        @endphp
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6"
             x-data="{
                 locs: {{ Js::from($locationsList) }},
                 newLoc: '',
                 add() {
                     const t = this.newLoc.trim();
                     if (t && !this.locs.includes(t)) { this.locs.push(t); }
                     this.newLoc = '';
                     this.$nextTick(() => this.$refs.newInput.focus());
                 },
                 remove(i) { this.locs.splice(i, 1); },
                 handleKey(e) { if (e.key === 'Enter') { e.preventDefault(); this.add(); } }
             }">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">home_work</span>
                    <h2 class="font-extrabold text-gray-900 text-sm">Default Sample Location Names</h2>
                </div>
                <span class="text-xs text-gray-500 font-mono font-bold" x-text="locs.length + ' location(s)'"></span>
            </div>

            <div class="p-6">
                <p class="text-xs text-gray-500 font-medium mb-4">{{ $locSetting?->description ?? 'Pre-filled location names assigned to samples when a new project is registered.' }}</p>

                {{-- Location Chips --}}
                <div class="flex flex-wrap gap-2 mb-5 min-h-[3rem] p-3 bg-gray-50 rounded-xl border border-gray-200">
                    <template x-for="(loc, i) in locs" :key="i">
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-eids-primary/10 border border-eids-primary/20 rounded-xl text-xs font-bold text-eids-primary shadow-2xs">
                            <span x-text="loc"></span>
                            <button type="button" @click="remove(i)"
                                    class="text-eids-primary/60 hover:text-red-600 transition ml-1 leading-none p-0.5 rounded-full hover:bg-red-50">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </button>
                            <input type="hidden" name="locations[]" :value="loc">
                        </div>
                    </template>
                    <p x-show="locs.length === 0" class="text-xs text-gray-400 italic py-1">No default locations defined — samples will default to "Sample 1", "Sample 2", etc.</p>
                </div>

                {{-- Add Location Input --}}
                <div class="flex gap-3">
                    <input x-ref="newInput" type="text" x-model="newLoc" @keydown="handleKey($event)"
                           placeholder="Enter location name (e.g. Master Bedroom, Living Room)..."
                           class="flex-1 min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                    <button type="button" @click="add()"
                            class="min-h-[44px] flex items-center gap-1.5 px-5 py-2.5 bg-eids-primary/10 border border-eids-primary/20 text-eids-primary text-sm font-extrabold rounded-xl hover:bg-eids-primary hover:text-white transition">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Add Location
                    </button>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex items-center justify-between mb-8 bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
            <p class="text-xs text-gray-500 font-medium">Changes take effect immediately for all new projects and scoring logic.</p>
            <button type="submit"
                    class="min-h-[44px] flex items-center gap-2 px-6 py-2.5 bg-eids-primary text-white text-sm font-extrabold rounded-xl hover:bg-eids-dark transition shadow-md">
                <span class="material-symbols-outlined text-lg">save</span>
                Save All Settings
            </button>
        </div>

    </form>

    {{-- Component Registry (Read-only) --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">construction</span>
                <h2 class="font-extrabold text-gray-900 text-sm">Architectural Component Weightage Registry ({{ count($components) }})</h2>
            </div>
            <span class="text-xs text-gray-500 font-mono font-semibold">Configured in <code class="bg-gray-100 px-2 py-0.5 rounded text-eids-primary">config/eids.php</code></span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                <tr>
                    <th class="px-6 py-3.5 text-left">Code</th>
                    <th class="px-4 py-3.5 text-left">Component Name</th>
                    <th class="px-6 py-3.5 text-right">Weightage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $totalWeight = 0; @endphp
                @foreach($components as $code => $comp)
                    @php $totalWeight += $comp['weightage']; @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3.5 font-mono text-xs font-extrabold text-eids-primary">{{ $code }}</td>
                        <td class="px-4 py-3.5 text-gray-900 font-semibold">{{ $comp['name'] }}</td>
                        <td class="px-6 py-3.5 text-right font-extrabold text-gray-900 font-mono">{{ $comp['weightage'] }}%</td>
                    </tr>
                @endforeach
                <tr class="bg-eids-primary/5 border-t-2 border-eids-primary/20">
                    <td class="px-6 py-4 font-extrabold text-eids-primary" colspan="2">Total Architectural Component Weight</td>
                    <td class="px-6 py-4 text-right font-extrabold text-eids-primary text-base font-mono">{{ $totalWeight }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
