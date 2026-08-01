@extends('layouts.app')

@section('title', 'Inspection Components')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-600">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Components Grid</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('projects.score', $project) }}"
       class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition shadow-xs">
        <span class="material-symbols-outlined text-base">score</span>
        View G-IDS Score &rarr;
    </a>
@endsection

@section('content')

{{-- Pipeline Step Indicator --}}
<x-workflow-step step="3" :project="$project" />

<div class="mb-5 bg-eids-primary/5 border border-eids-primary/10 rounded-2xl p-4 flex items-start gap-3">
    <span class="material-symbols-outlined text-eids-accent text-xl shrink-0 mt-0.5">checklist</span>
    <div>
        <h1 class="font-bold text-gray-800 text-sm">G-IDS Component Inspection Grid</h1>
        <div class="text-xs text-gray-600 mt-0.5 leading-relaxed">
            Click <strong>Inspect</strong> on any cell to open the 5-point assessment form.
            A green badge indicates PASS, red indicates FAIL, grey indicates pending inspection.
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-xs">
        {{-- Header row with component codes --}}
        <thead>
            <tr class="border-b border-gray-100">
                <th class="px-4 py-3.5 text-left font-semibold text-gray-700 text-sm min-w-40 bg-gray-50/80">Sample Location</th>
                @foreach($components as $code => $comp)
                    <th class="px-3 py-3.5 text-center font-medium text-gray-500 min-w-24 bg-gray-50/80">
                        <div class="font-mono text-[10px] text-gray-400 font-bold">{{ $code }}</div>
                        <div class="text-[11px] font-bold text-gray-800 leading-tight mt-0.5 max-w-24 mx-auto">
                            {{ Str::before($comp['name'], ' (') }}
                        </div>
                        <div class="text-[10px] text-eids-primary font-extrabold mt-0.5">{{ $comp['weightage'] }}%</div>
                    </th>
                @endforeach
                <th class="px-3 py-3.5 text-center font-medium text-gray-700 bg-gray-50/80 min-w-24">Progress</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($project->samples as $sample)
                @php
                    $assessedCodes = $sample->assessments->pluck('component_code')->toArray();
                    $totalComp = count($components);
                    $doneCount = count($assessedCodes);
                    $pct = $totalComp > 0 ? round(($doneCount / $totalComp) * 100) : 0;
                @endphp
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-4 py-4">
                        <div class="font-bold text-gray-800 text-sm">{{ $sample->location_name }}</div>
                        <div class="text-gray-400 text-[11px] mt-0.5">Sample #{{ $sample->sample_index }}</div>
                    </td>
                    @foreach($components as $code => $comp)
                        @php
                            $assessment = $sample->assessments->where('component_code', $code)->first();
                            $isPending  = !$assessment;
                            $isPass     = $assessment && $assessment->overall_sample_status === 'PASS';
                            $isFail     = $assessment && $assessment->overall_sample_status === 'FAIL';
                        @endphp
                        <td class="px-3 py-3.5 text-center">
                            @if(auth()->user()->canInspect())
                                <a href="{{ route('projects.inspect', [$project, $sample, 'component' => $code]) }}"
                                   aria-label="Inspect {{ $code }} for {{ $sample->location_name }}"
                                   class="inline-flex flex-col items-center gap-1 group min-h-[44px] min-w-[44px] justify-center p-1 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent">
                                    @if($isPending)
                                        <span class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-eids-primary group-hover:text-white transition shadow-2xs">
                                            <span class="material-symbols-outlined text-base">add</span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-gray-400 group-hover:text-eids-primary transition">Inspect</span>
                                    @elseif($isPass)
                                        <span class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-700 group-hover:bg-emerald-200 transition shadow-2xs">
                                            <span class="material-symbols-outlined text-base font-bold">check</span>
                                        </span>
                                        <span class="text-[10px] text-emerald-700 font-extrabold">PASS</span>
                                    @else
                                        <span class="w-10 h-10 rounded-full bg-red-100 border border-red-300 flex items-center justify-center text-red-700 group-hover:bg-red-200 transition shadow-2xs">
                                            <span class="material-symbols-outlined text-base font-bold">close</span>
                                        </span>
                                        <span class="text-[10px] text-red-700 font-extrabold">FAIL</span>
                                    @endif
                                </a>
                            @else
                                @if($isPending)
                                    <span class="text-gray-300 text-base">—</span>
                                @elseif($isPass)
                                    <span class="text-emerald-600 font-bold text-base">✓</span>
                                @else
                                    <span class="text-red-600 font-bold text-base">✗</span>
                                @endif
                            @endif
                        </td>
                    @endforeach
                    <td class="px-3 py-3.5">
                        <div class="flex flex-col items-center gap-1">
                            <div class="text-xs font-extrabold {{ $pct === 100 ? 'text-emerald-700' : ($pct > 0 ? 'text-amber-700' : 'text-gray-400') }}">
                                {{ $pct }}%
                            </div>
                            <div class="w-14 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $pct === 100 ? 'bg-emerald-500' : 'bg-eids-accent' }} rounded-full transition-all"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
