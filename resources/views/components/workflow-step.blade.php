@props([
    'step' => 1,
    'project' => null,
    'role' => null,
])

@php
    $steps = [
        1 => ['label' => 'Project Details', 'route' => 'projects.show'],
        2 => ['label' => 'Sample Setup',   'route' => 'projects.samples'],
        3 => ['label' => 'Components Grid','route' => 'projects.components'],
        4 => ['label' => 'Inspection',     'route' => 'projects.inspect'],
    ];
    $isManager = in_array($role, ['admin', 'lead_auditor']);

    $scoreReady = (bool) ($project && $project->inspection_progress >= 100);
    $ratingColorMap = [
        'GOOD'     => ['bg' => 'bg-emerald-500', 'text' => 'text-white',      'ink' => 'text-emerald-600'],
        'MODERATE' => ['bg' => 'bg-amber-400',   'text' => 'text-amber-950',  'ink' => 'text-amber-600'],
        'WEAK'     => ['bg' => 'bg-red-500',     'text' => 'text-white',      'ink' => 'text-red-600'],
    ];
    $ratingColor = $scoreReady ? ($ratingColorMap[$project->rating] ?? $ratingColorMap['WEAK']) : null;
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-3 sm:p-4 mb-6">
    <div class="flex items-center justify-between gap-1">

        {{-- Steps 1-4: inspection routing slip --}}
        <div class="flex items-stretch overflow-x-auto no-scrollbar flex-1 min-w-0">
            @foreach($steps as $num => $info)
                @php
                    $isCurrent   = ($num === (int)$step);
                    $isCompleted = ($num < (int)$step);
                    $isNotMyStep = $isManager && $num >= 3;
                    $needsSample = $num === 4 && $project && $project->samples->isEmpty();
                    $isDisabled  = !$isCurrent && (($num > (int)$step && !$project) || $isNotMyStep || $needsSample);

                    $routeParams = $project ? [$project] : [];
                    if ($num === 4 && $project && $project->samples->first()) {
                        $routeParams = [$project, $project->samples->first()];
                    }

                    $lockedLabel = $isNotMyStep ? 'Handled by Inspector' : ($needsSample ? 'Configure samples first' : $info['label']);
                    $code = str_pad($num, 2, '0', STR_PAD_LEFT);
                @endphp

                @if($num > 1)
                    {{-- Perforation divider --}}
                    <div class="w-px self-stretch my-1.5 border-l border-dashed border-gray-200 shrink-0" aria-hidden="true"></div>
                @endif

                @if($isDisabled || !$project)
                    <div class="flex items-center gap-2 px-3 py-2 shrink-0 select-none cursor-not-allowed" title="{{ $lockedLabel }}">
                        <span class="font-mono text-[11px] font-extrabold tabular-nums rounded px-1.5 py-0.5 border border-gray-200 text-gray-300 bg-white">
                            {{ $code }}
                        </span>
                        <span class="text-xs font-semibold text-gray-300 hidden sm:inline">{{ $lockedLabel }}</span>
                    </div>
                @else
                    <a href="{{ route($info['route'], $routeParams) }}"
                       class="flex items-center gap-2 px-3 py-2 shrink-0 min-h-[44px] rounded-lg transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-eids-accent"
                       title="{{ $info['label'] }}">
                        <span class="relative font-mono text-[11px] font-extrabold tabular-nums rounded px-1.5 py-0.5 border transition-colors
                            {{ $isCurrent
                                ? 'bg-eids-primary border-eids-primary text-white'
                                : ($isCompleted ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-white border-gray-200 text-gray-400') }}">
                            {{ $code }}
                            @if($isCompleted && !$isCurrent)
                                <span class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white shadow-2xs flex items-center justify-center -rotate-12">
                                    <svg viewBox="0 0 10 10" class="w-2.5 h-2.5" fill="none">
                                        <path d="M2 5.2L4 7.2L8 2.8" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            @endif
                        </span>
                        <span class="text-xs hidden sm:inline
                            {{ $isCurrent ? 'font-extrabold text-eids-primary' : ($isCompleted ? 'font-semibold text-gray-700' : 'font-medium text-gray-500') }}">
                            {{ $info['label'] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Step 5: G-IDS Score certification seal --}}
        <div class="shrink-0 pl-2"
             x-data="{
                 celebrate: false,
                 init() {
                     @if($scoreReady && $project)
                     const key = 'eids-score-celebrated-{{ $project->id }}';
                     if (!localStorage.getItem(key)) {
                         this.celebrate = true;
                         localStorage.setItem(key, '1');
                     }
                     @endif
                 }
             }">
            @if($scoreReady)
                <a href="{{ route('projects.score', [$project]) }}"
                   :class="celebrate ? 'animate-score-pop' : ''"
                   class="flex items-center gap-2 pl-2 pr-4 py-2 rounded-xl min-h-[44px] font-extrabold text-xs shadow-md transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $ratingColor['bg'] }} {{ $ratingColor['text'] }}">
                    <svg viewBox="0 0 40 40" class="w-7 h-7 shrink-0 {{ $ratingColor['ink'] }}">
                        <circle cx="20" cy="20" r="17" fill="none" stroke="white" stroke-opacity="0.85" stroke-width="1.5" stroke-dasharray="3 2.2" />
                        <circle cx="20" cy="20" r="12" fill="white" />
                        <path d="M13 20.5L17.5 25L27.5 14" stroke="currentColor" stroke-width="2.8" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ number_format($project->overall_score, 1) }} &middot; {{ $project->rating }}</span>
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            @else
                <div class="flex items-center gap-2 pl-2 pr-4 py-2 rounded-xl min-h-[44px] font-bold text-xs text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed"
                     title="Unlocks once inspection is complete">
                    <svg viewBox="0 0 40 40" class="w-7 h-7 shrink-0 text-gray-300">
                        <circle cx="20" cy="20" r="17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 2.2" />
                        <circle cx="20" cy="20" r="12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2 2" />
                    </svg>
                    <span class="hidden sm:inline">G-IDS Score</span>
                </div>
            @endif
        </div>
    </div>
</div>
