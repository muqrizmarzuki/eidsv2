<div
    x-data="{
        open: false,
        defect: {},
        viewMode: 'split',
        activeBeforeIdx: 0,
        activeAfterIdx: 0,
        openModal(detail) {
            this.defect = detail || {};
            this.activeBeforeIdx = 0;
            this.activeAfterIdx = 0;
            const hasBefore = (this.defect.before_photos && this.defect.before_photos.length > 0);
            const hasAfter = (this.defect.after_photos && this.defect.after_photos.length > 0);

            if (window.innerWidth < 768) {
                this.viewMode = hasAfter ? 'after' : 'before';
            } else {
                this.viewMode = 'split';
            }
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
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
>
    {{-- Backdrop --}}
    <div @click="closeModal()" class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>

    {{-- Dialog Container --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-2xl sm:rounded-3xl shadow-2xl max-w-4xl w-full p-4 sm:p-6 z-10 border border-gray-200 my-4 sm:my-8 max-h-[92vh] flex flex-col">

        {{-- Modal Header --}}
        <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 shrink-0">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 border rounded-full text-xs font-bold whitespace-nowrap"
                          :class="defect.status_class"
                          x-text="defect.status_label"></span>
                    <span class="inline-flex items-center px-2.5 py-0.5 border rounded-full text-xs font-bold whitespace-nowrap"
                          :class="defect.severity_class"
                          x-text="defect.severity_label"></span>
                    <span class="text-xs text-gray-500 font-semibold" x-text="defect.project_no"></span>
                </div>
                <h3 id="evidence-modal-title" class="font-extrabold text-gray-900 text-base sm:text-lg leading-tight truncate" x-text="defect.component_name"></h3>
                <div class="text-xs text-gray-600 mt-0.5 flex items-center gap-1 font-medium truncate">
                    <span class="material-symbols-outlined text-xs text-eids-accent">location_on</span>
                    <span x-text="defect.location"></span>
                </div>
            </div>
            <button type="button" @click="closeModal()"
                    class="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition shrink-0 min-h-[40px] min-w-[40px] flex items-center justify-center"
                    aria-label="Close modal">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        {{-- Responsive View Toggle / Tabs --}}
        <div class="pt-3 pb-1 flex items-center justify-between flex-wrap gap-2 shrink-0">
            <div class="inline-flex p-1 bg-gray-100 rounded-xl text-xs font-bold w-full sm:w-auto">
                {{-- Split View (Desktop Only) --}}
                <button type="button"
                        @click="viewMode = 'split'"
                        class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition"
                        :class="viewMode === 'split' ? 'bg-white text-gray-900 shadow-2xs font-extrabold' : 'text-gray-600 hover:text-gray-900'">
                    <span class="material-symbols-outlined text-sm">view_column</span>
                    Side-by-Side
                </button>
                {{-- Before Tab --}}
                <button type="button"
                        @click="viewMode = 'before'"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-lg transition"
                        :class="viewMode === 'before' ? 'bg-white text-rose-700 shadow-2xs font-extrabold' : 'text-gray-600 hover:text-gray-900'">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Before (Defect)
                    <span class="text-[10px] opacity-75" x-text="`(${defect.before_photos?.length || 0})`"></span>
                </button>
                {{-- After Tab --}}
                <button type="button"
                        @click="viewMode = 'after'"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-lg transition"
                        :class="viewMode === 'after' ? 'bg-white text-emerald-700 shadow-2xs font-extrabold' : 'text-gray-600 hover:text-gray-900'">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    After (Rectified)
                    <span class="text-[10px] opacity-75" x-text="`(${defect.after_photos?.length || 0})`"></span>
                </button>
            </div>
            <div class="text-[11px] text-gray-500 hidden sm:block font-medium">
                Click any photo to view full size
            </div>
        </div>

        {{-- Modal Body: Scrollable Content --}}
        <div class="overflow-y-auto flex-1 py-3 pr-1">
            <div class="grid gap-4"
                 :class="viewMode === 'split' ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 max-w-2xl mx-auto'">

                {{-- ── BEFORE PANEL ── --}}
                <div x-show="viewMode === 'split' || viewMode === 'before'"
                     class="bg-rose-50/40 border border-rose-200/80 rounded-2xl p-3.5 sm:p-4 flex flex-col">
                    <div class="flex items-center justify-between pb-2.5 border-b border-rose-200/60 mb-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                            <span class="material-symbols-outlined text-xs">warning</span>
                            BEFORE · Defect Evidence
                        </span>
                        <span class="text-xs font-semibold text-rose-700"
                              x-text="defect.before_photos?.length ? `${defect.before_photos.length} Photo${defect.before_photos.length > 1 ? 's' : ''}` : 'No photos'"></span>
                    </div>

                    {{-- Photo Container --}}
                    <template x-if="defect.before_photos && defect.before_photos.length > 0">
                        <div>
                            <div class="relative rounded-xl overflow-hidden bg-gray-900 border border-rose-200 h-56 sm:h-72 flex items-center justify-center">
                                <img :src="defect.before_photos[activeBeforeIdx]?.url"
                                     alt="Defect before photo"
                                     class="w-full h-full object-contain">
                                <a :href="defect.before_photos[activeBeforeIdx]?.url" target="_blank" rel="noopener"
                                   class="absolute top-2 right-2 px-2.5 py-1 rounded-lg bg-black/70 hover:bg-black text-white text-[11px] font-bold flex items-center gap-1 transition"
                                   title="Open full size in new tab">
                                    <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    Full Size
                                </a>
                            </div>

                            {{-- Gallery Thumbnails --}}
                            <template x-if="defect.before_photos.length > 1">
                                <div class="flex items-center gap-2 mt-2.5 overflow-x-auto pb-1">
                                    <template x-for="(ph, idx) in defect.before_photos" :key="ph.id || idx">
                                        <button type="button" @click="activeBeforeIdx = idx"
                                                class="w-12 h-12 rounded-lg overflow-hidden border-2 shrink-0 transition"
                                                :class="activeBeforeIdx === idx ? 'border-rose-600 ring-2 ring-rose-300' : 'border-gray-200 opacity-60 hover:opacity-100'">
                                            <img :src="ph.url" class="w-full h-full object-cover">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!defect.before_photos || defect.before_photos.length === 0">
                        <div class="py-10 px-4 rounded-xl border border-dashed border-rose-300 bg-white text-center flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-rose-400 text-3xl mb-1">no_photography</span>
                            <p class="text-xs font-bold text-gray-700">No defect photo attached</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">This defect was logged without photo evidence.</p>
                        </div>
                    </template>

                    {{-- Inspector Remarks --}}
                    <div class="mt-3.5 pt-3 border-t border-rose-200/60 flex-1">
                        <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Inspector Description:</div>
                        <div class="text-xs text-gray-800 leading-relaxed font-medium bg-white/90 p-3 rounded-xl border border-rose-100"
                             x-text="defect.defect_description || 'No description provided.'"></div>
                    </div>
                </div>

                {{-- ── AFTER PANEL ── --}}
                <div x-show="viewMode === 'split' || viewMode === 'after'"
                     class="bg-emerald-50/40 border border-emerald-200/80 rounded-2xl p-3.5 sm:p-4 flex flex-col">
                    <div class="flex items-center justify-between pb-2.5 border-b border-emerald-200/60 mb-3">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <span class="material-symbols-outlined text-xs">verified</span>
                            AFTER · Rectified Proof
                        </span>
                        <span class="text-xs font-semibold text-emerald-700"
                              x-text="defect.after_photos?.length ? `${defect.after_photos.length} Photo${defect.after_photos.length > 1 ? 's' : ''}` : 'Pending'"></span>
                    </div>

                    {{-- Photo Container --}}
                    <template x-if="defect.after_photos && defect.after_photos.length > 0">
                        <div>
                            <div class="relative rounded-xl overflow-hidden bg-gray-900 border border-emerald-200 h-56 sm:h-72 flex items-center justify-center">
                                <img :src="defect.after_photos[activeAfterIdx]?.url"
                                     alt="Rectified after photo"
                                     class="w-full h-full object-contain">
                                <a :href="defect.after_photos[activeAfterIdx]?.url" target="_blank" rel="noopener"
                                   class="absolute top-2 right-2 px-2.5 py-1 rounded-lg bg-black/70 hover:bg-black text-white text-[11px] font-bold flex items-center gap-1 transition"
                                   title="Open full size in new tab">
                                    <span class="material-symbols-outlined text-xs">open_in_new</span>
                                    Full Size
                                </a>
                            </div>

                            {{-- Gallery Thumbnails --}}
                            <template x-if="defect.after_photos.length > 1">
                                <div class="flex items-center gap-2 mt-2.5 overflow-x-auto pb-1">
                                    <template x-for="(ph, idx) in defect.after_photos" :key="ph.id || idx">
                                        <button type="button" @click="activeAfterIdx = idx"
                                                class="w-12 h-12 rounded-lg overflow-hidden border-2 shrink-0 transition"
                                                :class="activeAfterIdx === idx ? 'border-emerald-600 ring-2 ring-emerald-300' : 'border-gray-200 opacity-60 hover:opacity-100'">
                                            <img :src="ph.url" class="w-full h-full object-cover">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!defect.after_photos || defect.after_photos.length === 0">
                        <div class="py-8 px-4 rounded-xl border border-dashed border-emerald-300 bg-white text-center flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-500 text-3xl mb-1">hourglass_top</span>
                            <p class="text-xs font-bold text-gray-800">Awaiting Rectification Proof</p>
                            <p class="text-[11px] text-gray-500 mt-1 max-w-xs">
                                The contractor has not submitted rectification photos for this defect yet.
                            </p>
                            <template x-if="defect.can_rectify">
                                <button type="button"
                                        @click="closeModal(); $dispatch('open-rectify-modal', defect);"
                                        class="mt-3 px-4 py-2 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition shadow-xs flex items-center gap-1.5 min-h-[40px]">
                                    <span class="material-symbols-outlined text-sm">add_a_photo</span>
                                    Upload Proof Photos Now
                                </button>
                            </template>
                        </div>
                    </template>

                    {{-- Contractor Remarks --}}
                    <div class="mt-3.5 pt-3 border-t border-emerald-200/60 flex-1">
                        <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">Contractor Rectification Notes:</div>
                        <div class="text-xs text-gray-800 leading-relaxed font-medium bg-white/90 p-3 rounded-xl border border-emerald-100"
                             x-text="defect.rectification_notes || (defect.after_photos && defect.after_photos.length > 0 ? 'Rectification completed.' : 'No notes submitted yet.')"></div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="pt-3 border-t border-gray-100 flex items-center justify-between flex-wrap gap-2 shrink-0">
            <div>
                <template x-if="defect.can_rectify">
                    <button type="button"
                            @click="closeModal(); $dispatch('open-rectify-modal', defect);"
                            class="min-h-[44px] px-5 py-2.5 bg-eids-primary hover:bg-eids-dark text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shadow-xs">
                        <span class="material-symbols-outlined text-base">fact_check</span>
                        Mark Settled &amp; Submit Proof
                    </button>
                </template>
            </div>
            <button type="button" @click="closeModal()"
                    class="min-h-[44px] px-6 py-2.5 text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition ml-auto">
                Close
            </button>
        </div>

    </div>
</div>
