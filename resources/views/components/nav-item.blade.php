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
   class="flex items-center gap-3 px-3.5 py-2.5 min-h-[44px] rounded-xl text-sm font-semibold transition-all duration-150 relative group
          {{ $isActive
              ? 'bg-white/15 text-white shadow-xs font-bold border border-white/10'
              : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
    <span class="material-symbols-outlined text-xl shrink-0 transition-transform group-hover:scale-110 {{ $isActive ? 'filled text-eids-light' : 'text-white/60' }}">{{ $icon }}</span>
    <span class="truncate">{{ $label }}</span>
    @if($isActive)
        <span class="ml-auto w-2 h-2 rounded-full bg-eids-light shadow-xs animate-pulse"></span>
    @endif
</a>
