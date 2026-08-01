@extends('layouts.app')

@section('title', 'G-IDS Score')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-600">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-600 truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-700 font-medium">G-IDS Score</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('projects.components', $project) }}"
       class="flex items-center gap-1.5 px-3.5 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">
        <span class="material-symbols-outlined text-base">grid_view</span>
        Grid
    </a>
    <a href="{{ route('reports.show', $project) }}"
       class="flex items-center gap-1.5 px-4 py-2 bg-eids-primary text-white text-sm font-medium rounded-lg hover:bg-eids-dark transition shadow-xs">
        <span class="material-symbols-outlined text-base">description</span>
        Full PDF Report &rarr;
    </a>
@endsection

@section('content')

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="5" :project="$project" />

    {{-- Score Hero --}}
    @php
        $ratingColor = match($rating) {
            'GOOD'     => 'emerald',
            'MODERATE' => 'amber',
            default    => 'red',
        };
        $ratingMap = ['GOOD' => 'GOOD RATING', 'MODERATE' => 'MODERATE RATING', 'WEAK' => 'WEAK RATING'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5 mb-6">
        <div class="lg:col-span-1 bg-eids-primary rounded-2xl p-6 text-white flex flex-col items-center justify-center text-center shadow-md">
            <div class="text-xs text-white/60 uppercase tracking-widest font-bold mb-2">G-IDS Total Score</div>
            <div class="text-5xl font-extrabold">{{ number_format($totalScore, 2) }}</div>
            <div class="text-white/60 text-xs mt-1">out of {{ number_format(array_sum(array_column($components, 'weightage')) + $meScore + $extScore, 2) }} max</div>
            <div class="mt-4 px-4 py-1.5 rounded-full text-xs font-extrabold tracking-wider uppercase
                @if($rating === 'GOOD') bg-emerald-500 text-white
                @elseif($rating === 'MODERATE') bg-amber-400 text-amber-950
                @else bg-red-500 text-white @endif shadow-xs">
                {{ $ratingMap[$rating] ?? $rating }}
            </div>
        </div>

        <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col items-center justify-center text-center">
                <div class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-2">Architectural Subtotal</div>
                <div class="text-3xl font-extrabold text-eids-primary">{{ number_format($sArch, 2) }}</div>
                <div class="text-xs text-gray-400 font-medium mt-1">Max {{ array_sum(array_column($components, 'weightage')) }} pts</div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col items-center justify-center text-center">
                <div class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-2">M&amp;E Work Fixed</div>
                <div class="text-3xl font-extrabold text-eids-accent">{{ number_format($meScore, 2) }}</div>
                <div class="text-xs text-gray-400 font-medium mt-1">Fixed standard score</div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col items-center justify-center text-center">
                <div class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-2">External Work Fixed</div>
                <div class="text-3xl font-extrabold text-gray-700">{{ number_format($extScore, 2) }}</div>
                <div class="text-xs text-gray-400 font-medium mt-1">Fixed standard score</div>
            </div>
        </div>
    </div>

    {{-- Component breakdown --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-base">analytics</span>
                Architectural Component Pass Rates &amp; Weighted Score (S_comp)
            </h2>
            <span class="text-xs text-gray-400 font-mono">Formula: S_comp = (Pass / Total) &times; Weightage</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50/80 text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-left font-medium">Component</th>
                    <th class="px-4 py-3 text-center font-medium hidden sm:table-cell">Weightage</th>
                    <th class="px-4 py-3 text-center font-medium">Pass</th>
                    <th class="px-4 py-3 text-center font-medium">Fail</th>
                    <th class="px-4 py-3 text-center font-medium hidden md:table-cell">Pass Rate</th>
                    <th class="px-4 py-3 text-center font-medium">S_comp Score</th>
                    <th class="px-4 py-3 text-left font-medium hidden lg:table-cell">Pass Rate Bar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($rows as $code => $row)
                    @php
                        $barWidth = min(100, $row['passRate']);
                        $barCls = $row['passRate'] >= 80 ? 'bg-emerald-500' : ($row['passRate'] >= 60 ? 'bg-amber-400' : 'bg-red-400');
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-gray-800 text-xs font-mono">{{ $code }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $row['name'] }}</div>
                        </td>
                        <td class="px-4 py-3.5 text-center hidden sm:table-cell font-mono text-xs text-gray-500">{{ $row['weightage'] }}%</td>
                        <td class="px-4 py-3.5 text-center font-semibold text-emerald-700">{{ $row['pass'] }}</td>
                        <td class="px-4 py-3.5 text-center font-semibold text-red-700">{{ $row['fail'] }}</td>
                        <td class="px-4 py-3.5 text-center hidden md:table-cell font-semibold text-gray-700">
                            {{ number_format($row['passRate'], 1) }}%
                        </td>
                        <td class="px-4 py-3.5 text-center font-bold text-eids-primary">
                            {{ number_format($row['sComp'], 2) }}
                        </td>
                        <td class="px-4 py-3.5 hidden lg:table-cell">
                            <div class="w-32 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $barCls }} rounded-full transition-all" style="width: {{ $barWidth }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Call to action bottom bar --}}
    <div class="mt-6 flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
        <a href="{{ route('projects.components', $project) }}" class="min-h-[44px] px-4 py-2.5 text-xs font-bold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-1.5">
            &larr; Return to Inspection Grid
        </a>
        <a href="{{ route('reports.show', $project) }}" class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-bold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
            <span class="material-symbols-outlined text-base">description</span>
            Generate Official G-IDS Certificate PDF &rarr;
        </a>
    </div>

@endsection
