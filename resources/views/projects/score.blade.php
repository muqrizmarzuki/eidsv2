@extends('layouts.app')

@section('title', 'G-IDS Score Breakdown')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Dashboard</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <a href="{{ route('projects.show', $project) }}" class="hover:text-gray-800 transition truncate max-w-36">{{ $project->project_name }}</a>
    <span class="material-symbols-outlined text-sm">chevron_right</span>
    <span class="text-gray-900 font-bold">G-IDS Score</span>
@endsection

@php
    $nextAction = $project->nextActionFor(auth()->user());
@endphp

@section('topbar-actions')
    @if(auth()->user()->canInspect())
        <a href="{{ route('projects.components', $project) }}"
           class="flex items-center gap-1.5 px-4 py-2.5 border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-100 transition min-h-[44px]">
            <span class="material-symbols-outlined text-lg">grid_on</span>
            Grid
        </a>
    @endif
    <a href="{{ route('reports.show', $project) }}"
       class="flex items-center gap-2 px-5 py-2.5 bg-eids-primary text-white text-sm font-bold rounded-xl hover:bg-eids-dark transition shadow-xs min-h-[44px]">
        <span class="material-symbols-outlined text-lg">description</span>
        Official PDF Report &rarr;
    </a>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Next Action Hero Card --}}
    <x-next-action-card :action="$nextAction" />

    {{-- Pipeline Step Indicator --}}
    <x-workflow-step step="5" :project="$project" :role="auth()->user()->role" />

    {{-- Score Hero Section --}}
    @php
        $ratingColor = match($rating) {
            'GOOD'     => 'emerald',
            'MODERATE' => 'amber',
            default    => 'red',
        };
        $ratingMap = ['GOOD' => 'GOOD RATING', 'MODERATE' => 'MODERATE RATING', 'WEAK' => 'WEAK RATING'];

        // Radial gauge geometry: a 240deg arc swept from 150deg (value 0) to 390deg
        // (value = gaugeMax), clockwise through the top, split into WEAK / MODERATE / GOOD zones.
        $gaugeMax   = $archPct + $meScore + $extScore;
        $gaugeCx    = 110; $gaugeCy = 110; $gaugeR = 84;
        $gaugeStart = 150; $gaugeSweep = 240;
        $tFor      = fn ($v) => max(0, min($v, $gaugeMax)) / max($gaugeMax, 0.0001);
        $angleFor  = fn ($v) => $gaugeStart + $gaugeSweep * $tFor($v);
        $pointAt   = fn ($angleDeg, $radius) => [
            $gaugeCx + $radius * cos(deg2rad($angleDeg)),
            $gaugeCy + $radius * sin(deg2rad($angleDeg)),
        ];
        $arcPath = function ($v1, $v2) use ($angleFor, $pointAt, $gaugeR) {
            $theta1 = $angleFor($v1);
            $theta2 = $angleFor($v2);
            [$x1, $y1] = $pointAt($theta1, $gaugeR);
            [$x2, $y2] = $pointAt($theta2, $gaugeR);
            $largeArc = ($theta2 - $theta1) > 180 ? 1 : 0;
            return "M {$x1} {$y1} A {$gaugeR} {$gaugeR} 0 {$largeArc} 1 {$x2} {$y2}";
        };
        $weakArc = $arcPath(0, $ratingMod);
        $modArc  = $arcPath($ratingMod, $ratingBaik);
        $goodArc = $arcPath($ratingBaik, $gaugeMax);

        [$needleX, $needleY] = $pointAt($angleFor($totalScore), $gaugeR - 22);

        // Decorative tick marks around the outer edge of the arc.
        $tickCount = 32;
        $ticks = collect(range(0, $tickCount))->map(function ($i) use ($tickCount, $gaugeStart, $gaugeSweep, $pointAt) {
            $angle = $gaugeStart + $gaugeSweep * ($i / $tickCount);
            [$x1, $y1] = $pointAt($angle, 92);
            [$x2, $y2] = $pointAt($angle, 100);
            return compact('x1', 'y1', 'x2', 'y2');
        });
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        {{-- Total Score Card --}}
        <div class="lg:col-span-1 bg-eids-primary rounded-2xl p-6 text-white flex flex-col items-center justify-center text-center shadow-md border border-white/10 relative overflow-hidden">
            <div class="text-xs text-eids-light uppercase tracking-widest font-extrabold mb-2">G-IDS Final Score</div>

            {{-- Radial gauge --}}
            <svg viewBox="0 5 220 165" class="w-full max-w-[220px]" aria-hidden="true">
                @foreach($ticks as $tick)
                    <line x1="{{ $tick['x1'] }}" y1="{{ $tick['y1'] }}" x2="{{ $tick['x2'] }}" y2="{{ $tick['y2'] }}" stroke="white" stroke-opacity="0.15" stroke-width="1.5" />
                @endforeach
                <path d="{{ $weakArc }}" stroke="#f87171" stroke-width="8"  fill="none" stroke-linecap="round" />
                <path d="{{ $modArc }}"  stroke="#fbbf24" stroke-width="11" fill="none" stroke-linecap="round" />
                <path d="{{ $goodArc }}" stroke="#34d399" stroke-width="14" fill="none" stroke-linecap="round" />
                <line x1="{{ $gaugeCx }}" y1="{{ $gaugeCy }}" x2="{{ $needleX }}" y2="{{ $needleY }}"
                      stroke="white" stroke-width="4" stroke-linecap="round" />
                <circle cx="{{ $gaugeCx }}" cy="{{ $gaugeCy }}" r="14" fill="white" fill-opacity="0.12" />
                <circle cx="{{ $gaugeCx }}" cy="{{ $gaugeCy }}" r="7" fill="white" />
            </svg>

            <div class="text-4xl lg:text-5xl font-extrabold tracking-tight -mt-1">{{ number_format($totalScore, 2) }}</div>
            <div class="text-white/70 text-xs mt-1 font-semibold">out of {{ number_format($gaugeMax, 2) }} max pts</div>
            <div class="mt-4 px-4 py-1.5 rounded-full text-xs font-extrabold tracking-wider uppercase
                @if($rating === 'GOOD') bg-emerald-500 text-white
                @elseif($rating === 'MODERATE') bg-amber-400 text-amber-950
                @else bg-red-500 text-white @endif shadow-xs">
                {{ $ratingMap[$rating] ?? $rating }}
            </div>
        </div>

        {{-- Subtotals Cards --}}
        <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col items-center justify-center text-center">
                <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Architectural Subtotal</div>
                <div class="text-3xl lg:text-4xl font-extrabold text-eids-primary font-mono">{{ number_format($sArch, 2) }}</div>
                <div class="text-xs text-gray-500 font-semibold mt-1">Max {{ number_format($archPct, 2) }} pts</div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col items-center justify-center text-center">
                <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">M&amp;E Fittings (Annex B)</div>
                <div class="text-3xl lg:text-4xl font-extrabold text-eids-accent font-mono">{{ number_format($meScore, 2) }}</div>
                <div class="text-xs text-gray-500 font-semibold mt-1">
                    Max {{ number_format($mePct, 2) }} pts &middot; {{ $meRow['passRate'] }}% pass rate ({{ $meRow['pass'] }}/{{ $meRow['total'] }})
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col items-center justify-center text-center">
                <div class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">External Works (Annex C)</div>
                <div class="text-3xl lg:text-4xl font-extrabold text-gray-700 font-mono">{{ number_format($extScore, 2) }}</div>
                <div class="text-xs text-gray-500 font-semibold mt-1">
                    Max {{ number_format($extPct, 2) }} pts &middot; {{ $externalRow['passRate'] }}% pass rate ({{ $externalRow['pass'] }}/{{ $externalRow['total'] }})
                </div>
            </div>
        </div>
    </div>

    {{-- Component Breakdown Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 flex-wrap gap-2">
            <h2 class="font-extrabold text-gray-900 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-eids-accent text-lg">analytics</span>
                Architectural Component Pass Rates &amp; Weighted Score (S_comp)
            </h2>
            <span class="text-xs text-gray-500 font-mono font-semibold bg-gray-100 px-3 py-1 rounded-lg">Formula: S_comp = (Pass / Total) &times; Weightage</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-6 py-4 text-left">Component</th>
                        <th class="px-4 py-4 text-center hidden sm:table-cell">Weightage</th>
                        <th class="px-4 py-4 text-center">Pass</th>
                        <th class="px-4 py-4 text-center">Fail</th>
                        <th class="px-4 py-4 text-center hidden md:table-cell">Pass Rate</th>
                        <th class="px-4 py-4 text-center">S_comp Score</th>
                        <th class="px-6 py-4 text-left hidden lg:table-cell">Pass Rate Bar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($rows as $code => $row)
                        @php
                            $barWidth = min(100, $row['passRate']);
                            $barCls = $row['passRate'] >= 80 ? 'bg-emerald-500' : ($row['passRate'] >= 60 ? 'bg-amber-400' : 'bg-red-500');
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="px-6 py-4">
                                <div class="font-mono text-xs font-extrabold text-eids-primary bg-eids-primary/10 px-2 py-0.5 rounded-md inline-block mb-0.5">{{ $code }}</div>
                                <div class="text-xs text-gray-700 font-semibold">{{ $row['name'] }}</div>
                            </td>
                            <td class="px-4 py-4 text-center hidden sm:table-cell font-mono text-xs text-gray-600 font-bold">{{ $row['weightage'] }}%</td>
                            <td class="px-4 py-4 text-center font-extrabold text-emerald-800">{{ $row['pass'] }}</td>
                            <td class="px-4 py-4 text-center font-extrabold text-red-800">{{ $row['fail'] }}</td>
                            <td class="px-4 py-4 text-center hidden md:table-cell font-extrabold text-gray-800">
                                {{ number_format($row['passRate'], 1) }}%
                            </td>
                            <td class="px-4 py-4 text-center font-extrabold text-eids-primary font-mono text-base">
                                {{ number_format($row['sComp'], 2) }}
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <div class="w-36 h-2.5 bg-gray-100 rounded-full overflow-hidden border border-gray-200/50">
                                    <div class="h-full {{ $barCls }} rounded-full transition-all duration-300" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Action Bar --}}
    <div class="mt-6 flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex-wrap gap-3">
        @if(auth()->user()->canInspect())
            <a href="{{ route('projects.components', $project) }}" class="min-h-[44px] px-5 py-2.5 text-xs font-extrabold text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
                &larr; Return to Inspection Grid
            </a>
        @endif
        <a href="{{ route('reports.show', $project) }}" class="min-h-[44px] px-6 py-2.5 bg-eids-primary text-white text-xs font-extrabold rounded-xl hover:bg-eids-dark transition flex items-center gap-2 shadow-md">
            <span class="material-symbols-outlined text-base">description</span>
            Generate Official G-IDS Certificate PDF &rarr;
        </a>
    </div>

</div>
@endsection
