<div
    x-data="{
        toasts: [],
        add(type, msg) {
            const id = Date.now();
            this.toasts.push({ id, type, msg });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
    }"
    x-on:toast.window="add($event.detail.type, $event.detail.msg)"
    class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
>
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 translate-x-4"
             class="pointer-events-auto flex items-center gap-3 bg-white border border-emerald-200 shadow-lg rounded-xl px-4 py-3">
            <span class="material-symbols-outlined filled text-emerald-500 text-lg shrink-0">check_circle</span>
            <p class="text-sm text-gray-700 flex-1">{{ session('success') }}</p>
            <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition shrink-0">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="pointer-events-auto flex items-center gap-3 bg-white border border-red-200 shadow-lg rounded-xl px-4 py-3">
            <span class="material-symbols-outlined filled text-red-500 text-lg shrink-0">error</span>
            <p class="text-sm text-gray-700 flex-1">{{ session('error') }}</p>
            <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition shrink-0">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    @if($errors->any() && !session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="pointer-events-auto flex items-start gap-3 bg-white border border-amber-200 shadow-lg rounded-xl px-4 py-3">
            <span class="material-symbols-outlined filled text-amber-500 text-lg shrink-0 mt-0.5">warning</span>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-700">Please check the form:</p>
                <ul class="mt-1 text-xs text-gray-500 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition shrink-0">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    @endif

    {{-- Dynamic toasts via JS --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             class="pointer-events-auto flex items-center gap-3 bg-white shadow-lg rounded-xl px-4 py-3"
             :class="toast.type === 'success' ? 'border border-emerald-200' : 'border border-red-200'">
            <span class="material-symbols-outlined filled text-lg shrink-0"
                  :class="toast.type === 'success' ? 'text-emerald-500' : 'text-red-500'"
                  x-text="toast.type === 'success' ? 'check_circle' : 'error'"></span>
            <p class="text-sm text-gray-700 flex-1" x-text="toast.msg"></p>
            <button @click="remove(toast.id)" class="text-gray-300 hover:text-gray-500 transition shrink-0">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    </template>
</div>
