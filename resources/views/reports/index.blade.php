@extends('layouts.app')

@section('title', 'E-IDS Reports')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">E-IDS Reports</span>
@endsection

@section('content')
@php
    $ratingMap  = ['GOOD' => 'GOOD RATING', 'MODERATE' => 'MODERATE RATING', 'WEAK' => 'WEAK RATING'];
    $ratingCls  = [
        'GOOD'     => 'bg-emerald-100 text-emerald-900 border-emerald-300',
        'MODERATE' => 'bg-amber-100 text-amber-900 border-amber-300',
        'WEAK'     => 'bg-red-100 text-red-900 border-red-300',
    ];
    $typeMap = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
@endphp

@if($projects->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center px-6">
        <div class="w-16 h-16 rounded-2xl bg-eids-primary/5 border border-eids-primary/10 flex items-center justify-center mb-4 text-eids-primary">
            <span class="material-symbols-outlined text-4xl">description</span>
        </div>
        <div class="text-base text-gray-900 font-extrabold">No E-IDS reports available yet</div>
        <p class="text-xs text-gray-500 mt-1 max-w-sm">Reports appear here once a project has completed inspection and scoring. Start by registering and inspecting a project.</p>
        <a href="{{ route('projects.index') }}"
           class="mt-6 px-6 py-2.5 min-h-[44px] bg-eids-primary text-white text-xs font-extrabold rounded-xl hover:bg-eids-dark transition shadow-xs inline-flex items-center gap-2">
            Go to Projects <span class="material-symbols-outlined text-base">arrow_forward</span>
        </a>
    </div>
@else
    {{-- Rating Distribution Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 flex items-center justify-center shrink-0 text-emerald-800">
                <span class="material-symbols-outlined text-2xl">verified</span>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-900 leading-none">{{ $good }}</div>
                <div class="text-xs text-gray-500 font-bold mt-1">Good Rating (&ge; {{ number_format($ratingBaik, 0) }} pts)</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 border border-amber-300 flex items-center justify-center shrink-0 text-amber-800">
                <span class="material-symbols-outlined text-2xl">remove_moderator</span>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-900 leading-none">{{ $moderate }}</div>
                <div class="text-xs text-gray-500 font-bold mt-1">Moderate Rating (&ge; {{ number_format($ratingMod, 0) }} pts)</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 border border-red-300 flex items-center justify-center shrink-0 text-red-800">
                <span class="material-symbols-outlined text-2xl">dangerous</span>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-gray-900 leading-none">{{ $weak }}</div>
                <div class="text-xs text-gray-500 font-bold mt-1">Weak Rating (&lt; {{ number_format($ratingMod, 0) }} pts)</div>
            </div>
        </div>
    </div>

    {{-- Scored Projects List --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">description</span>
                <h2 class="font-extrabold text-gray-900 text-sm">Scored E-IDS Inspection Reports</h2>
            </div>
            <span class="text-xs text-gray-500 font-mono font-semibold">{{ $projects->total() }} report(s) total</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($projects as $project)
                @php
                    $score  = $project->overall_score;
                    $rating = $score >= $ratingBaik ? 'GOOD' : ($score >= $ratingMod ? 'MODERATE' : 'WEAK');
                    $barW   = min(100, round(($score / 100) * 100));
                    $barCls = $rating === 'GOOD' ? 'bg-emerald-500' : ($rating === 'MODERATE' ? 'bg-amber-400' : 'bg-red-500');
                @endphp
                <div class="flex items-center gap-4 px-6 py-5 hover:bg-gray-50/80 transition group flex-wrap sm:flex-nowrap">

                    {{-- Rating Score Circle Badge --}}
                    <div class="w-14 h-14 rounded-2xl flex flex-col items-center justify-center shrink-0 border shadow-2xs
                        @if($rating === 'GOOD') bg-emerald-100 border-emerald-300 text-emerald-900
                        @elseif($rating === 'MODERATE') bg-amber-100 border-amber-300 text-amber-950
                        @else bg-red-100 border-red-300 text-red-900 @endif">
                        <span class="text-lg font-extrabold leading-none">
                            {{ number_format($score, 0) }}
                        </span>
                        <span class="text-[9px] uppercase font-extrabold mt-0.5 opacity-80">pts</span>
                    </div>

                    {{-- Project info --}}
                    <div class="flex-1 min-w-48">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <a href="{{ route('reports.show', $project) }}" class="font-extrabold text-gray-900 text-base group-hover:text-eids-accent transition">
                                {{ $project->project_name }}
                            </a>
                            <span class="inline-flex px-3 py-0.5 border rounded-full text-[11px] font-extrabold {{ $ratingCls[$rating] }}">
                                {{ $ratingMap[$rating] }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-2 flex-wrap font-semibold">
                            <span class="font-mono text-gray-700">{{ $project->project_no }}</span>
                            <span>·</span>
                            <span>{{ $typeMap[$project->building_type] ?? $project->building_type }}</span>
                            @if($project->creator)
                                <span>·</span>
                                <span>Auditor: {{ $project->creator->name }}</span>
                            @endif
                        </div>
                        {{-- Score bar --}}
                        <div class="mt-2.5 h-2 w-full max-w-sm bg-gray-100 rounded-full overflow-hidden border border-gray-200/50">
                            <div class="h-full {{ $barCls }} rounded-full transition-all duration-300" style="width: {{ $barW }}%"></div>
                        </div>
                    </div>

                    {{-- Defects Count --}}
                    <div class="text-center shrink-0 hidden sm:block px-2">
                        <div class="text-base font-extrabold text-gray-900">{{ $project->defects_count }}</div>
                        <div class="text-xs text-gray-500 font-medium">defects logged</div>
                    </div>

                    {{-- Score Precise --}}
                    <div class="text-center shrink-0 hidden md:block px-2">
                        <div class="text-base font-extrabold text-eids-primary font-mono">{{ number_format($score, 2) }}</div>
                        <div class="text-xs text-gray-500 font-medium">E-IDS pts</div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('reports.show', $project) }}"
                           class="flex items-center gap-1.5 px-4 py-2.5 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition min-h-[44px] shadow-2xs">
                            <span class="material-symbols-outlined text-base">visibility</span>
                            View Report
                        </a>
                        <a href="{{ route('reports.pdf', $project) }}" data-no-loading
                           class="flex items-center gap-1.5 px-3.5 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-100 transition min-h-[44px]"
                           title="Download Formal PDF Certificate">
                            <span class="material-symbols-outlined text-base text-red-600">picture_as_pdf</span>
                            PDF
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">{{ $projects->links() }}</div>
    </div>
@endif
@endsection
