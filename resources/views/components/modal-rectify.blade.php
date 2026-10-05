<div
    x-data="{
        open: false,
        action: '',
        defect: {},
        submitting: false,
        openModal(detail) {
            this.defect = detail || {};
            this.action = detail.advance_url || '';
            this.open = true;
            this.submitting = false;
        },
        closeModal() {
            this.open = false;
            this.submitting = false;
        }
    }"
    x-on:open-rectify-modal.window="openModal($event.detail)"
    @keydown.escape.window="closeModal()"
    x-show="open"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-labelledby="rectify-modal-title"
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
         class="relative bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-8 z-10 border border-gray-100 my-8 max-h-[90vh] overflow-y-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-gray-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-blue-50 text-blue-600 border border-blue-100">
                    <span class="material-symbols-outlined text-2xl">verified</span>
                </div>
                <div>
                    <h3 id="rectify-modal-title" class="font-extrabold text-gray-900 text-lg leading-tight">
                        Hantar Bukti Pembaikan
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5 font-medium">
                        Submit Rectification Evidence &amp; Request Verification
                    </p>
                </div>
            </div>
            <button type="button" @click="closeModal()"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        {{-- Confirmation Question / Warning Alert --}}
        <div class="mt-4 p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-2xl flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-600 text-xl shrink-0 mt-0.5">info</span>
            <div class="text-xs text-blue-900 leading-relaxed font-medium">
                <span class="font-bold">Pengesahan Status:</span> Adakah anda pasti kerja pembaikan ini telah siap sepenuhnya? Status defect akan bertukar ke <strong>Pending Verification</strong> untuk disemak dan disahkan oleh inspector tapak.
            </div>
        </div>

        {{-- Defect Context Card (Component, Location & Before Photo) --}}
        <div class="mt-4 bg-gray-50 rounded-2xl p-4 border border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <span class="text-xs font-bold text-gray-900" x-text="defect.component_name"></span>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-600 bg-white px-2.5 py-1 rounded-lg border border-gray-200">
                    <span class="material-symbols-outlined text-xs text-eids-accent">location_on</span>
                    <span x-text="defect.location"></span>
                </span>
            </div>
            <p class="text-xs text-gray-600 font-medium leading-relaxed" x-text="defect.defect_description"></p>

            {{-- Before Photos thumbnail preview --}}
            <template x-if="defect.before_photos && defect.before_photos.length > 0">
                <div class="mt-3 pt-3 border-t border-gray-200/80">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1 mb-2">
                        <span class="material-symbols-outlined text-sm">photo_camera</span>
                        Gambar Defect Asal (Before - Inspector)
                    </div>
                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                        <template x-for="(photo, idx) in defect.before_photos" :key="photo.id || idx">
                            <a :href="photo.url" target="_blank" rel="noopener"
                               class="group relative shrink-0 w-20 h-20 rounded-xl overflow-hidden border border-rose-200 bg-white shadow-2xs hover:border-rose-400 transition"
                               title="Lihat saiz penuh">
                                <img :src="photo.url" alt="Defect Before" class="w-full h-full object-cover group-hover:scale-105 transition">
                                <span class="absolute bottom-1 right-1 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-bold">
                                    Before
                                </span>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Form: Upload After Photos & Remarks --}}
        <form :action="action" method="POST" enctype="multipart/form-data" @submit="submitting = true" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="to" value="PENDING_VERIFICATION">

            {{-- Rectification Photo Evidence Picker --}}
            <div x-data="photoPicker({ max: 10, used: 0 })">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                    Lampirkan Gambar Bukti Baik Pulih (After / Selepas) <span class="text-rose-500">*</span>
                </label>

                {{-- Queued previews --}}
                <div class="grid grid-cols-3 gap-2 mb-2" x-show="queued.length" x-cloak>
                    <template x-for="(item, index) in queued" :key="item.src">
                        <div class="relative rounded-xl overflow-hidden border border-eids-accent bg-emerald-50/30">
                            <img :src="item.src" alt="Rectification proof" class="h-24 w-full object-cover">
                            <span class="absolute bottom-1 left-1 bg-emerald-700 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">
                                After
                            </span>
                            <button type="button" @click="removeQueued(index, $refs.input)" title="Remove"
                                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Dropzone / File Picker Input --}}
                <div class="relative border-2 border-dashed border-gray-300 rounded-2xl hover:border-eids-accent transition bg-gray-50/50"
                     :class="queued.length ? 'border-eids-accent bg-emerald-50/20' : ''"
                     x-show="remaining > 0">
                    <input x-ref="input" type="file" name="rectification_photos[]" accept="image/*" multiple @change="pick($event)"
                           class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                    <div class="flex flex-col items-center justify-center py-6 text-center px-4">
                        <span class="material-symbols-outlined text-gray-400 text-3xl mb-1">add_a_photo</span>
                        <div class="text-xs font-bold text-gray-800">Ambil gambar atau pilih fail bukti pembaikan</div>
                        <div class="text-[11px] text-gray-500 mt-1">
                            Kamera telefon atau fail imej (Maksimum 10 keping, 3 MB setiap satu)
                        </div>
                        <div class="text-[11px] font-bold text-eids-accent mt-1"
                             x-text="`${remaining} baki slot foto`"></div>
                    </div>
                </div>

                <p x-show="busy" x-cloak class="mt-1.5 text-[11px] text-gray-500 font-medium">Sedang memproses gambar&hellip;</p>
                <p x-show="error" x-cloak x-text="error" class="mt-1.5 text-[11px] text-red-600 font-bold"></p>
            </div>

            {{-- Rectification Remarks / Notes --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                    Catatan Pembaikan (Rectification Remarks)
                </label>
                <textarea name="rectification_notes" rows="2"
                          placeholder="Terangkan secara ringkas tindakan pembaikan yang telah dijalankan (contoh: Jubin telah diganti baru, disapu lepa simen dan dibersihkan)..."
                          class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-gray-50/50 focus:bg-white font-medium transition"
                          x-text="defect.rectification_notes || ''"></textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <button type="button" @click="closeModal()" :disabled="submitting"
                        class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" :disabled="submitting"
                        class="min-h-[44px] px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition flex items-center gap-2 disabled:opacity-50">
                    <span x-show="!submitting" class="material-symbols-outlined text-base">send</span>
                    <span x-show="submitting" class="material-symbols-outlined text-base animate-spin">refresh</span>
                    <span x-text="submitting ? 'Sedang Menghantar...' : 'Sahkan &amp; Hantar untuk Pengesahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
