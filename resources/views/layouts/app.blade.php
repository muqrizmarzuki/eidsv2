<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-IDS — @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900" x-data="{ sidebarOpen: false }">

    <x-toast />

    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed lg:relative z-30 w-60 h-full bg-eids-primary flex flex-col transition-transform duration-200 ease-in-out shrink-0">

            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="w-9 h-9 bg-eids-accent rounded-xl flex items-center justify-center shrink-0 shadow-xs">
                    <span class="material-symbols-outlined filled text-white text-lg">domain</span>
                </div>
                <div class="min-w-0">
                    <div class="text-white font-extrabold text-lg leading-none tracking-tight">E-IDS</div>
                    <div class="text-white/50 text-[10px] tracking-widest uppercase leading-tight mt-1 truncate font-semibold">Defect Inspection</div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <x-nav-item route="dashboard"      icon="grid_view" label="Dashboard" />
                <x-nav-item route="projects.index" icon="domain"    label="Projects" :match="['projects.*']" />
                <x-nav-item route="defects.index"  icon="warning"   label="Defects Register" />
                <x-nav-item route="reports.index"  icon="description" label="G-IDS Reports" :match="['reports.*']" />

                @if(auth()->user()->isAdmin())
                    <div class="pt-5 pb-1.5 px-3">
                        <span class="text-white/30 text-[10px] uppercase tracking-widest font-bold">System Admin</span>
                    </div>
                    <x-nav-item route="users.index"    icon="group"    label="User Management" />
                    <x-nav-item route="settings.index" icon="settings" label="System Settings" />
                @endif
            </nav>

            {{-- User info --}}
            <div class="px-3 py-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/5 border border-white/5">
                    <div class="w-8 h-8 rounded-full bg-eids-accent/25 border border-eids-accent/40 flex items-center justify-center shrink-0">
                        <span class="text-eids-light text-xs font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-white text-xs font-bold truncate">{{ auth()->user()->name }}</div>
                        <div class="text-white/50 text-[10px] truncate font-medium">{{ auth()->user()->getRoleLabel() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign Out" aria-label="Sign Out" class="text-white/40 hover:text-white transition p-1 focus:outline-none">
                            <span class="material-symbols-outlined text-base">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Topbar --}}
            <header class="h-14 bg-white border-b border-gray-100 flex items-center gap-4 px-6 shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" aria-label="Toggle Sidebar" class="lg:hidden text-gray-400 hover:text-gray-600 focus:outline-none">
                    <span class="material-symbols-outlined">menu</span>
                </button>

                <div class="flex-1 min-w-0">
                    <h1 class="sr-only">@yield('title', 'Dashboard')</h1>
                    @hasSection('breadcrumb')
                        <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 text-sm text-gray-400">
                            @yield('breadcrumb')
                        </nav>
                    @else
                        <div class="font-bold text-gray-800 text-sm truncate">@yield('title', 'Dashboard')</div>
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @yield('topbar-actions')
                </div>
            </header>

            {{-- Content area --}}
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Global confirm modal --}}
    <x-modal-confirm id="delete-confirm"
        title="Delete Confirmation"
        message="This action cannot be undone. Are you sure you want to delete this record?"
        confirm="Yes, Delete"
        cancel="Cancel" />

</body>
</html>
