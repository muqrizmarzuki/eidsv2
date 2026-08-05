@extends('layouts.app')

@section('title', 'External Works')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.index') }}" class="hover:text-gray-800 transition">Projects</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">External Works</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="bg-eids-primary/5 border border-eids-primary/15 rounded-2xl p-5 mb-6 flex items-start gap-3.5 shadow-2xs">
        <span class="material-symbols-outlined text-eids-accent text-2xl shrink-0 mt-0.5">info</span>
        <div>
            <div class="font-extrabold text-gray-900 text-sm">External Works (Annex C)</div>
            <div class="text-xs text-gray-700 mt-1 leading-relaxed font-medium">
                Add or remove instances of each external element this project has (e.g. 3 separate playgrounds).
                Each instance gets its own full Table 6 sample set. The External Works score is a flat pass-rate
                across every instance's checklist answers.
            </div>
        </div>
    </div>

    @foreach($elements->groupBy(fn ($e) => $e['el']->group) as $group => $groupElements)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h2 class="font-extrabold text-gray-900 text-sm">{{ $group }}</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($groupElements as $code => $entry)
                    @php
                        $el       = $entry['el'];
                        $quantity = $entry['quantity'];
                        $samples  = $entry['samples'];
                    @endphp
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <div class="font-bold text-gray-900 text-sm">{{ $el->name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $quantity }} instance(s) &middot; {{ $el->sample_count }} sample section(s) each, per Table 6</div>
                            </div>
                            <form method="POST" action="{{ route('projects.external.add', [$project, $el->element_code]) }}">
                                @csrf
                                <button type="submit"
                                        class="min-h-[38px] px-4 py-2 rounded-xl text-xs font-extrabold bg-eids-primary/10 text-eids-primary border border-eids-primary/20 hover:bg-eids-primary hover:text-white transition flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">add</span>
                                    Add {{ $el->name }}
                                </button>
                            </form>
                        </div>

                        @if($quantity > 0)
                            <div class="mt-3 space-y-2">
                                @foreach($samples->groupBy('instance_index') as $instanceIndex => $instanceSamples)
                                    <div class="flex items-center gap-2 flex-wrap p-2 bg-gray-50/60 border border-gray-100 rounded-xl">
                                        @foreach($instanceSamples as $sample)
                                            @php
                                                $pr = $sample->pass_rate;
                                                $cls = is_null($pr) ? 'bg-white border-gray-200 text-gray-600' : ($pr >= 80 ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : ($pr >= 60 ? 'bg-amber-50 border-amber-300 text-amber-800' : 'bg-red-50 border-red-300 text-red-800'));
                                            @endphp
                                            <a href="{{ route('projects.external.inspect', [$project, $sample]) }}"
                                               class="min-h-[40px] px-3.5 py-2 rounded-xl border text-xs font-bold flex items-center gap-2 hover:shadow-xs transition {{ $cls }}">
                                                {{ $sample->label }}
                                                <span>{{ is_null($pr) ? 'Pending' : number_format($pr, 1) . '%' }}</span>
                                            </a>
                                        @endforeach
                                        <form method="POST" action="{{ route('projects.external.remove', [$project, $el->element_code, $instanceIndex]) }}"
                                              onsubmit="return confirm('Remove this {{ $el->name }} instance? Its inspection data will be permanently deleted.');" class="ml-auto">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Remove this instance"
                                                    class="w-9 h-9 rounded-lg text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 transition flex items-center justify-center">
                                                <span class="material-symbols-outlined text-lg">close</span>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
        <a href="{{ route('projects.show', $project) }}" class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
            &larr; Return to Project
        </a>
        <a href="{{ route('projects.score', $project) }}" class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
            <span class="material-symbols-outlined text-base">analytics</span>
            View E-IDS Score &rarr;
        </a>
    </div>
</div>
@endsection
