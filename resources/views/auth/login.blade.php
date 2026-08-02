@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="min-h-screen flex bg-gray-50">

    {{-- Left Panel --}}
    <div class="hidden lg:flex lg:w-5/12 bg-eids-primary flex-col justify-between p-12 relative overflow-hidden border-r border-white/10 shadow-2xl">
        {{-- Architectural Grid Pattern Overlay --}}
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <svg class="w-full h-full" viewBox="0 0 600 800" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="300" cy="400" r="300" stroke="white" stroke-width="1.5"/>
                <circle cx="300" cy="400" r="200" stroke="white" stroke-width="1"/>
                <circle cx="300" cy="400" r="100" stroke="white" stroke-width="1"/>
                <line x1="0" y1="400" x2="600" y2="400" stroke="white" stroke-width="1"/>
                <line x1="300" y1="0" x2="300" y2="800" stroke="white" stroke-width="1"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex items-center gap-3.5 mb-8">
                <div class="w-11 h-11 bg-eids-accent rounded-xl flex items-center justify-center shadow-md ring-2 ring-white/10">
                    <span class="material-symbols-outlined filled text-white text-2xl">domain</span>
                </div>
                <div>
                    <div class="text-white font-extrabold text-2xl leading-none tracking-tight">E-IDS <span class="text-eids-light text-sm font-semibold">v2</span></div>
                    <div class="text-eids-light/80 text-xs tracking-widest uppercase font-bold mt-1">Electronic Inspection Defect System</div>
                </div>
            </div>
        </div>

        <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center px-4">
            <div class="w-56 h-56 bg-white/5 rounded-3xl border border-white/10 backdrop-blur-xs flex items-center justify-center mb-8 shadow-inner">
                <span class="material-symbols-outlined text-eids-light" style="font-size: 110px; font-variation-settings: 'FILL' 1">apartment</span>
            </div>
            <h2 class="text-white font-extrabold text-2xl mb-3 tracking-tight">Building Quality Inspection System</h2>
            <p class="text-white/80 text-sm leading-relaxed max-w-sm font-medium">
                Professional digital ledger for residential building defect inspections, real-time sample matrix calculations, and G-IDS / CIS 7 scoring.
            </p>
        </div>

        <div class="relative z-10 text-white/60 text-xs font-semibold pt-6 border-t border-white/10 flex justify-between items-center">
            <div>Politeknik Merlimau Melaka · Department of Civil Engineering</div>
            <div class="font-mono text-eids-light">v2.4</div>
        </div>
    </div>

    {{-- Right Form Panel --}}
    <div class="flex-1 flex flex-col justify-center px-6 sm:px-12 lg:px-16 xl:px-24 bg-white">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-6 p-4 bg-emerald-50 border border-emerald-300 text-emerald-900 rounded-2xl text-sm font-extrabold flex items-center gap-2.5 shadow-2xs">
                <span class="material-symbols-outlined text-lg text-emerald-700">check_circle</span>
                {{ session('success') }}
            </div>
        @endif

        <div class="max-w-md w-full mx-auto">
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <div class="w-10 h-10 bg-eids-primary rounded-xl flex items-center justify-center shadow-xs">
                    <span class="material-symbols-outlined text-white text-lg">domain</span>
                </div>
                <div>
                    <span class="font-extrabold text-eids-primary text-xl leading-none block">E-IDS v2</span>
                    <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Defect Inspection</span>
                </div>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Inspector Portal Sign In</h1>
            <p class="text-gray-500 text-sm mb-8 font-medium">Enter your credentials to access site inspection projects and G-IDS scoring ledger.</p>

            <form method="POST" action="{{ route('login.submit') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-xl">mail</span>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="e.g. inspector@eids.my" required autofocus
                            class="w-full pl-12 pr-4 min-h-[48px] py-3 border rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-eids-accent transition
                                   {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50/50 hover:bg-white focus:bg-white' }}">
                    </div>
                    @error('email')
                        <p class="mt-2 text-xs text-red-600 font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="mb-6" x-data="{ show: false }">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-xl">lock</span>
                        <input :type="show ? 'text' : 'password'" name="password"
                            placeholder="Enter password" required
                            class="w-full pl-12 pr-12 min-h-[48px] py-3 border border-gray-200 rounded-xl text-sm font-medium bg-gray-50/50 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-eids-accent transition">
                        <button type="button" @click="show = !show"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined text-xl" x-text="show ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer font-medium">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-eids-accent focus:ring-eids-accent">
                        Keep me signed in
                    </label>
                    <span class="text-xs font-semibold text-gray-500">Contact admin for access</span>
                </div>

                <button type="submit" :disabled="loading"
                    class="w-full min-h-[48px] py-3.5 bg-eids-primary text-white font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center justify-center gap-2 shadow-md disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Authenticating...' : 'Sign In to Portal'"></span>
                    <span class="material-symbols-outlined text-xl" x-show="!loading">arrow_forward</span>
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-gray-500 font-medium">
                Protected by E-IDS v2 Enterprise Quality Control Protocol
            </p>
        </div>
    </div>
</div>
@endsection
