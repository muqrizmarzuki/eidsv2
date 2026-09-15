@extends('layouts.app')

@section('title', 'Inspect: ' . $componentCode)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-28">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ $gridUrl }}" class="hover:text-gray-800 transition">Grid</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">{{ $sample->location_name }} / {{ $componentCode }}</span>
@endsection

@php
    $initialAnswers = [];
    foreach ($items as $item) {
        $answer = $answers->get($item->id);
        $initialAnswers[(string) $item->id] = [
            'type'   => $item->input_type,
            'max'    => $item->tolerance_max_mm !== null ? (float) $item->tolerance_max_mm : null,
            'result' => $answer->result ?? 'PASS',
            'value'  => $answer?->numeric_value !== null ? (string) $answer->numeric_value : '',
        ];
    }
@endphp

@section('content')
<div class="max-w-5xl mx-auto flex flex-col w-full min-h-[calc(100dvh-6rem)] sm:min-h-[calc(100dvh-7rem)] lg:min-h-[calc(100dvh-8rem)]"
     x-data="{
         answers: {{ Js::from($initialAnswers) }},
         statusFor(id) {
             const a = this.answers[id];
             if (a.type === 'numeric_with_tolerance') {
                 const v = parseFloat(a.value);
                 if (isNaN(v) || a.value === '' || a.max === null) return 'PASS';
                 return v <= a.max ? 'PASS' : 'FAIL';
             }
             return a.result;
         },
         get overall() {
             return Object.keys(this.answers).some(id => this.statusFor(id) === 'FAIL') ? 'FAIL' : 'PASS';
         },
     }">

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="4" :project="$project" />

    {{-- Default-PASS Warning Box for New Records --}}
    @if(!$assessment)
        <div class="bg-amber-50 border border-amber-300 rounded-2xl p-5 mb-6 flex items-start gap-3.5 shadow-2xs">
            <div class="w-10 h-10 rounded-xl bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0 text-amber-800">
                <span class="material-symbols-outlined filled text-xl">warning</span>
            </div>
            <div>
                <div class="text-sm font-extrabold text-amber-950">New Inspection Record Initialization</div>
                <div class="text-xs text-amber-900 mt-1 leading-relaxed font-medium">
                    Check values default to <strong>PASS</strong>. Inspect each criterion on-site and explicitly select <strong>FAIL</strong> wherever defects exist before saving.
                </div>
            </div>
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-2xl px-5 py-3 mb-6 flex items-center justify-between text-xs text-blue-900 shadow-2xs font-medium">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-blue-700">history</span>
                <span>Existing assessment loaded for <strong>{{ $componentCode }}</strong></span>
            </div>
            <span class="text-blue-700 font-bold">Last saved {{ $assessment->updated_at->diffForHumans() }}</span>
        </div>
    @endif

    {{-- Component & Sample Header --}}
    <div class="bg-eids-primary rounded-2xl p-6 mb-6 text-white shadow-md border border-white/10">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="text-eids-light text-xs font-extrabold uppercase tracking-wider mb-1">
                    {{ $project->project_name }} &nbsp;·&nbsp; Component {{ $componentPos }} of {{ $componentCount }}
                </div>
                <h1 class="font-extrabold text-2xl text-white tracking-tight">{{ $component['name'] }} ({{ $componentCode }})</h1>
                <div class="text-white/80 text-sm mt-1.5 flex items-center gap-2 font-medium">
                    <span class="material-symbols-outlined text-base text-eids-light">home_work</span>
                    @if($sample->unit_reference){{ $sample->unit_reference }} &nbsp;·&nbsp; @endif{{ $sample->location_name }} &nbsp;·&nbsp; Sample #{{ $sample->sample_index }}
                </div>
            </div>
            @if($component['weightage'] !== null)
                <div class="text-center bg-white/10 px-5 py-3 rounded-xl backdrop-blur-xs border border-white/10">
                    <div class="text-[10px] uppercase tracking-widest text-eids-light font-bold mb-0.5">{{ $componentCode === 'ME_FITTING' ? 'M&E Weight' : 'Architectural Weight' }}</div>
                    <div class="text-3xl font-extrabold text-white">{{ number_format((float) $component['weightage'], 1) }}<span class="text-base text-white/70">%</span></div>
                </div>
            @else
                <div class="text-center bg-white/10 px-5 py-3 rounded-xl backdrop-blur-xs border border-white/10">
                    <div class="text-[10px] uppercase tracking-widest text-eids-light font-bold mb-0.5">Category</div>
                    <div class="text-lg font-extrabold text-white">External Works</div>
                </div>
            @endif
        </div>

        {{-- Calculated Overall Result Badge --}}
        <div class="mt-6 pt-5 border-t border-white/10 flex items-center justify-between">
            <span class="text-xs font-bold text-white/80 uppercase tracking-wider">Calculated Component Result</span>
            <span x-text="overall"
                  :class="overall === 'PASS' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'"
                  class="px-5 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-widest shadow-xs transition-colors"></span>
        </div>
    </div>

    <form method="POST" action="{{ $storeUrl }}"
          enctype="multipart/form-data" class="flex-1 flex flex-col">
        @csrf
        <input type="hidden" name="component_code" value="{{ $componentCode }}">

        {{-- Checklist Card — No. / Question / Method-Tool / Limit / Guide / Result --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden mb-6">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">checklist</span>
                    Inspection Checklist ({{ $items->count() }} Questions)
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-4 py-3 text-left w-10">No.</th>
                            <th class="px-4 py-3 text-left">Inspection Question</th>
                            <th class="px-4 py-3 text-left">Tool / Method (CIS 7:2021)</th>
                            <th class="px-4 py-3 text-center hidden sm:table-cell">Limit</th>
                            <th class="px-4 py-3 text-center">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($items as $i => $item)
                            <tr class="align-top">
                                <td class="px-4 py-4 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                                <td class="px-4 py-4 text-gray-900 font-semibold max-w-xs">{{ $item->question_text }}</td>
                                <td class="px-4 py-4 text-xs font-semibold text-eids-primary">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-eids-primary/5 text-eids-primary border border-eids-primary/10 rounded-lg">
                                        <span class="material-symbols-outlined text-xs">build</span>
                                        {{ $item->method_tool ?? 'Visual' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-600 text-xs text-center hidden sm:table-cell font-mono">{{ $item->tolerance_text ?? '-' }}</td>
                                <td class="px-4 py-4">
                                    @if($item->input_type === 'numeric_with_tolerance')
                                        <div class="flex items-center gap-2 justify-center">
                                            <input type="number" step="0.01" min="0"
                                                   name="answers[{{ $item->id }}]"
                                                   x-model="answers[{{ $item->id }}].value"
                                                   placeholder="mm"
                                                   class="w-24 min-h-[40px] px-2.5 py-2 border border-gray-200 rounded-lg text-sm text-center focus:outline-none focus:ring-2 focus:ring-eids-accent font-mono font-medium">
                                            <span :class="statusFor({{ $item->id }}) === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900' : 'bg-red-100 border-red-400 text-red-900'"
                                                  class="px-2.5 py-1 border rounded-lg text-[11px] font-extrabold" x-text="statusFor({{ $item->id }})"></span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 justify-center">
                                            <label :class="answers[{{ $item->id }}].result === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900 font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 font-semibold'"
                                                   class="min-h-[40px] px-3 py-2 border rounded-lg text-xs cursor-pointer transition flex items-center gap-1">
                                                <input type="radio" name="answers[{{ $item->id }}]" value="PASS" x-model="answers[{{ $item->id }}].result" class="sr-only">
                                                PASS
                                            </label>
                                            <label :class="answers[{{ $item->id }}].result === 'FAIL' ? 'bg-red-100 border-red-400 text-red-900 font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 font-semibold'"
                                                   class="min-h-[40px] px-3 py-2 border rounded-lg text-xs cursor-pointer transition flex items-center gap-1">
                                                <input type="radio" name="answers[{{ $item->id }}]" value="FAIL" x-model="answers[{{ $item->id }}].result" class="sr-only">
                                                FAIL
                                            </label>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 text-[11px] text-gray-400 border-t border-gray-100">Meets CIS 7:2021 standards. Results are recorded automatically.</div>
        </div>

        {{-- Documentation & Photo Evidence Card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-4 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">camera_alt</span>
                Documentation & Photo Evidence
            </h2>

            <div class="space-y-5">
                {{-- Photo Upload --}}
                <x-photo-uploader label="Upload Defect Photo Evidence (Optional)"
                                  :photos="$assessment?->getMedia('photos')" />

                {{-- Inspection Remarks --}}
                <div>
                    <label for="input_remarks" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Inspection Remarks / Defect Description
                    </label>
                    <textarea name="remarks" id="input_remarks" rows="3" placeholder="Describe defect location, root cause, or specific corrective action..."
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent resize-none font-medium">{{ $assessment?->remarks }}</textarea>
                </div>
            </div>
        </div>

        {{-- Primary Actions Bar --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md p-4 mt-auto flex items-center justify-between gap-3 flex-wrap">
            <a href="{{ $gridUrl }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">grid_on</span>
                Grid
            </a>

            <div class="flex items-center gap-2">
                @if($prevCode)
                    <a href="{{ $prevUrl }}"
                       class="min-h-[44px] px-4 py-2.5 text-xs font-bold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-1"
                       title="View previous without saving">
                        <span class="material-symbols-outlined text-base">chevron_left</span>
                        Prev
                    </a>
                @endif

                <button type="submit"
                        class="min-h-[44px] px-6 py-2.5 bg-eids-primary hover:bg-eids-dark text-white text-sm font-extrabold rounded-xl shadow-md transition flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-eids-accent">
                    <span class="material-symbols-outlined text-base">save</span>
                    @if($nextCode)
                        Save &amp; Next ({{ $nextCode }}) &rarr;
                    @else
                        Save &amp; Return to Grid
                    @endif
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
