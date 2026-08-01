@props([
    'id'      => 'confirm-modal',
    'title'   => 'Confirm Action',
    'message' => 'Are you sure you want to proceed with this action?',
    'confirm' => 'Yes, Proceed',
    'cancel'  => 'Cancel',
    'danger'  => true,
])

<div
    x-data="{ open: false, formAction: '', formMethod: 'POST' }"
    x-on:open-confirm.window="
        if ($event.detail.id === '{{ $id }}') {
            formAction = $event.detail.action ?? '';
            formMethod = $event.detail.method ?? 'POST';
            open = true;
        }
    "
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    {{-- Backdrop --}}
    <div @click="open = false" class="absolute inset-0 bg-black/50 backdrop-blur-xs"></div>

    {{-- Dialog --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10 border border-gray-100">

        {{-- Icon --}}
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0
                        {{ $danger ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                <span class="material-symbols-outlined filled text-xl">
                    {{ $danger ? 'delete_forever' : 'warning' }}
                </span>
            </div>
            <div>
                <h3 id="{{ $id }}-title" class="font-bold text-gray-900 text-base leading-tight">{{ $title }}</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $message }}</p>
            </div>
        </div>

        {{-- Action form --}}
        <form :action="formAction" method="POST" class="flex justify-end gap-3 mt-6">
            @csrf
            <template x-if="formMethod !== 'POST'">
                <input type="hidden" name="_method" :value="formMethod">
            </template>

            <button type="button" @click="open = false"
                    class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-gray-400">
                {{ $cancel }}
            </button>
            <button type="submit"
                    class="min-h-[44px] px-6 py-2.5 text-xs font-bold text-white rounded-xl shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-400
                           {{ $danger ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700' }}">
                {{ $confirm }}
            </button>
        </form>
    </div>
</div>
