@extends('layouts.app')

@section('title', 'Inspect — ' . $componentCode)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-28">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.components', $project) }}" class="hover:text-gray-800 transition">Grid</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">{{ $sample->location_name }} / {{ $componentCode }}</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto"
     x-data="{
         finishing:  '{{ $assessment?->finishing_status ?? 'PASS' }}',
         hollow:     '{{ $assessment?->hollow_status    ?? 'PASS' }}',
         levelling:  '{{ $assessment?->levelling_mm     ?? '' }}',
         joint:      '{{ $assessment?->joint_mm         ?? '' }}',
         crack:      '{{ $assessment?->crack_status     ?? 'PASS' }}',
         levellingMax: {{ $levellingMax }},
         jointMax:     {{ $jointMax }},
         get levellingStatus() {
             const v = parseFloat(this.levelling);
             if (isNaN(v) || this.levelling === '') return 'PASS';
             return v <= this.levellingMax ? 'PASS' : 'FAIL';
         },
         get jointStatus() {
             const v = parseFloat(this.joint);
             if (isNaN(v) || this.joint === '') return 'PASS';
             return v <= this.jointMax ? 'PASS' : 'FAIL';
         },
         get overall() {
             return [this.finishing, this.hollow, this.levellingStatus, this.jointStatus, this.crack].includes('FAIL') ? 'FAIL' : 'PASS';
         },
         photoSrc: null,
         handlePhoto(e) {
             const f = e.target.files[0];
             if (f) this.photoSrc = URL.createObjectURL(f);
         }
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
                    {{ $sample->location_name }} &nbsp;·&nbsp; Sample Unit #{{ $sample->sample_index }}
                </div>
            </div>
            <div class="text-center bg-white/10 px-5 py-3 rounded-xl backdrop-blur-xs border border-white/10">
                <div class="text-[10px] uppercase tracking-widest text-eids-light font-bold mb-0.5">Architectural Weight</div>
                <div class="text-3xl font-extrabold text-white">{{ $component['weightage'] }}<span class="text-base text-white/70">%</span></div>
            </div>
        </div>

        {{-- Calculated Overall Result Badge --}}
        <div class="mt-6 pt-5 border-t border-white/10 flex items-center justify-between">
            <span class="text-xs font-bold text-white/80 uppercase tracking-wider">Calculated Component Result</span>
            <span x-text="overall"
                  :class="overall === 'PASS' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'"
                  class="px-5 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-widest shadow-xs transition-colors"></span>
        </div>
    </div>

    <form method="POST" action="{{ route('projects.inspect.store', [$project, $sample]) }}"
          enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="component_code" value="{{ $componentCode }}">

        {{-- 5 Checklist Items Card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">checklist</span>
                    Inspection Criteria Checklist (5 Checks)
                </h2>
                <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">44px Touch Target</span>
            </div>

            <div class="space-y-6">

                {{-- 1. Finishing --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
                    <div>
                        <div class="text-sm font-extrabold text-gray-900">1. Finishing</div>
                        <div class="text-xs text-gray-500 mt-0.5 font-medium">Surface finishing quality, alignment & plastering uniform</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label :class="finishing === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900 shadow-2xs font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 font-semibold'"
                               class="min-h-[44px] min-w-[100px] px-4 py-2.5 border rounded-xl text-xs cursor-pointer transition flex items-center justify-center gap-2">
                            <input type="radio" name="finishing_status" value="PASS" x-model="finishing" class="sr-only">
                            <span class="material-symbols-outlined text-base">check_circle</span> PASS
                        </label>
                        <label :class="finishing === 'FAIL' ? 'bg-red-100 border-red-400 text-red-900 shadow-2xs font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 font-semibold'"
                               class="min-h-[44px] min-w-[100px] px-4 py-2.5 border rounded-xl text-xs cursor-pointer transition flex items-center justify-center gap-2">
                            <input type="radio" name="finishing_status" value="FAIL" x-model="finishing" class="sr-only">
                            <span class="material-symbols-outlined text-base">cancel</span> FAIL
                        </label>
                    </div>
                </div>

                {{-- 2. Hollow --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-gray-100">
                    <div>
                        <div class="text-sm font-extrabold text-gray-900">2. Hollow</div>
                        <div class="text-xs text-gray-500 mt-0.5 font-medium">Tapping test for hollow sound underneath plastering or tiles</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label :class="hollow === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900 shadow-2xs font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 font-semibold'"
                               class="min-h-[44px] min-w-[100px] px-4 py-2.5 border rounded-xl text-xs cursor-pointer transition flex items-center justify-center gap-2">
                            <input type="radio" name="hollow_status" value="PASS" x-model="hollow" class="sr-only">
                            <span class="material-symbols-outlined text-base">check_circle</span> PASS
                        </label>
                        <label :class="hollow === 'FAIL' ? 'bg-red-100 border-red-400 text-red-900 shadow-2xs font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 font-semibold'"
                               class="min-h-[44px] min-w-[100px] px-4 py-2.5 border rounded-xl text-xs cursor-pointer transition flex items-center justify-center gap-2">
                            <input type="radio" name="hollow_status" value="FAIL" x-model="hollow" class="sr-only">
                            <span class="material-symbols-outlined text-base">cancel</span> FAIL
                        </label>
                    </div>
                </div>

                {{-- 3. Levelling --}}
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="text-sm font-extrabold text-gray-900">3. Levelling (mm)</div>
                            <div class="text-xs text-gray-500 mt-0.5 font-medium">Max allowable tolerance: <strong class="text-gray-900 font-bold">{{ $levellingMax }}mm</strong></div>
                        </div>
                        <span :class="levellingStatus === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900' : 'bg-red-100 border-red-400 text-red-900'"
                              class="min-h-[36px] px-4 py-1 border rounded-lg text-xs font-extrabold transition flex items-center gap-1"
                              x-text="levellingStatus"></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" name="levelling_mm" step="0.01" min="0" id="input_levelling_mm"
                               placeholder="Enter measured value (leave blank = PASS)"
                               x-model="levelling"
                               class="flex-1 min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        <span class="text-xs text-gray-600 font-bold shrink-0">mm</span>
                    </div>
                    <input type="hidden" name="levelling_status" :value="levellingStatus">
                </div>

                {{-- 4. Joint --}}
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="text-sm font-extrabold text-gray-900">4. Joint / Gap (mm)</div>
                            <div class="text-xs text-gray-500 mt-0.5 font-medium">Max allowable tolerance: <strong class="text-gray-900 font-bold">{{ $jointMax }}mm</strong></div>
                        </div>
                        <span :class="jointStatus === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900' : 'bg-red-100 border-red-400 text-red-900'"
                              class="min-h-[36px] px-4 py-1 border rounded-lg text-xs font-extrabold transition flex items-center gap-1"
                              x-text="jointStatus"></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" name="joint_mm" step="0.01" min="0" id="input_joint_mm"
                               placeholder="Enter measured value (leave blank = PASS)"
                               x-model="joint"
                               class="flex-1 min-h-[44px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent font-medium">
                        <span class="text-xs text-gray-600 font-bold shrink-0">mm</span>
                    </div>
                    <input type="hidden" name="joint_status" :value="jointStatus">
                </div>

                {{-- 5. Crack --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-gray-100">
                    <div>
                        <div class="text-sm font-extrabold text-gray-900">5. Crack</div>
                        <div class="text-xs text-gray-500 mt-0.5 font-medium">Visible cracks, hairline fractures, or structural splits</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label :class="crack === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-900 shadow-2xs font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 font-semibold'"
                               class="min-h-[44px] min-w-[100px] px-4 py-2.5 border rounded-xl text-xs cursor-pointer transition flex items-center justify-center gap-2">
                            <input type="radio" name="crack_status" value="PASS" x-model="crack" class="sr-only">
                            <span class="material-symbols-outlined text-base">check_circle</span> PASS
                        </label>
                        <label :class="crack === 'FAIL' ? 'bg-red-100 border-red-400 text-red-900 shadow-2xs font-extrabold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 font-semibold'"
                               class="min-h-[44px] min-w-[100px] px-4 py-2.5 border rounded-xl text-xs cursor-pointer transition flex items-center justify-center gap-2">
                            <input type="radio" name="crack_status" value="FAIL" x-model="crack" class="sr-only">
                            <span class="material-symbols-outlined text-base">cancel</span> FAIL
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documentation & Photo Evidence Card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 mb-6">
            <h2 class="font-extrabold text-gray-900 mb-4 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">camera_alt</span>
                Documentation & Photo Evidence
            </h2>

            <div class="space-y-5">
                {{-- Photo Upload --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Upload Defect Photo Evidence (Optional)
                    </label>
                    <div class="relative border-2 border-dashed border-gray-300 rounded-2xl hover:border-eids-accent transition bg-gray-50/50"
                         :class="photoSrc ? 'border-eids-accent bg-emerald-50/30' : ''">
                        <input type="file" name="photo" accept="image/*" @change="handlePhoto"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <div x-show="!photoSrc" class="flex flex-col items-center justify-center py-8 text-center px-4">
                            <span class="material-symbols-outlined text-gray-400 text-4xl mb-2">add_a_photo</span>
                            <div class="text-xs font-bold text-gray-800">Tap to take photo or select file</div>
                            <div class="text-[11px] text-gray-500 mt-1">Supports JPG, PNG up to 5MB</div>
                        </div>
                        <div x-show="photoSrc" x-cloak class="p-3">
                            <img :src="photoSrc" class="w-full rounded-xl object-cover max-h-56">
                        </div>
                    </div>
                    @if($assessment?->photo_url)
                        <div class="mt-2 text-xs text-gray-600 flex items-center gap-2 font-medium">
                            <span class="material-symbols-outlined text-base text-emerald-700">image</span>
                            <span>Existing photo attached: <strong class="text-gray-900">View Photo</strong></span>
                        </div>
                    @endif
                </div>

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
        <div class="bg-white rounded-2xl border border-gray-200 shadow-md p-4 flex items-center justify-between gap-3 flex-wrap">
            <a href="{{ route('projects.components', $project) }}"
               class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">grid_on</span>
                Grid
            </a>

            <div class="flex items-center gap-2">
                @if($prevCode)
                    <a href="{{ route('projects.inspect', [$project, $sample, 'component' => $prevCode]) }}"
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
