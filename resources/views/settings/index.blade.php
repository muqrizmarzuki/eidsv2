@extends('layouts.app')

@section('title', 'Settings')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Settings</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf

        {{-- Formula Parameters --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-base">calculate</span>
                <h2 class="font-semibold text-gray-800 text-sm">G-IDS Formula Parameters</h2>
            </div>

            <div class="divide-y divide-gray-50">
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
                    <div class="flex items-start gap-4 px-5 py-4">
                        <div class="flex-1">
                            <label for="s_{{ $key }}"
                                   class="block text-sm font-semibold text-gray-700 mb-0.5">
                                {{ $setting->label }}
                            </label>
                            @if($setting->description)
                                <p class="text-xs text-gray-400">{{ $setting->description }}</p>
                            @endif
                            @error("settings.{$key}")
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center gap-2 shrink-0 mt-0.5">
                            <input id="s_{{ $key }}"
                                   type="{{ $setting->type === 'number' ? 'number' : 'text' }}"
                                   name="settings[{{ $key }}]"
                                   value="{{ old("settings.{$key}", $setting->value) }}"
                                   step="{{ $opts['step'] ?? '0.01' }}"
                                   min="{{ $opts['min'] ?? '0' }}"
                                   class="w-28 px-3 py-2 text-sm border border-gray-200 rounded-lg text-right focus:outline-none focus:ring-2 focus:ring-eids-accent font-mono {{ $errors->has("settings.{$key}") ? 'border-red-400' : '' }}">
                            @if($setting->unit)
                                <span class="text-xs text-gray-400 w-6">{{ $setting->unit }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Default Sample Locations --}}
        @php
            $locSetting  = $settings['default_locations'] ?? null;
            $locationsList = $locSetting ? (json_decode($locSetting->value, true) ?? []) : $locations;
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-5"
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
            <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-base">home_work</span>
                <h2 class="font-semibold text-gray-800 text-sm">Default Sample Locations</h2>
                <span class="ml-auto text-xs text-gray-400" x-text="locs.length + ' location(s)'"></span>
            </div>

            <div class="px-5 py-4">
                <p class="text-xs text-gray-400 mb-4">{{ $locSetting?->description ?? 'Pre-filled location names assigned to samples when a new project is created.' }}</p>

                {{-- Existing chips --}}
                <div class="flex flex-wrap gap-2 mb-4 min-h-[2.5rem]">
                    <template x-for="(loc, i) in locs" :key="i">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-eids-primary/5 border border-eids-primary/15 rounded-lg text-sm text-gray-700">
                            <span x-text="loc"></span>
                            <button type="button" @click="remove(i)"
                                    class="text-gray-400 hover:text-red-500 transition ml-1 leading-none">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </button>
                            <input type="hidden" name="locations[]" :value="loc">
                        </div>
                    </template>
                    <p x-show="locs.length === 0" class="text-xs text-gray-400 italic py-1.5">No locations defined — samples will be named "Sample 1", "Sample 2", etc.</p>
                </div>

                {{-- Add new --}}
                <div class="flex gap-2">
                    <input x-ref="newInput" type="text" x-model="newLoc" @keydown="handleKey($event)"
                           placeholder="Add location name..."
                           class="flex-1 px-3.5 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                    <button type="button" @click="add()"
                            class="flex items-center gap-1.5 px-4 py-2 border border-eids-accent/30 text-eids-primary text-sm font-medium rounded-lg hover:bg-eids-accent/5 transition">
                        <span class="material-symbols-outlined text-base">add</span>
                        Add
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">Press Enter or click Add. Drag to reorder is not supported — delete and re-add to change order.</p>
            </div>
        </div>

        {{-- Save button --}}
        <div class="flex items-center justify-between mb-5">
            <p class="text-xs text-gray-400">Changes take effect immediately for all new projects and calculations.</p>
            <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-semibold rounded-lg hover:bg-eids-dark transition">
                <span class="material-symbols-outlined text-base">save</span>
                Save Settings
            </button>
        </div>

    </form>

    {{-- Component Registry (read-only) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
            <span class="material-symbols-outlined text-eids-accent text-base">construction</span>
            <h2 class="font-semibold text-gray-800 text-sm">Component Registry ({{ count($components) }})</h2>
            <span class="ml-auto text-xs text-gray-400">Configured in <code class="bg-gray-100 px-1.5 py-0.5 rounded">config/eids.php</code></span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-left font-medium">Code</th>
                    <th class="px-4 py-3 text-left font-medium">Component Name</th>
                    <th class="px-4 py-3 text-right font-medium">Weightage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php $totalWeight = 0; @endphp
                @foreach($components as $code => $comp)
                    @php $totalWeight += $comp['weightage']; @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-mono text-xs font-semibold text-eids-primary">{{ $code }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $comp['name'] }}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800">{{ $comp['weightage'] }}%</td>
                    </tr>
                @endforeach
                <tr class="bg-eids-primary/5 border-t-2 border-eids-primary/10">
                    <td class="px-5 py-3 font-semibold text-eids-primary" colspan="2">Total Architectural Weight</td>
                    <td class="px-4 py-3 text-right font-bold text-eids-primary text-base">{{ $totalWeight }}%</td>
                </tr>
            </tbody>
        </table>
    </div>


</div>
@endsection
