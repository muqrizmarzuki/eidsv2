@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex">

    {{-- Left panel --}}
    <div class="hidden lg:flex lg:w-5/12 bg-eids-primary flex-col justify-between p-10 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 600 800" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="300" cy="400" r="300" stroke="white" stroke-width="1"/>
                <circle cx="300" cy="400" r="200" stroke="white" stroke-width="1"/>
                <circle cx="300" cy="400" r="100" stroke="white" stroke-width="1"/>
                <line x1="0" y1="400" x2="600" y2="400" stroke="white" stroke-width="0.5"/>
                <line x1="300" y1="0" x2="300" y2="800" stroke="white" stroke-width="0.5"/>
            </svg>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-eids-accent rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-xl">domain</span>
                </div>
                <div>
                    <div class="text-white font-bold text-2xl leading-none">E-IDS</div>
                    <div class="text-eids-muted text-xs tracking-widest uppercase mt-0.5">Electronic Inspection Defect System</div>
                </div>
            </div>
        </div>
        <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center px-6">
            <div class="w-64 h-64 bg-white/5 rounded-2xl border border-white/10 flex items-center justify-center mb-8">
                <span class="material-symbols-outlined text-eids-light" style="font-size: 120px; font-variation-settings: 'FILL' 1">apartment</span>
            </div>
            <h2 class="text-white font-semibold text-xl mb-2">Building Quality Inspection System</h2>
            <p class="text-eids-muted text-sm leading-relaxed max-w-xs">
                Digital platform for building defect inspection and recording according to G-IDS / CIS 7 standards.
            </p>
        </div>
        <div class="relative z-10 text-eids-muted text-xs">
            <div>Politeknik Merlimau Melaka</div>
            <div class="mt-1">Department of Civil Engineering · Version 2.4</div>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="flex-1 flex flex-col justify-center px-8 sm:px-12 lg:px-16 xl:px-24 bg-white">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-base">check_circle</span>
                {{ session('success') }}
            </div>
        @endif

        <div class="max-w-md w-full mx-auto">
            <div class="lg:hidden flex items-center gap-2 mb-8">
                <div class="w-8 h-8 bg-eids-primary rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-sm">domain</span>
                </div>
                <span class="font-bold text-eids-primary text-lg">E-IDS</span>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-1">Welcome to E-IDS</h1>
            <p class="text-gray-500 text-sm mb-8">Sign in to your account to start inspections.</p>

            <form method="POST" action="{{ route('login.submit') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Email</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">mail</span>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="Enter your email" required autofocus
                            class="w-full pl-10 pr-4 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-eids-accent focus:border-transparent transition
                                   {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 hover:bg-white' }}">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="mb-5" x-data="{ show: false }">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400 text-lg">lock</span>
                        <input :type="show ? 'text' : 'password'" name="password"
                            placeholder="Enter your password" required
                            class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 hover:bg-white focus:outline-none focus:ring-2 focus:ring-eids-accent focus:border-transparent transition">
                        <button type="button" @click="show = !show"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined text-lg" x-text="show ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-eids-accent focus:ring-eids-accent">
                        Remember me
                    </label>
                    <span class="text-sm text-gray-400">Forgot password? Contact admin.</span>
                </div>

                <button type="submit" :disabled="loading"
                    class="w-full py-3.5 bg-eids-primary text-white font-semibold rounded-xl hover:bg-eids-dark transition flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Signing in...' : 'Sign In'"></span>
                    <span class="material-symbols-outlined text-lg" x-show="!loading">arrow_forward</span>
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-gray-400">
                Need access? <span class="font-semibold text-eids-primary">Contact Administrator</span>
            </p>
        </div>
    </div>
</div>
@endsection
