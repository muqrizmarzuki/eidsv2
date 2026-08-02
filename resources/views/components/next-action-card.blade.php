@props(['action'])

@if(!empty($action['text']))
    @if($action['actionable'])
        <div class="bg-eids-primary/10 border border-eids-primary/20 rounded-2xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center gap-4 shadow-xs">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="material-symbols-outlined text-eids-primary text-3xl shrink-0">{{ $action['icon'] }}</span>
                <div class="min-w-0">
                    <div class="text-[10px] uppercase tracking-widest font-extrabold text-eids-primary/70 mb-0.5">Your Next Step</div>
                    <div class="text-sm font-bold text-gray-900">{{ $action['text'] }}</div>
                </div>
            </div>
            <a href="{{ route($action['route'], $action['params'] ?? []) }}"
               class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
                {{ $action['button_label'] }}
                <span class="material-symbols-outlined text-lg">arrow_forward</span>
            </a>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-gray-400 text-2xl shrink-0">{{ $action['icon'] }}</span>
            <div>
                <div class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 mb-0.5">Waiting</div>
                <div class="text-sm font-bold text-gray-700">{{ $action['text'] }}</div>
            </div>
        </div>
    @endif
@endif
