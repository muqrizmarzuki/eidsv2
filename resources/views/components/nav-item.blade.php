@props([
    'route'  => '',
    'icon'   => 'circle',
    'label'  => '',
    'match'  => [],
])

@php
    $patterns = count($match) ? $match : [$route . '*'];
    $isActive = request()->routeIs(...$patterns);
    try {
        $href = Route::has($route) ? route($route) : '#';
    } catch (\Exception $e) {
        $href = '#';
    }
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
          {{ $isActive
              ? 'bg-white/15 text-white shadow-sm'
              : 'text-white/60 hover:bg-white/8 hover:text-white' }}">
    <span class="material-symbols-outlined text-lg {{ $isActive ? 'filled' : '' }}">{{ $icon }}</span>
    <span>{{ $label }}</span>
    @if($isActive)
        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-eids-accent"></span>
    @endif
</a>
