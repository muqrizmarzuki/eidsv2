@props([
    'step' => 1,
    'project' => null,
])

@php
    $steps = [
        1 => ['label' => 'Project Details', 'route' => 'projects.show', 'icon' => 'assignment'],
        2 => ['label' => 'Sample Setup',   'route' => 'projects.samples', 'icon' => 'view_module'],
        3 => ['label' => 'Components Grid','route' => 'projects.components', 'icon' => 'grid_on'],
        4 => ['label' => 'Inspection',     'route' => 'projects.inspect', 'icon' => 'rule'],
        5 => ['label' => 'G-IDS Score',    'route' => 'projects.score', 'icon' => 'analytics'],
    ];
@endphp

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
    <div class="flex items-center justify-between overflow-x-auto gap-2 no-scrollbar py-1">
        @foreach($steps as $num => $info)
            @php
                $isCurrent   = ($num === (int)$step);
                $isCompleted = ($num < (int)$step);
                $isDisabled  = ($num > (int)$step && !$project);
                
                $routeParams = $project ? [$project] : [];
                if ($num === 4 && $project && $project->samples->first()) {
                    $routeParams = [$project, $project->samples->first()];
                }
            @endphp

            @if($isDisabled || !$project)
                <div class="flex items-center gap-2 text-gray-300 opacity-60 shrink-0">
                    <div class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-xs font-semibold">
                        {{ $num }}
                    </div>
                    <span class="text-xs font-medium text-gray-400 hidden md:inline">{{ $info['label'] }}</span>
                </div>
            @else
                <a href="{{ route($info['route'], $routeParams) }}"
                   class="flex items-center gap-2 shrink-0 group focus:outline-none focus:ring-2 focus:ring-eids-accent rounded-full">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all
                        {{ $isCurrent ? 'bg-eids-primary text-white shadow-md shadow-eids-primary/20 scale-105' : ($isCompleted ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500 group-hover:bg-gray-200') }}">
                        @if($isCompleted)
                            <span class="material-symbols-outlined text-base">check</span>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-xs font-medium transition-colors
                        {{ $isCurrent ? 'text-eids-primary font-semibold' : ($isCompleted ? 'text-gray-700' : 'text-gray-400 group-hover:text-gray-600') }} hidden sm:inline">
                        {{ $info['label'] }}
                    </span>
                </a>
            @endif

            @if($num < count($steps))
                <div class="flex-1 min-w-4 h-0.5 {{ $num < (int)$step ? 'bg-emerald-200' : 'bg-gray-100' }} hidden sm:block"></div>
            @endif
        @endforeach
    </div>
</div>
