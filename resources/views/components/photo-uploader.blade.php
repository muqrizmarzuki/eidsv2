@props([
    'photos' => null,
    'label'  => 'Photo Evidence (Optional)',
    'max'    => 10,
])

@php
    /** @var \Illuminate\Support\Collection $photos */
    $photos = $photos ?? collect();
@endphp

{{-- Saved photos are rendered server-side so they show without JS; Alpine only
     handles previews of newly picked files and the one-at-a-time delete. --}}
<div x-data="photoPicker({ max: {{ (int) $max }}, used: {{ $photos->count() }} })">
    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">{{ $label }}</label>

    @if($photos->isNotEmpty())
        <div class="grid grid-cols-3 gap-2 mb-3">
            @foreach($photos as $media)
                <div data-photo-tile class="relative rounded-xl overflow-hidden border border-gray-200 bg-gray-50">
                    <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" title="View full size">
                        <img src="{{ $media->getUrl() }}" alt="Attached photo" class="h-24 w-full object-cover">
                    </a>
                    <button type="button" data-delete-photo="{{ $media->id }}" @click="destroy($event)"
                            title="Delete this photo"
                            class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-3 gap-2" x-show="queued.length" x-cloak>
        <template x-for="(item, index) in queued" :key="item.src">
            <div class="relative rounded-xl overflow-hidden border border-eids-accent bg-emerald-50/30">
                <img :src="item.src" alt="" class="h-24 w-full object-cover">
                <button type="button" @click="removeQueued(index, $refs.input)" title="Remove"
                        class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>
        </template>
    </div>

    <div class="relative border-2 border-dashed border-gray-300 rounded-2xl hover:border-eids-accent transition bg-gray-50/50 mt-2"
         :class="queued.length ? 'border-eids-accent bg-emerald-50/30' : ''"
         x-show="remaining > 0">
        <input x-ref="input" type="file" name="photos[]" accept="image/*" multiple @change="pick($event)"
               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
        <div class="flex flex-col items-center justify-center py-7 text-center px-4">
            <span class="material-symbols-outlined text-gray-400 text-3xl mb-1">add_a_photo</span>
            <div class="text-xs font-bold text-gray-800">Tap to take photos or select files</div>
            <div class="text-[11px] text-gray-500 mt-1">
                Up to {{ $max }} photos, 3 MB each &middot; larger photos are resized automatically
            </div>
            <div class="text-[11px] font-bold text-eids-accent mt-1"
                 x-text="`${remaining} slot${remaining === 1 ? '' : 's'} left`"></div>
        </div>
    </div>

    <div x-show="remaining === 0" x-cloak class="mt-2 text-[11px] text-gray-500 font-medium">
        Maximum of {{ $max }} photos reached. Delete one to add another.
    </div>

    <p x-show="busy" x-cloak class="mt-2 text-[11px] text-gray-500 font-medium">Preparing photos&hellip;</p>
    <p x-show="error" x-cloak x-text="error" class="mt-2 text-[11px] text-red-600 font-bold"></p>

    @error('photos')
        <p class="mt-2 text-[11px] text-red-600 font-bold">{{ $message }}</p>
    @enderror
    @foreach($errors->get('photos.*') as $messages)
        @foreach($messages as $message)
            <p class="mt-1 text-[11px] text-red-600 font-bold">{{ $message }}</p>
        @endforeach
    @endforeach
</div>
