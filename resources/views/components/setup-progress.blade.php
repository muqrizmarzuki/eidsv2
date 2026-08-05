@props([
    'step' => 1,
])

@php
    $steps = [1 => 'Project Details', 2 => 'Sample Setup'];
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4 mb-6 flex items-center gap-3">
    @foreach($steps as $num => $label)
        @php $isCurrent = $num === (int) $step; $isDone = $num < (int) $step; @endphp
        @if($num > 1)
            <div class="flex-1 h-px {{ $isDone || $isCurrent ? 'bg-eids-accent/40' : 'bg-gray-200' }}"></div>
        @endif
        <div class="flex items-center gap-2 shrink-0">
            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-extrabold border
                {{ $isCurrent ? 'bg-eids-primary border-eids-primary text-white' : ($isDone ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-white border-gray-200 text-gray-400') }}">
                {{ $num }}
            </span>
            <span class="text-xs {{ $isCurrent ? 'font-extrabold text-eids-primary' : ($isDone ? 'font-semibold text-gray-700' : 'font-medium text-gray-400') }}">
                Step {{ $num }} of 2: {{ $label }}
            </span>
        </div>
    @endforeach
</div>
