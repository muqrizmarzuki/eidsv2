@extends('layouts.app')

@section('title', 'Inspection Components Grid')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Components Grid</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('projects.score', $project) }}"
       class="flex items-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
        <span class="material-symbols-outlined text-lg">analytics</span>
        View G-IDS Score &rarr;
    </a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto" x-data="{ viewMode: 'cards' }">

{{-- Pipeline Step Indicator --}}
<x-workflow-step step="3" :project="$project" />

{{-- Header Banner & View Switcher Toggle --}}
<div class="mb-6 bg-white rounded-2xl border border-gray-200 p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-eids-primary/10 border border-eids-primary/20 text-eids-primary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-xl">checklist</span>
        </div>
        <div>
            <h1 class="font-extrabold text-gray-900 text-sm">G-IDS Component Inspection Grid</h1>
            <p class="text-xs text-gray-600 mt-0.5 font-medium">Select any component cell to record 5-point inspection criteria.</p>
        </div>
    </div>

    {{-- View Switcher Buttons --}}
    <div class="flex items-center bg-gray-100 p-1 rounded-xl border border-gray-200 shrink-0">
        <button type="button" @click="viewMode = 'cards'"
                :class="viewMode === 'cards' ? 'bg-white text-eids-primary shadow-2xs font-extrabold' : 'text-gray-600 hover:text-gray-900 font-semibold'"
                class="min-h-[38px] px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">grid_view</span>
            Cards View
        </button>
        <button type="button" @click="viewMode = 'table'"
                :class="viewMode === 'table' ? 'bg-white text-eids-primary shadow-2xs font-extrabold' : 'text-gray-600 hover:text-gray-900 font-semibold'"
                class="min-h-[38px] px-3.5 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base">table_chart</span>
            Matrix Table
        </button>
    </div>
</div>

{{-- OPTION A: CARDS VIEW (No Horizontal Scroll - Mobile & Touch Optimized) --}}
<div x-show="viewMode === 'cards'" class="space-y-6">
    @foreach($project->samples as $sample)
        @php
            $assessedCodes = $sample->assessments->pluck('component_code')->toArray();
            $totalComp = count($components);
            $doneCount = count($assessedCodes);
            $pct = $totalComp > 0 ? round(($doneCount / $totalComp) * 100) : 0;
            $passCount = $sample->assessments->where('overall_sample_status', 'PASS')->count();
            $failCount = $sample->assessments->where('overall_sample_status', 'FAIL')->count();
        @endphp

        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            {{-- Sample Card Header --}}
            <div class="px-6 py-4 bg-gray-50/80 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-eids-primary text-white text-xs font-mono font-extrabold flex items-center justify-center shadow-2xs">
                        #{{ $sample->sample_index }}
                    </span>
                    <div>
                        <h2 class="font-extrabold text-gray-900 text-sm leading-tight">{{ $sample->location_name }}</h2>
                        <div class="text-[11px] text-gray-500 font-medium mt-0.5">
                            {{ $doneCount }} of {{ $totalComp }} components inspected
                            @if($doneCount > 0)
                                · <span class="text-emerald-700 font-bold">{{ $passCount }} PASS</span>
                                @if($failCount > 0)
                                    · <span class="text-red-700 font-bold">{{ $failCount }} FAIL</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Progress Pill --}}
                <div class="flex items-center gap-3">
                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-eids-accent' }} transition-all duration-300" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-xs font-extrabold font-mono {{ $pct === 100 ? 'text-emerald-700' : 'text-gray-700' }}">{{ $pct }}%</span>
                </div>
            </div>

            {{-- Component Touch Tiles Grid (2-cols on mobile, 4-cols on sm/md) --}}
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                @foreach($components as $code => $comp)
                    @php
                        $assessment = $sample->assessments->where('component_code', $code)->first();
                        $isPending  = !$assessment;
                        $isPass     = $assessment && $assessment->overall_sample_status === 'PASS';
                        $isFail     = $assessment && $assessment->overall_sample_status === 'FAIL';
                    @endphp

                    @if(auth()->user()->canInspect())
                        <a href="{{ route('projects.inspect', [$project, $sample, 'component' => $code]) }}"
                           class="min-h-[64px] p-3 rounded-xl border transition-all duration-200 flex items-center justify-between gap-3 group focus:outline-none focus:ring-2 focus:ring-eids-accent
                                  {{ $isPass ? 'bg-emerald-50/50 border-emerald-200 hover:bg-emerald-100/60' : ($isFail ? 'bg-red-50/50 border-red-200 hover:bg-red-100/60' : 'bg-gray-50/60 border-gray-200 hover:bg-white hover:border-eids-accent hover:shadow-xs') }}">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="font-mono text-[11px] font-extrabold px-1.5 py-0.2 rounded
                                                 {{ $isPass ? 'bg-emerald-200/70 text-emerald-900' : ($isFail ? 'bg-red-200/70 text-red-900' : 'bg-gray-200 text-gray-800') }}">
                                        {{ $code }}
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-500">{{ $comp['weightage'] }}%</span>
                                </div>
                                <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $comp['name'] }}</div>
                            </div>

                            <div class="shrink-0">
                                @if($isPending)
                                    <span class="w-8 h-8 rounded-full bg-white border border-gray-300 flex items-center justify-center text-gray-500 group-hover:bg-eids-primary group-hover:text-white group-hover:border-eids-primary transition shadow-2xs">
                                        <span class="material-symbols-outlined text-base">add</span>
                                    </span>
                                @elseif($isPass)
                                    <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow-2xs">
                                        <span class="material-symbols-outlined text-base font-extrabold">check</span>
                                    </span>
                                @else
                                    <span class="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center shadow-2xs">
                                        <span class="material-symbols-outlined text-base font-extrabold">close</span>
                                    </span>
                                @endif
                            </div>
                        </a>
                    @else
                        <div class="min-h-[64px] p-3 rounded-xl border border-gray-200 flex items-center justify-between gap-3 bg-gray-50">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="font-mono text-[11px] font-extrabold px-1.5 py-0.2 rounded bg-gray-200 text-gray-800">{{ $code }}</span>
                                    <span class="text-[10px] font-bold text-gray-500">{{ $comp['weightage'] }}%</span>
                                </div>
                                <div class="text-xs font-bold text-gray-900 truncate leading-tight">{{ $comp['name'] }}</div>
                            </div>
                            <div class="shrink-0 font-extrabold text-xs">
                                @if($isPending)<span class="text-gray-400">—</span>
                                @elseif($isPass)<span class="text-emerald-700">PASS</span>
                                @else<span class="text-red-700">FAIL</span>@endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>

{{-- OPTION B: MATRIX TABLE VIEW (Spreadsheet Layout) --}}
<div x-show="viewMode === 'table'" x-cloak class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-x-auto">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80">
                <th class="px-5 py-4 text-left font-extrabold text-gray-900 text-sm min-w-48">Sample Location</th>
                @foreach($components as $code => $comp)
                    <th class="px-3 py-4 text-center font-medium text-gray-700 min-w-28 border-l border-gray-100">
                        <div class="font-mono text-xs text-eids-primary font-extrabold bg-eids-primary/10 py-0.5 px-2 rounded-md inline-block mb-1">{{ $code }}</div>
                        <div class="text-xs font-bold text-gray-900 leading-snug truncate max-w-28 mx-auto" title="{{ $comp['name'] }}">
                            {{ Str::before($comp['name'], ' (') }}
                        </div>
                        <div class="text-[11px] text-eids-accent font-extrabold mt-1">{{ $comp['weightage'] }}% Weight</div>
                    </th>
                @endforeach
                <th class="px-4 py-4 text-center font-extrabold text-gray-900 bg-gray-50/80 min-w-28 border-l border-gray-100">Progress</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($project->samples as $sample)
                @php
                    $assessedCodes = $sample->assessments->pluck('component_code')->toArray();
                    $totalComp = count($components);
                    $doneCount = count($assessedCodes);
                    $pct = $totalComp > 0 ? round(($doneCount / $totalComp) * 100) : 0;
                @endphp
                <tr class="hover:bg-gray-50/80 transition">
                    <td class="px-5 py-4">
                        <div class="font-extrabold text-gray-900 text-sm">{{ $sample->location_name }}</div>
                        <div class="text-gray-500 text-xs font-mono font-semibold mt-0.5">Sample Unit #{{ $sample->sample_index }}</div>
                    </td>
                    @foreach($components as $code => $comp)
                        @php
                            $assessment = $sample->assessments->where('component_code', $code)->first();
                            $isPending  = !$assessment;
                            $isPass     = $assessment && $assessment->overall_sample_status === 'PASS';
                            $isFail     = $assessment && $assessment->overall_sample_status === 'FAIL';
                        @endphp
                        <td class="px-3 py-4 text-center border-l border-gray-100/60">
                            @if(auth()->user()->canInspect())
                                <a href="{{ route('projects.inspect', [$project, $sample, 'component' => $code]) }}"
                                   aria-label="Inspect {{ $code }} for {{ $sample->location_name }}"
                                   class="inline-flex flex-col items-center gap-1 group min-h-[44px] min-w-[44px] justify-center p-1 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent">
                                    @if($isPending)
                                        <span class="w-10 h-10 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500 group-hover:bg-eids-primary group-hover:text-white group-hover:border-eids-primary transition shadow-2xs">
                                            <span class="material-symbols-outlined text-lg">add</span>
                                        </span>
                                        <span class="text-[10px] font-bold text-gray-500 group-hover:text-eids-primary transition">Inspect</span>
                                    @elseif($isPass)
                                        <span class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-800 group-hover:bg-emerald-200 transition shadow-2xs">
                                            <span class="material-symbols-outlined text-xl font-extrabold">check</span>
                                        </span>
                                        <span class="text-[10px] text-emerald-800 font-extrabold">PASS</span>
                                    @else
                                        <span class="w-10 h-10 rounded-full bg-red-100 border border-red-300 flex items-center justify-center text-red-800 group-hover:bg-red-200 transition shadow-2xs">
                                            <span class="material-symbols-outlined text-xl font-extrabold">close</span>
                                        </span>
                                        <span class="text-[10px] text-red-800 font-extrabold">FAIL</span>
                                    @endif
                                </a>
                            @else
                                @if($isPending)<span class="text-gray-300 font-bold text-base">—</span>
                                @elseif($isPass)<span class="text-emerald-700 font-extrabold text-base">✓ PASS</span>
                                @else<span class="text-red-700 font-extrabold text-base">✗ FAIL</span>@endif
                            @endif
                        </td>
                    @endforeach
                    <td class="px-4 py-4 border-l border-gray-100">
                        <div class="flex flex-col items-center gap-1.5">
                            <div class="text-xs font-extrabold {{ $pct === 100 ? 'text-emerald-700' : ($pct > 0 ? 'text-amber-700' : 'text-gray-400') }}">
                                {{ $pct }}%
                            </div>
                            <div class="w-16 h-2 bg-gray-100 rounded-full overflow-hidden border border-gray-200/50">
                                <div class="h-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-eids-accent' }} rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Bottom Action Bar --}}
<div class="mt-6 flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
    <a href="{{ route('projects.samples', $project) }}" class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
        &larr; Back to Sample Setup
    </a>
    <a href="{{ route('projects.score', $project) }}" class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
        <span class="material-symbols-outlined text-base">analytics</span>
        Proceed to G-IDS Score Breakdown &rarr;
    </a>
</div>

</div>
@endsection
