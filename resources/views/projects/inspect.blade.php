@extends('layouts.app')

@section('title', 'Inspect — ' . $componentCode)

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-28">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.components', $project) }}" class="hover:text-gray-600">Components</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">{{ $sample->location_name }} / {{ $componentCode }}</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto"
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

    {{-- Default-PASS warning for new assessments --}}
    @if(!$assessment)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-5 flex items-start gap-3 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined filled text-amber-700 text-lg">warning</span>
            </div>
            <div>
                <div class="text-sm font-bold text-amber-900">New Inspection Record</div>
                <div class="text-xs text-amber-700 mt-0.5 leading-relaxed">
                    Check values default to PASS. Inspect each criteria on-site and explicitly select <strong>FAIL</strong> wherever defects exist before saving.
                </div>
            </div>
        </div>
    @else
        <div class="bg-blue-50/80 border border-blue-200/80 rounded-2xl px-4 py-2.5 mb-5 flex items-center justify-between text-xs text-blue-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-blue-600">history</span>
                <span>Existing assessment loaded for <strong>{{ $componentCode }}</strong></span>
            </div>
            <span class="text-blue-600/80 font-medium">Last saved {{ $assessment->updated_at->diffForHumans() }}</span>
        </div>
    @endif

    {{-- Component & sample header --}}
    <div class="bg-eids-primary rounded-2xl p-6 mb-5 text-white shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="text-white/60 text-xs font-semibold uppercase tracking-wider mb-1">
                    {{ $project->project_name }} &nbsp;·&nbsp; Component {{ $componentPos }} of {{ $componentCount }}
                </div>
                <h1 class="font-bold text-xl text-white">{{ $component['name'] }} ({{ $componentCode }})</h1>
                <div class="text-white/70 text-sm mt-1 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">home_work</span>
                    {{ $sample->location_name }} &nbsp;·&nbsp; Sample #{{ $sample->sample_index }}
                </div>
            </div>
            <div class="text-center bg-white/10 px-4 py-2.5 rounded-xl backdrop-blur-xs">
                <div class="text-[10px] uppercase tracking-widest text-white/60 font-semibold mb-0.5">Weightage</div>
                <div class="text-3xl font-extrabold text-eids-light">{{ $component['weightage'] }}<span class="text-base text-white/50">%</span></div>
            </div>
        </div>

        {{-- Overall result badge --}}
        <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between">
            <span class="text-xs font-medium text-white/70">Calculated Overall Result</span>
            <span x-text="overall"
                  :class="overall === 'PASS' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'"
                  class="px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-wider shadow-xs transition-colors"></span>
        </div>
    </div>

    <form method="POST" action="{{ route('projects.inspect.store', [$project, $sample]) }}"
          enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="component_code" value="{{ $componentCode }}">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-eids-accent text-lg">checklist</span>
                    Inspection Criteria (5 Checks)
                </h2>
                <span class="text-xs text-gray-400">Min. 44px tap targets</span>
            </div>

            {{-- Checklist items --}}
            <div class="space-y-4 divide-y divide-gray-50">

                {{-- Finishing --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">1. Finishing</div>
                        <div class="text-xs text-gray-500 mt-0.5">Surface finishing quality & alignment</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label :class="finishing === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-800 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'"
                               class="min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl text-xs font-bold cursor-pointer transition flex items-center justify-center gap-1.5">
                            <input type="radio" name="finishing_status" value="PASS" x-model="finishing" class="sr-only">
                            <span class="material-symbols-outlined text-base">check_circle</span> PASS
                        </label>
                        <label :class="finishing === 'FAIL' ? 'bg-red-100 border-red-400 text-red-800 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'"
                               class="min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl text-xs font-bold cursor-pointer transition flex items-center justify-center gap-1.5">
                            <input type="radio" name="finishing_status" value="FAIL" x-model="finishing" class="sr-only">
                            <span class="material-symbols-outlined text-base">cancel</span> FAIL
                        </label>
                    </div>
                </div>

                {{-- Hollow --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">2. Hollow</div>
                        <div class="text-xs text-gray-500 mt-0.5">Hollow sound tapping test</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label :class="hollow === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-800 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'"
                               class="min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl text-xs font-bold cursor-pointer transition flex items-center justify-center gap-1.5">
                            <input type="radio" name="hollow_status" value="PASS" x-model="hollow" class="sr-only">
                            <span class="material-symbols-outlined text-base">check_circle</span> PASS
                        </label>
                        <label :class="hollow === 'FAIL' ? 'bg-red-100 border-red-400 text-red-800 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'"
                               class="min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl text-xs font-bold cursor-pointer transition flex items-center justify-center gap-1.5">
                            <input type="radio" name="hollow_status" value="FAIL" x-model="hollow" class="sr-only">
                            <span class="material-symbols-outlined text-base">cancel</span> FAIL
                        </label>
                    </div>
                </div>

                {{-- Levelling --}}
                <div class="pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">3. Levelling (mm)</div>
                            <div class="text-xs text-gray-500 mt-0.5">Max allowable tolerance: <strong>{{ $levellingMax }}mm</strong></div>
                        </div>
                        <span :class="levellingStatus === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-800' : 'bg-red-100 border-red-400 text-red-800'"
                              class="min-h-[36px] px-3.5 py-1 border rounded-lg text-xs font-extrabold transition flex items-center gap-1"
                              x-text="levellingStatus"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" name="levelling_mm" step="0.01" min="0" id="input_levelling_mm"
                               placeholder="Enter measured mm (leave blank = PASS)"
                               x-model="levelling"
                               class="flex-1 min-h-[44px] px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                        <span class="text-xs text-gray-500 font-semibold shrink-0">mm</span>
                    </div>
                    <input type="hidden" name="levelling_status" :value="levellingStatus">
                </div>

                {{-- Joint --}}
                <div class="pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">4. Joint / Gap (mm)</div>
                            <div class="text-xs text-gray-500 mt-0.5">Max allowable tolerance: <strong>{{ $jointMax }}mm</strong></div>
                        </div>
                        <span :class="jointStatus === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-800' : 'bg-red-100 border-red-400 text-red-800'"
                              class="min-h-[36px] px-3.5 py-1 border rounded-lg text-xs font-extrabold transition flex items-center gap-1"
                              x-text="jointStatus"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" name="joint_mm" step="0.01" min="0" id="input_joint_mm"
                               placeholder="Enter measured mm (leave blank = PASS)"
                               x-model="joint"
                               class="flex-1 min-h-[44px] px-3.5 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent">
                        <span class="text-xs text-gray-500 font-semibold shrink-0">mm</span>
                    </div>
                    <input type="hidden" name="joint_status" :value="jointStatus">
                </div>

                {{-- Crack --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">5. Crack</div>
                        <div class="text-xs text-gray-500 mt-0.5">Visible cracks, fractures, or structural splits</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label :class="crack === 'PASS' ? 'bg-emerald-100 border-emerald-400 text-emerald-800 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'"
                               class="min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl text-xs font-bold cursor-pointer transition flex items-center justify-center gap-1.5">
                            <input type="radio" name="crack_status" value="PASS" x-model="crack" class="sr-only">
                            <span class="material-symbols-outlined text-base">check_circle</span> PASS
                        </label>
                        <label :class="crack === 'FAIL' ? 'bg-red-100 border-red-400 text-red-800 shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100'"
                               class="min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl text-xs font-bold cursor-pointer transition flex items-center justify-center gap-1.5">
                            <input type="radio" name="crack_status" value="FAIL" x-model="crack" class="sr-only">
                            <span class="material-symbols-outlined text-base">cancel</span> FAIL
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documentation --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
            <h2 class="font-bold text-gray-900 mb-4 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">camera_alt</span>
                Documentation & Photo Evidence
            </h2>

            <div class="space-y-4">
                {{-- Photo upload --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                        Upload Photo Evidence (Optional)
                    </label>
                    <div class="relative border-2 border-dashed border-gray-200 rounded-2xl hover:border-eids-accent transition bg-gray-50/50"
                         :class="photoSrc ? 'border-eids-accent bg-emerald-50/20' : ''">
                        <input type="file" name="photo" accept="image/*" @change="handlePhoto"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <div x-show="!photoSrc" class="flex flex-col items-center justify-center py-8 text-center px-4">
                            <span class="material-symbols-outlined text-gray-400 text-4xl mb-2">add_a_photo</span>
                            <div class="text-xs font-semibold text-gray-700">Tap to take photo or select file</div>
                            <div class="text-[11px] text-gray-400 mt-1">Supports JPG, PNG up to 5MB</div>
                        </div>
                        <div x-show="photoSrc" x-cloak class="p-3">
                            <img :src="photoSrc" class="w-full rounded-xl object-cover max-h-56">
                        </div>
                    </div>
                    @if($assessment?->photo_path)
                        <div class="mt-2 text-xs text-gray-500 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-emerald-600">image</span>
                            <span>Existing photo: <strong class="text-gray-700">{{ basename($assessment->photo_path) }}</strong></span>
                        </div>
                    @endif
                </div>

                {{-- Remarks --}}
                <div>
                    <label for="input_remarks" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                        Inspection Remarks / Defect Description
                    </label>
                    <textarea name="remarks" id="input_remarks" rows="3" placeholder="Describe defect location, cause, or specific corrective action..."
                              class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent resize-none">{{ $assessment?->remarks }}</textarea>
                </div>
            </div>
        </div>

        {{-- Primary Actions Bar --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-lg p-4 sticky bottom-4 flex items-center justify-between gap-3 z-10 flex-wrap">
            <a href="{{ route('projects.components', $project) }}"
               class="min-h-[44px] px-4 py-2.5 text-xs font-bold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">grid_view</span>
                Grid
            </a>

            <div class="flex items-center gap-2">
                @if($prevCode)
                    <a href="{{ route('projects.inspect', [$project, $sample, 'component' => $prevCode]) }}"
                       class="min-h-[44px] px-3.5 py-2.5 text-xs font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-1"
                       title="View previous without saving">
                        <span class="material-symbols-outlined text-base">chevron_left</span>
                        Prev
                    </a>
                @endif

                <button type="submit"
                        class="min-h-[44px] px-6 py-2.5 bg-eids-primary hover:bg-eids-dark text-white text-sm font-bold rounded-xl shadow-md transition flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-eids-accent">
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
