<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-IDS — @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-outlined.filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans antialiased text-gray-900 selection:bg-eids-accent selection:text-white" x-data="{ sidebarOpen: false }">

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
                    <x-nav-item route="reports.index"  icon="description" label="G-IDS Reports" :match="['reports.*']" />
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

</body>
</html>
