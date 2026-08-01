@extends('layouts.app')

@section('title', 'Reports')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">Reports</span>
@endsection

@section('content')
@php
    $ratingMap  = ['GOOD' => 'Good', 'MODERATE' => 'Moderate', 'WEAK' => 'Weak'];
    $ratingCls  = [
        'GOOD'     => 'bg-emerald-100 text-emerald-700',
        'MODERATE' => 'bg-amber-100 text-amber-700',
        'WEAK'     => 'bg-red-100 text-red-600',
    ];
    $typeMap = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
@endphp

@if($projects->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <span class="material-symbols-outlined text-5xl text-gray-200 mb-4">description</span>
        <div class="text-gray-500 font-medium">No reports available yet</div>
        <p class="text-xs text-gray-400 mt-1 max-w-xs">Reports appear here once a project has been inspected and scored. Start by completing an inspection.</p>
        <a href="{{ route('projects.index') }}"
           class="mt-5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition">
            Go to Projects
        </a>
    </div>
@else
    {{-- Stats row --}}
    @php
        $good     = $projects->filter(fn($p) => $p->overall_score >= $ratingBaik)->count();
        $moderate = $projects->filter(fn($p) => $p->overall_score >= $ratingMod && $p->overall_score < $ratingBaik)->count();
        $weak     = $projects->filter(fn($p) => $p->overall_score < $ratingMod)->count();
    @endphp
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-emerald-600 text-xl">verified</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $good }}</div>
                <div class="text-xs text-gray-400 mt-0.5">Good (&ge; {{ $ratingBaik }} pts)</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-amber-600 text-xl">remove_moderator</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $moderate }}</div>
                <div class="text-xs text-gray-400 mt-0.5">Moderate (&ge; {{ $ratingMod }} pts)</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-red-500 text-xl">dangerous</span>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-800">{{ $weak }}</div>
                <div class="text-xs text-gray-400 mt-0.5">Weak (&lt; {{ $ratingMod }} pts)</div>
            </div>
        </div>
    </div>

    {{-- Project report list --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center gap-2">
            <span class="material-symbols-outlined text-eids-accent text-base">description</span>
            <h2 class="font-semibold text-gray-800 text-sm">Scored Projects</h2>
            <span class="ml-auto text-xs text-gray-400">{{ $projects->count() }} report(s)</span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($projects as $project)
                @php
                    $score  = $project->overall_score;
                    $rating = $score >= $ratingBaik ? 'GOOD' : ($score >= $ratingMod ? 'MODERATE' : 'WEAK');
                    $barW   = min(100, round(($score / 100) * 100));
                    $barCls = $rating === 'GOOD' ? 'bg-emerald-500' : ($rating === 'MODERATE' ? 'bg-amber-400' : 'bg-red-400');
                @endphp
                <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50/60 transition group">

                    {{-- Rating badge --}}
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0
                        @if($rating === 'GOOD') bg-emerald-100
                        @elseif($rating === 'MODERATE') bg-amber-100
                        @else bg-red-100 @endif">
                        <span class="text-lg font-bold
                            @if($rating === 'GOOD') text-emerald-600
                            @elseif($rating === 'MODERATE') text-amber-600
                            @else text-red-500 @endif">
                            {{ number_format($score, 0) }}
                        </span>
                    </div>

                    {{-- Project info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-800 text-sm truncate">{{ $project->project_name }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $ratingCls[$rating] }}">
                                {{ $ratingMap[$rating] }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-2 flex-wrap">
                            <span class="font-mono">{{ $project->project_no }}</span>
                            <span>·</span>
                            <span>{{ $typeMap[$project->building_type] ?? $project->building_type }}</span>
                            @if($project->creator)
                                <span>·</span>
                                <span>{{ $project->creator->name }}</span>
                            @endif
                        </div>
                        {{-- Score bar --}}
                        <div class="mt-2 h-1 w-full max-w-xs bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $barCls }} rounded-full" style="width: {{ $barW }}%"></div>
                        </div>
                    </div>

                    {{-- Defects count --}}
                    <div class="text-center shrink-0 hidden sm:block">
                        <div class="text-sm font-bold text-gray-700">{{ $project->defects_count }}</div>
                        <div class="text-xs text-gray-400">defects</div>
                    </div>

                    {{-- Score precise --}}
                    <div class="text-center shrink-0 hidden md:block">
                        <div class="text-sm font-bold text-gray-700">{{ number_format($score, 2) }}</div>
                        <div class="text-xs text-gray-400">G-IDS pts</div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('reports.show', $project) }}"
                           class="flex items-center gap-1.5 px-3.5 py-2 bg-eids-primary text-white text-xs font-semibold rounded-lg hover:bg-eids-dark transition">
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                            View
                        </a>
                        <a href="{{ route('reports.pdf', $project) }}"
                           class="flex items-center gap-1.5 px-3 py-2 border border-gray-200 text-gray-500 text-xs rounded-lg hover:bg-gray-50 transition"
                           title="Download PDF">
                            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
