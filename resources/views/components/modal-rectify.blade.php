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
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
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
         class="relative bg-white rounded-2xl sm:rounded-3xl shadow-2xl max-w-xl w-full p-5 sm:p-7 z-10 border border-gray-200 my-4 sm:my-8 max-h-[92vh] flex flex-col">

        {{-- Modal Header --}}
        <div class="flex items-start justify-between gap-3 pb-3.5 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 bg-eids-primary/10 text-eids-primary border border-eids-primary/20">
                    <span class="material-symbols-outlined text-2xl">verified</span>
                </div>
                <div>
                    <h3 id="rectify-modal-title" class="font-extrabold text-gray-900 text-base sm:text-lg leading-tight">
                        Submit Rectification Evidence
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5 font-medium">
                        Attach proof photos of the rectified defect for reinspection
                    </p>
                </div>
            </div>
            <button type="button" @click="closeModal()"
                    class="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition shrink-0 min-h-[40px] min-w-[40px] flex items-center justify-center"
                    aria-label="Close modal">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        {{-- Scrollable Form Content --}}
        <div class="overflow-y-auto flex-1 py-3 pr-1">

            {{-- Status Transition Confirmation Alert --}}
            <div class="p-3.5 bg-amber-50/80 border border-amber-200 rounded-2xl flex items-start gap-3 mb-4">
                <span class="material-symbols-outlined text-amber-700 text-xl shrink-0 mt-0.5">info</span>
                <div class="text-xs text-amber-900 leading-relaxed font-medium">
                    <span class="font-extrabold">Confirmation:</span> Submitting this will change the defect status from <strong>In Progress</strong> to <strong>Pending Verification</strong> so the site inspector can verify the completed work.
                </div>
            </div>

            {{-- Defect Context Reference --}}
            <div class="bg-gray-50 rounded-2xl p-3.5 sm:p-4 border border-gray-200 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                    <span class="text-xs font-bold text-gray-900" x-text="defect.component_name"></span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-600 bg-white px-2.5 py-0.5 rounded-lg border border-gray-200">
                        <span class="material-symbols-outlined text-xs text-eids-accent">location_on</span>
                        <span x-text="defect.location"></span>
                    </span>
                </div>
                <p class="text-xs text-gray-600 font-medium leading-relaxed" x-text="defect.defect_description"></p>

                {{-- Before Photos thumbnail preview --}}
                <template x-if="defect.before_photos && defect.before_photos.length > 0">
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-rose-700 flex items-center gap-1 mb-2">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                            Original Defect Photo (Before)
                        </div>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1">
                            <template x-for="(photo, idx) in defect.before_photos" :key="photo.id || idx">
                                <a :href="photo.url" target="_blank" rel="noopener"
                                   class="group relative shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-rose-300 bg-white shadow-2xs hover:border-rose-500 transition"
                                   title="Click to view full size">
                                    <img :src="photo.url" alt="Defect Before" class="w-full h-full object-cover group-hover:scale-105 transition">
                                    <span class="absolute bottom-1 right-1 bg-black/70 text-white text-[8px] px-1.5 py-0.5 rounded font-black tracking-tight">
                                        BEFORE
                                    </span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Form: Upload After Photos & Remarks --}}
            <form :id="'rectify-form-' + (defect.id || 'current')"
                  :action="action" method="POST" enctype="multipart/form-data" @submit="submitting = true" class="space-y-4">
                @csrf
                <input type="hidden" name="to" value="PENDING_VERIFICATION">

                {{-- Rectification Photo Evidence Picker --}}
                <div x-data="photoPicker({ max: 10, used: 0 })">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Proof Photos (After Rectification) <span class="text-rose-500">*</span>
                    </label>

                    {{-- Queued previews --}}
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-2" x-show="queued.length" x-cloak>
                        <template x-for="(item, index) in queued" :key="item.src">
                            <div class="relative rounded-xl overflow-hidden border border-emerald-300 bg-emerald-50/40">
                                <img :src="item.src" alt="Rectification proof" class="h-20 sm:h-24 w-full object-cover">
                                <span class="absolute bottom-1 left-1 bg-emerald-700 text-white text-[8px] font-black px-1.5 py-0.5 rounded">
                                    AFTER
                                </span>
                                <button type="button" @click="removeQueued(index, $refs.input)" title="Remove photo"
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
                            <div class="text-xs font-bold text-gray-800">Tap to take photo or choose files</div>
                            <div class="text-[11px] text-gray-500 mt-1">
                                Mobile camera or image files (Up to 10 photos &middot; resized automatically)
                            </div>
                            <div class="text-[11px] font-bold text-eids-accent mt-1"
                                 x-text="`${remaining} photo slot${remaining === 1 ? '' : 's'} remaining`"></div>
                        </div>
                    </div>

                    <p x-show="busy" x-cloak class="mt-1.5 text-[11px] text-gray-500 font-medium">Preparing photos&hellip;</p>
                    <p x-show="error" x-cloak x-text="error" class="mt-1.5 text-[11px] text-red-600 font-bold"></p>
                </div>

                {{-- Rectification Remarks / Notes --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Rectification Notes (Optional)
                    </label>
                    <textarea name="rectification_notes" rows="2"
                              placeholder="Describe the repair actions taken (e.g. Cracked tile replaced, re-grouted and surface cleaned)..."
                              class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-eids-accent bg-gray-50/50 focus:bg-white font-medium transition"
                              x-text="defect.rectification_notes || ''"></textarea>
                </div>
            </form>
        </div>

        {{-- Action Buttons --}}
        <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3 shrink-0">
            <button type="button" @click="closeModal()" :disabled="submitting"
                    class="min-h-[44px] px-5 py-2.5 text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                Cancel
            </button>
            <button type="submit"
                    :form="'rectify-form-' + (defect.id || 'current')"
                    :disabled="submitting"
                    class="min-h-[44px] px-6 py-2.5 text-xs font-bold text-white bg-eids-primary hover:bg-eids-dark rounded-xl shadow-xs transition flex items-center gap-2 disabled:opacity-50">
                <span x-show="!submitting" class="material-symbols-outlined text-base">send</span>
                <span x-show="submitting" class="material-symbols-outlined text-base animate-spin">refresh</span>
                <span x-text="submitting ? 'Submitting...' : 'Confirm &amp; Submit for Verification'"></span>
            </button>
        </div>

    </div>
</div>
