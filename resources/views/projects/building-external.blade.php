@extends('layouts.app')

@section('title', 'Building External Finishes')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">Roof / External Wall / Apron / Car Park</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-2xl p-5 mb-6 flex items-start gap-3.5 shadow-2xs">
        <span class="material-symbols-outlined text-eids-accent text-2xl shrink-0 mt-0.5">info</span>
        <div>
            <div class="font-extrabold text-gray-900 text-sm">Building-Level Architectural Components (Table 3)</div>
            <div class="text-xs text-gray-700 mt-1 leading-relaxed font-medium">
                Roof, External Wall, Apron &amp; Perimeter Drain and Car Park aren't tied to any specific room;
                the standard samples them as building sections/lengths. Roof and External Wall sample count is
                50% of the project's units (min 4 sections); Apron/Drain and Car Park use a minimum of 2 length-sections.
            </div>
        </div>
    </div>

    @foreach($elements as $code => $entry)
        @php
            $el      = $entry['el'];
            $samples = $entry['samples'];
        @endphp
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <h2 class="font-extrabold text-gray-900 text-sm">{{ $el->name }} <span class="text-gray-400 font-mono text-xs">({{ $code }})</span></h2>
                <span class="text-xs text-gray-500 font-mono font-semibold">{{ $samples->count() }} section(s)</span>
            </div>
            <div class="p-4 flex flex-wrap gap-2">
                @foreach($samples as $sample)
                    @php
                        $pr = $sample->pass_rate;
                        $cls = is_null($pr) ? 'bg-gray-50 border-gray-200 text-gray-600' : ($pr >= 80 ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : ($pr >= 60 ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-red-50 border-red-300 text-red-800'));
                    @endphp
                    <a href="{{ route('projects.building-external.inspect', [$project, $sample]) }}"
                       class="min-h-[40px] px-3.5 py-2 rounded-xl border text-xs font-bold flex items-center gap-2 hover:shadow-xs transition {{ $cls }}">
                        {{ $sample->label }}
                        <span>{{ is_null($pr) ? 'Pending' : number_format($pr, 1) . '%' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
        <a href="{{ route('projects.components', $project) }}" class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
            &larr; Back to Components Grid
        </a>
        <a href="{{ route('projects.external', $project) }}" class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
            External Works (Annex C) &rarr;
        </a>
    </div>
</div>
@endsection
