@props([
    'label'  => '',
    'value'  => '0',
    'icon'   => 'analytics',
    'color'  => 'emerald',  {{-- emerald | blue | amber | red | gray --}}
    'sub'    => null,
])

@php
    $colors = [
        'emerald' => ['bg' => 'bg-emerald-500/10', 'icon' => 'text-emerald-700', 'border' => 'border-emerald-200', 'ring' => 'group-hover:border-emerald-400'],
        'blue'    => ['bg' => 'bg-blue-500/10',    'icon' => 'text-blue-700',    'border' => 'border-blue-200',    'ring' => 'group-hover:border-blue-400'],
        'amber'   => ['bg' => 'bg-amber-500/10',   'icon' => 'text-amber-700',   'border' => 'border-amber-200',   'ring' => 'group-hover:border-amber-400'],
        'red'     => ['bg' => 'bg-red-500/10',     'icon' => 'text-red-700',     'border' => 'border-red-200',     'ring' => 'group-hover:border-red-400'],
        'gray'    => ['bg' => 'bg-gray-500/10',    'icon' => 'text-gray-700',    'border' => 'border-gray-200',    'ring' => 'group-hover:border-gray-400'],
    ];
    $c = $colors[$color] ?? $colors['emerald'];
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 flex items-center gap-4 group transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
    <div class="w-12 h-12 rounded-xl {{ $c['bg'] }} {{ $c['border'] }} border flex items-center justify-center shrink-0 transition-colors {{ $c['ring'] }}">
        <span class="material-symbols-outlined filled {{ $c['icon'] }} text-2xl">{{ $icon }}</span>
    </div>
    <div class="min-w-0 flex-1">
        <div class="text-xs text-gray-500 uppercase tracking-wider font-bold truncate mb-0.5">{{ $label }}</div>
        <div class="text-2xl lg:text-3xl font-extrabold text-gray-900 leading-none tracking-tight">{{ $value }}</div>
        @if($sub)
            <div class="text-xs text-gray-500 font-medium mt-1 truncate">{{ $sub }}</div>
        @endif
    </div>
</div>
