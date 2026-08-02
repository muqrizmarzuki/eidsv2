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
        5 => ['label' => 'G-IDS Score',    'route' => 'projects.score', 'icon' => 'analytics'],
    ];
    $isManager = in_array($role, ['admin', 'lead_auditor']);
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4 mb-6">
    <div class="flex items-center justify-between overflow-x-auto gap-2 no-scrollbar py-1">
        @foreach($steps as $num => $info)
            @php
                $isCurrent      = ($num === (int)$step);
                $isCompleted    = ($num < (int)$step);
                $isNotMyStep    = $isManager && $num >= 3;
                $needsSample    = $num === 4 && $project && $project->samples->isEmpty();
                $isDisabled     = ($num > (int)$step && !$project) || $isNotMyStep || $needsSample;

                $routeParams = $project ? [$project] : [];
                if ($num === 4 && $project && $project->samples->first()) {
                    $routeParams = [$project, $project->samples->first()];
                }
            @endphp

            @if($isDisabled || !$project)
                <div class="flex items-center gap-2 text-gray-300 opacity-60 shrink-0 select-none">
                    <div class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center text-xs font-bold">
                        {{ $num }}
                    </div>
                    <span class="text-xs font-medium text-gray-400 hidden lg:inline">
                        {{ $isNotMyStep ? 'Handled by Inspector' : $info['label'] }}
                    </span>
                </div>
            @else
                <a href="{{ route($info['route'], $routeParams) }}"
                   class="flex items-center gap-2 shrink-0 group focus:outline-none focus:ring-2 focus:ring-eids-accent rounded-full min-h-[44px] px-2"
                   title="{{ $info['label'] }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200
                        {{ $isCurrent ? 'bg-eids-primary text-white shadow-md shadow-eids-primary/20 ring-4 ring-eids-primary/10 scale-105' : ($isCompleted ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200') }}">
                        @if($isCompleted)
                            <span class="material-symbols-outlined text-base font-extrabold text-emerald-700">check</span>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-xs transition-colors
                        {{ $isCurrent ? 'text-eids-primary font-bold' : ($isCompleted ? 'text-gray-800 font-semibold' : 'text-gray-500 group-hover:text-gray-800') }} hidden sm:inline">
                        {{ $info['label'] }}
                    </span>
                </a>
            @endif

            @if($num < count($steps))
                <div class="flex-1 min-w-3 h-0.5 {{ $num < (int)$step ? 'bg-emerald-400' : 'bg-gray-200' }} hidden sm:block"></div>
            @endif
        @endforeach
    </div>
</div>
