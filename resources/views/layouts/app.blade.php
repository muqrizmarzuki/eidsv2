<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>E-IDS: @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pdfExport', () => ({
                loading: false,
                async download(url, filename) {
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!response.ok) throw new Error('PDF generation failed');

                        const blob = await response.blob();
                        const disposition = response.headers.get('Content-Disposition') || '';
                        const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
                        const name = match ? decodeURIComponent(match[1]) : (filename || 'report') + '.pdf';

                        const objectUrl = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = objectUrl;
                        link.download = name;
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        window.URL.revokeObjectURL(objectUrl);
                    } catch (e) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', msg: 'Failed to generate PDF. Please try again.' } }));
                    } finally {
                        this.loading = false;
                    }
                },
            }));

            /**
             * Photo evidence picker: previews what was chosen, shrinks it to fit
             * the 3 MB server cap before it ever leaves the phone, and deletes
             * saved photos one at a time without losing the rest of the form.
             */
            Alpine.data('photoPicker', (config = {}) => ({
                max: config.max ?? 3,
                used: config.used ?? 0,
                queued: [],
                busy: false,
                error: '',

                get remaining() {
                    return Math.max(0, this.max - this.used - this.queued.length);
                },

                async pick(event) {
                    const input = event.target;
                    const picked = Array.from(input.files || []);
                    if (!picked.length) return;

                    this.busy = true;
                    this.error = '';
                    try {
                        const room = this.remaining;
                        if (picked.length > room) {
                            this.error = room > 0
                                ? `Only ${room} more photo${room === 1 ? '' : 's'} can be added.`
                                : `A maximum of ${this.max} photos is allowed. Delete one first.`;
                        }

                        for (const file of picked.slice(0, room)) {
                            const shrunk = await this.downscale(file);
                            this.queued.push({ file: shrunk, src: URL.createObjectURL(shrunk) });
                        }
                        this.sync(input);
                    } finally {
                        this.busy = false;
                    }
                },

                sync(input) {
                    const bundle = new DataTransfer();
                    this.queued.forEach((item) => bundle.items.add(item.file));
                    input.files = bundle.files;
                },

                removeQueued(index, input) {
                    URL.revokeObjectURL(this.queued[index].src);
                    this.queued.splice(index, 1);
                    this.sync(input);
                    this.error = '';
                },

                /**
                 * A site photo off a phone is routinely 4-8 MB, which the 3 MB
                 * rule would reject mid-inspection. Resize to 1600px on the long
                 * edge and step the JPEG quality down until it fits.
                 */
                async downscale(file) {
                    const LIMIT = 3 * 1024 * 1024;
                    const MAX_EDGE = 1600;

                    if (!file.type.startsWith('image/')) return file;

                    let bitmap;
                    try {
                        bitmap = await createImageBitmap(file);
                    } catch (e) {
                        return file; // Unsupported format — let the server rule decide.
                    }

                    const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height));
                    if (scale === 1 && file.size <= LIMIT) {
                        bitmap.close?.();
                        return file;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(bitmap.width * scale);
                    canvas.height = Math.round(bitmap.height * scale);
                    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                    bitmap.close?.();

                    let blob = null;
                    for (const quality of [0.82, 0.7, 0.6, 0.5]) {
                        blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
                        if (blob && blob.size <= LIMIT) break;
                    }

                    if (!blob || blob.size >= file.size) return file;

                    return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });
                },

                async destroy(event) {
                    const button = event.currentTarget;
                    const id = button.dataset.deletePhoto;
                    if (!id || this.busy) return;
                    if (!window.confirm('Delete this photo? This cannot be undone.')) return;

                    this.busy = true;
                    try {
                        const response = await fetch(`/media/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        if (!response.ok) throw new Error('delete failed');

                        button.closest('[data-photo-tile]')?.remove();
                        this.used = Math.max(0, this.used - 1);
                        this.error = '';
                    } catch (e) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', msg: 'Could not delete the photo. Please try again.' } }));
                    } finally {
                        this.busy = false;
                    }
                },
            }));
        });
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans antialiased text-gray-900 selection:bg-eids-accent selection:text-white" x-data="{ sidebarOpen: false }">

    <x-page-loader />

    <x-toast />

    {{-- Mobile Overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/60 backdrop-blur-xs z-40 lg:hidden"></div>

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed lg:relative z-50 w-64 h-full bg-eids-primary flex flex-col transition-transform duration-200 ease-in-out shrink-0 border-r border-white/10 shadow-xl lg:shadow-none">

            {{-- Brand Header --}}
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="w-10 h-10 bg-eids-accent rounded-xl flex items-center justify-center shrink-0 shadow-md ring-2 ring-white/10">
                    <span class="material-symbols-outlined filled text-white text-xl">domain</span>
                </div>
                <div class="min-w-0">
                    <div class="text-white font-extrabold text-lg leading-none tracking-tight">E-IDS</div>
                    <div class="text-white/60 text-[10px] tracking-widest uppercase leading-tight mt-1 truncate font-semibold">Defect Inspection</div>
                </div>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                @if(auth()->user()->isContractor())
                    <x-nav-item route="defects.index"  icon="warning"   label="My Defects" />
                @else
                    <x-nav-item route="dashboard"      icon="grid_view" label="Dashboard" />
                    <x-nav-item route="projects.index" icon="domain"    label="Projects" :match="['projects.*']" />
                    <x-nav-item route="defects.index"  icon="warning"   label="Defects Register" />
                    <x-nav-item route="reports.index"  icon="description" label="E-IDS Reports" :match="['reports.*']" />
                @endif

                @if(auth()->user()->isAdmin())
                    <div class="pt-6 pb-2 px-3">
                        <span class="text-white/40 text-[10px] uppercase tracking-widest font-extrabold">System Administration</span>
                    </div>
                    <x-nav-item route="users.index"    icon="group"    label="User Management" />
                    <x-nav-item route="settings.index" icon="settings" label="System Settings" />
                @endif
            </nav>

            {{-- User Profile Footer Card --}}
            <div class="p-3 border-t border-white/10 bg-eids-dark/40">
                <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white/5 border border-white/10">
                    <div class="w-9 h-9 rounded-full bg-eids-accent/30 border border-eids-accent/50 flex items-center justify-center shrink-0 text-white font-bold text-sm shadow-xs">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-white text-xs font-bold truncate">{{ auth()->user()->name }}</div>
                        <div class="text-eids-light text-[10px] truncate font-medium flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-eids-accent"></span>
                            {{ auth()->user()->getRoleLabel() }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign Out" aria-label="Sign Out" class="text-white/50 hover:text-white hover:bg-white/10 p-1.5 rounded-lg transition focus:outline-none">
                            <span class="material-symbols-outlined text-base">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content Canvas --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-gray-50">

            {{-- Topbar --}}
            <header class="h-16 bg-white border-b border-gray-200 flex items-center gap-4 px-6 shrink-0 z-10 shadow-xs">
                <button @click="sidebarOpen = !sidebarOpen" aria-label="Toggle Sidebar" class="lg:hidden p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition focus:outline-none">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="flex-1 min-w-0">
                    <h1 class="sr-only">@yield('title', 'Dashboard')</h1>
                    @hasSection('breadcrumb')
                        <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-xs font-medium text-gray-400 overflow-x-auto no-scrollbar">
                            @yield('breadcrumb')
                        </nav>
                    @else
                        <div class="font-extrabold text-gray-900 text-base truncate">@yield('title', 'Dashboard')</div>
                    @endif
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    @yield('topbar-actions')
                </div>
            </header>

            {{-- Content Area --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Global Confirmation Modal --}}
    <x-modal-confirm id="delete-confirm"
        title="Confirm Deletion"
        message="This action cannot be undone. Are you sure you want to permanently delete this record?"
        confirm="Yes, Delete"
        cancel="Cancel" />

    {{-- Global Notify Modal — reused for contractor/inspector notify actions --}}
    <x-modal-notify id="notify-contractor"
        title="Notify Contractor?"
        message="Open defects will be flagged to the contractor for correction."
        confirm="Yes, Notify Contractor"
        cancel="Cancel"
        icon="campaign" />

    <x-modal-notify id="notify-inspector"
        title="Notify Inspector?"
        message="This will notify the inspector that this defect is ready for reinspection."
        confirm="Yes, Notify Inspector"
        cancel="Cancel"
        icon="fact_check" />

    <x-modal-notify id="sign-off-confirm"
        title="Sign Off Project?"
        message="This marks the project as Completed and unlocks the official E-IDS certificate."
        confirm="Yes, Sign Off"
        cancel="Cancel"
        icon="verified" />

    <x-modal-notify id="confirm-status-change"
        title="Confirm"
        message="Are you sure you want to change this defect's status?"
        confirm="Yes, Confirm"
        cancel="Cancel"
        icon="task_alt" />

</body>
</html>
