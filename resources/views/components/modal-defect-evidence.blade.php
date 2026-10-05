<div
    x-data="{
        open: false,
        defect: {},
        activeBeforeIdx: 0,
        activeAfterIdx: 0,
        openModal(detail) {
            this.defect = detail || {};
            this.activeBeforeIdx = 0;
            this.activeAfterIdx = 0;
            this.open = true;
        },
        closeModal() {
            this.open = false;
        }
    }"
    x-on:open-evidence-modal.window="openModal($event.detail)"
    @keydown.escape.window="closeModal()"
    x-show="open"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-labelledby="evidence-modal-title"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
>
    {{-- Backdrop --}}
    <div @click="closeModal()" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>

    {{-- Dialog Box --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-3xl shadow-2xl max-w-4xl w-full p-6 sm:p-8 z-10 border border-gray-100 my-8 max-h-[90vh] overflow-y-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-gray-100">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                    <span class="inline-flex items-center px-2.5 py-0.5 border rounded-full text-xs font-extrabold whitespace-nowrap"
                          :class="defect.status_class"
                          x-text="defect.status_label"></span>
                    <span class="inline-flex items-center px-2.5 py-0.5 border rounded-full text-xs font-extrabold whitespace-nowrap"
                          :class="defect.severity_class"
                          x-text="defect.severity_label"></span>
                    <span class="text-xs text-gray-500 font-semibold" x-text="defect.project_no"></span>
                </div>
                <h3 id="evidence-modal-title" class="font-extrabold text-gray-900 text-lg leading-tight" x-text="defect.component_name"></h3>
                <div class="text-xs text-gray-600 mt-1 flex items-center gap-1 font-medium">
                    <span class="material-symbols-outlined text-xs text-eids-accent">location_on</span>
                    <span x-text="defect.location"></span>
                </div>
            </div>
            <button type="button" @click="closeModal()"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        {{-- Side-by-side Before vs After Container --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- ── BEFORE: INSPECTOR DEFECT EVIDENCE ── --}}
            <div class="bg-rose-50/40 border border-rose-200/80 rounded-2xl p-4 flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-rose-200/60 mb-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 border border-rose-300">
                        <span class="material-symbols-outlined text-xs">warning</span>
                        BEFORE · Kerosakan Asal
                    </span>
                    <span class="text-[11px] font-bold text-rose-700"
                          x-text="defect.before_photos ? `${defect.before_photos.length} Foto` : '0 Foto'"></span>
                </div>

                {{-- Photo Viewport --}}
                <template x-if="defect.before_photos && defect.before_photos.length > 0">
                    <div>
                        <div class="relative rounded-xl overflow-hidden bg-black/5 border border-rose-200 aspect-4/3 flex items-center justify-center">
                            <img :src="defect.before_photos[activeBeforeIdx]?.url"
                                 alt="Before repair photo"
                                 class="w-full h-full object-contain">
                            <a :href="defect.before_photos[activeBeforeIdx]?.url" target="_blank" rel="noopener"
                               class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-black/60 hover:bg-black/80 text-white text-[10px] font-bold flex items-center gap-1 transition">
                                <span class="material-symbols-outlined text-xs">open_in_new</span>
                                Buka Foto
                            </a>
                        </div>

                        {{-- Gallery Thumbnails if more than 1 photo --}}
                        <template x-if="defect.before_photos.length > 1">
                            <div class="flex items-center gap-2 mt-2 overflow-x-auto pb-1">
                                <template x-for="(ph, idx) in defect.before_photos" :key="ph.id || idx">
                                    <button type="button" @click="activeBeforeIdx = idx"
                                            class="w-14 h-14 rounded-lg overflow-hidden border-2 shrink-0 transition"
                                            :class="activeBeforeIdx === idx ? 'border-rose-600 ring-2 ring-rose-300' : 'border-gray-200 opacity-60 hover:opacity-100'">
                                        <img :src="ph.url" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!defect.before_photos || defect.before_photos.length === 0">
                    <div class="py-12 px-4 rounded-xl border border-dashed border-rose-300 bg-white text-center flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-rose-400 text-3xl mb-1">no_photography</span>
                        <p class="text-xs font-bold text-gray-700">Tiada foto asal dilampirkan</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Defect ini direkod tanpa muat naik gambar.</p>
                    </div>
                </template>

                {{-- Description Box --}}
                <div class="mt-4 pt-3 border-t border-rose-200/60 flex-1">
                    <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Inspector:</div>
                    <div class="text-xs text-gray-700 leading-relaxed font-medium bg-white/70 p-3 rounded-xl border border-rose-100"
                         x-text="defect.defect_description || 'Tiada catatan tambahan.'"></div>
                </div>
            </div>

            {{-- ── AFTER: CONTRACTOR RECTIFICATION EVIDENCE ── --}}
            <div class="bg-emerald-50/40 border border-emerald-200/80 rounded-2xl p-4 flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-emerald-200/60 mb-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="material-symbols-outlined text-xs">task_alt</span>
                        AFTER · Bukti Baik Pulih
                    </span>
                    <span class="text-[11px] font-bold text-emerald-700"
                          x-text="defect.after_photos ? `${defect.after_photos.length} Foto` : '0 Foto'"></span>
                </div>

                {{-- Photo Viewport --}}
                <template x-if="defect.after_photos && defect.after_photos.length > 0">
                    <div>
                        <div class="relative rounded-xl overflow-hidden bg-black/5 border border-emerald-200 aspect-4/3 flex items-center justify-center">
                            <img :src="defect.after_photos[activeAfterIdx]?.url"
                                 alt="After repair photo"
                                 class="w-full h-full object-contain">
                            <a :href="defect.after_photos[activeAfterIdx]?.url" target="_blank" rel="noopener"
                               class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-black/60 hover:bg-black/80 text-white text-[10px] font-bold flex items-center gap-1 transition">
                                <span class="material-symbols-outlined text-xs">open_in_new</span>
                                Buka Foto
                            </a>
                        </div>

                        {{-- Gallery Thumbnails if more than 1 photo --}}
                        <template x-if="defect.after_photos.length > 1">
                            <div class="flex items-center gap-2 mt-2 overflow-x-auto pb-1">
                                <template x-for="(ph, idx) in defect.after_photos" :key="ph.id || idx">
                                    <button type="button" @click="activeAfterIdx = idx"
                                            class="w-14 h-14 rounded-lg overflow-hidden border-2 shrink-0 transition"
                                            :class="activeAfterIdx === idx ? 'border-emerald-600 ring-2 ring-emerald-300' : 'border-gray-200 opacity-60 hover:opacity-100'">
                                        <img :src="ph.url" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!defect.after_photos || defect.after_photos.length === 0">
                    <div class="py-10 px-4 rounded-xl border border-dashed border-emerald-300 bg-white text-center flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-emerald-400 text-3xl mb-1">hourglass_top</span>
                        <p class="text-xs font-bold text-gray-800">Menunggu Gambar Bukti Pembaikan</p>
                        <p class="text-[11px] text-gray-500 mt-1 max-w-xs">
                            Kontraktor belum memuat naik gambar bukti pembaikan bagi defect ini.
                        </p>
                        <template x-if="defect.can_rectify">
                            <button type="button"
                                    @click="closeModal(); $dispatch('open-rectify-modal', defect);"
                                    class="mt-3 px-4 py-2 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition shadow-xs flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">add_a_photo</span>
                                Upload Bukti Sekarang
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Remarks Box --}}
                <div class="mt-4 pt-3 border-t border-emerald-200/60 flex-1">
                    <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Pembaikan Kontraktor:</div>
                    <div class="text-xs text-gray-700 leading-relaxed font-medium bg-white/70 p-3 rounded-xl border border-emerald-100"
                         x-text="defect.rectification_notes || (defect.after_photos && defect.after_photos.length > 0 ? 'Kerja pembaikan telah disiapkan.' : 'Belum ada catatan pembaikan.')"></div>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <template x-if="defect.can_rectify">
                    <button type="button"
                            @click="closeModal(); $dispatch('open-rectify-modal', defect);"
                            class="min-h-[44px] px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs">
                        <span class="material-symbols-outlined text-base">fact_check</span>
                        Tandakan Selesai &amp; Hantar Bukti
                    </button>
                </template>
            </div>
            <button type="button" @click="closeModal()"
                    class="min-h-[44px] px-6 py-2.5 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                Tutup
            </button>
        </div>

    </div>
</div>
