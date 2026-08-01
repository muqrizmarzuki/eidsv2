@props([
    'label'  => '',
    'value'  => '0',
    'icon'   => 'analytics',
    'color'  => 'emerald',  {{-- emerald | blue | amber | red | gray --}}
    'sub'    => null,
])

@php
    $colors = [
        'emerald' => ['bg' => 'bg-emerald-50',  'icon' => 'text-emerald-600',  'border' => 'border-emerald-100'],
        'blue'    => ['bg' => 'bg-blue-50',     'icon' => 'text-blue-600',     'border' => 'border-blue-100'],
        'amber'   => ['bg' => 'bg-amber-50',    'icon' => 'text-amber-600',    'border' => 'border-amber-100'],
        'red'     => ['bg' => 'bg-red-50',      'icon' => 'text-red-600',      'border' => 'border-red-100'],
        'gray'    => ['bg' => 'bg-gray-50',     'icon' => 'text-gray-500',     'border' => 'border-gray-100'],
    ];
    $c = $colors[$color] ?? $colors['emerald'];
@endphp

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-xl {{ $c['bg'] }} {{ $c['border'] }} border flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined filled {{ $c['icon'] }} text-xl">{{ $icon }}</span>
    </div>
    <div class="min-w-0">
        <div class="text-xs text-gray-400 uppercase tracking-wider font-medium truncate">{{ $label }}</div>
        <div class="text-2xl font-bold text-gray-900 leading-tight">{{ $value }}</div>
        @if($sub)
            <div class="text-xs text-gray-400 mt-0.5">{{ $sub }}</div>
        @endif
    </div>
</div>
