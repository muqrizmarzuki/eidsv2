@props([
    'step' => 1,
    'project' => null,
    'role' => null,
])

@php
    $steps = [
        1 => ['label' => 'Project Details', 'route' => 'projects.show', 'icon' => 'assignment'],
        2 => ['label' => 'Sample Setup',   'route' => 'projects.samples', 'icon' => 'view_module'],
        3 => ['label' => 'Components Grid','route' => 'projects.components', 'icon' => 'grid_on'],
        4 => ['label' => 'Inspection',     'route' => 'projects.inspect', 'icon' => 'rule'],
    ];
    $isManager = in_array($role, ['admin', 'lead_auditor']);

    $scoreReady = (bool) ($project && $project->inspection_progress >= 100);
    $ratingColorMap = [
        'GOOD'     => ['bg' => 'bg-emerald-500', 'text' => 'text-white'],
        'MODERATE' => ['bg' => 'bg-amber-400',   'text' => 'text-amber-950'],
        'WEAK'     => ['bg' => 'bg-red-500',     'text' => 'text-white'],
    ];
    $ratingColor = $scoreReady ? ($ratingColorMap[$project->rating] ?? $ratingColorMap['WEAK']) : null;
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-3 sm:p-4 mb-6">
    <div class="flex items-center justify-between gap-3">

        {{-- Steps 1-4: plain navigation tabs --}}
        <div class="flex items-center gap-1 overflow-x-auto no-scrollbar flex-1 min-w-0">
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
                @endphp

                @if($isDisabled || !$project)
                    <div class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-gray-300 shrink-0 select-none cursor-not-allowed"
                         title="{{ $lockedLabel }}">
                        <span class="material-symbols-outlined text-lg">{{ $info['icon'] }}</span>
                        <span class="text-xs font-semibold hidden sm:inline">{{ $lockedLabel }}</span>
                    </div>
                @else
                    <a href="{{ route($info['route'], $routeParams) }}"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-xl shrink-0 min-h-[44px] transition focus:outline-none focus:ring-2 focus:ring-eids-accent
                           {{ $isCurrent
                                ? 'text-eids-primary bg-eids-primary/5 border-b-2 border-eids-primary font-extrabold'
                                : ($isCompleted ? 'text-gray-700 hover:bg-gray-50 font-semibold' : 'text-gray-500 hover:bg-gray-50 font-medium') }}"
                       title="{{ $info['label'] }}">
                        @if($isCompleted && !$isCurrent)
                            <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
                        @else
                            <span class="material-symbols-outlined text-lg">{{ $info['icon'] }}</span>
                        @endif
                        <span class="text-xs hidden sm:inline">{{ $info['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Step 5: G-IDS Score destination button --}}
        <div class="shrink-0"
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
                   class="flex items-center gap-2 px-4 py-2.5 rounded-xl min-h-[44px] font-extrabold text-xs shadow-md transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-eids-accent {{ $ratingColor['bg'] }} {{ $ratingColor['text'] }}">
                    <span class="material-symbols-outlined text-lg">emoji_events</span>
                    <span>{{ number_format($project->overall_score, 1) }} &middot; {{ $project->rating }}</span>
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            @else
                <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl min-h-[44px] font-bold text-xs text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed"
                     title="Unlocks once inspection is complete">
                    <span class="material-symbols-outlined text-lg">lock</span>
                    <span class="hidden sm:inline">G-IDS Score</span>
                </div>
            @endif
        </div>
    </div>
</div>
