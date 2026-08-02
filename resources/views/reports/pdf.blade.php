<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-IDS Report — {{ $project->project_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }

        .header { background: #00342b; color: #fff; padding: 20px 24px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header .sub { font-size: 10px; color: rgba(255,255,255,0.6); }

        .score-hero { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .score-val { font-size: 32px; font-weight: bold; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 99px; font-size: 10px; font-weight: bold; }
        .badge-good     { background: #10b981; color: #fff; }
        .badge-moderate { background: #f59e0b; color: #fff; }
        .badge-weak     { background: #ef4444; color: #fff; }

        .section { border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 14px; overflow: hidden; }
        .section-header { background: #f9fafb; padding: 8px 12px; font-weight: bold; font-size: 10px;
                          border-bottom: 1px solid #e5e7eb; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }

        table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        th    { background: #f3f4f6; padding: 6px 10px; text-align: left; font-weight: bold;
                color: #6b7280; text-transform: uppercase; font-size: 8.5px; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
        td    { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }

        .pass { color: #059669; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        .muted { color: #9ca3af; }

        .total-row td { background: #00342b; color: #fff; font-weight: bold; font-size: 11px; padding: 8px 10px; }
        .sub-row  td  { background: #f9fafb; color: #6b7280; }
        .arch-row td  { background: #f3f4f6; font-weight: bold; }

        .dl { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; padding: 10px 12px; }
        .dl-item dt { font-size: 8px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1px; }
        .dl-item dd { font-weight: bold; color: #111827; }

        .footer { text-align: center; color: #9ca3af; font-size: 8px; margin-top: 20px; padding-top: 8px;
                  border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>

@php
    $ratingMap = ['GOOD' => 'Good', 'MODERATE' => 'Moderate', 'WEAK' => 'Weak'];
    $typeMap   = ['teres' => 'Terrace', 'semi_d' => 'Semi-D', 'banglo' => 'Bungalow'];
    $badgeCls  = ['GOOD' => 'badge-good', 'MODERATE' => 'badge-moderate', 'WEAK' => 'badge-weak'];
    $sevCls    = ['low' => 'muted', 'medium' => 'fail', 'high' => 'fail'];
@endphp

{{-- Header --}}
<div class="header">
    <div class="score-hero">
        <div>
            <h1>{{ $project->project_name }}</h1>
            <div class="sub">{{ $project->project_no }} &nbsp;·&nbsp; {{ $typeMap[$project->building_type] ?? '' }} &nbsp;·&nbsp; {{ $project->location }}</div>
            <div class="sub" style="margin-top:4px">E-IDS Inspection Report &nbsp;·&nbsp; {{ $project->updated_at ? $project->updated_at->format('d M Y') : now()->format('d M Y') }}</div>
        </div>
        <div style="text-align:center">
            <div class="score-val">{{ number_format($totalScore, 2) }}</div>
            <div class="sub" style="margin-bottom:4px">G-IDS Score</div>
            <span class="badge {{ $badgeCls[$rating] ?? '' }}">{{ $ratingMap[$rating] ?? $rating }}</span>
        </div>
    </div>
</div>

{{-- Project Info --}}
<div class="section">
    <div class="section-header">Project Information</div>
    <div class="dl">
        <div class="dl-item"><dt>Developer</dt><dd>{{ $project->developer_name }}</dd></div>
        <div class="dl-item"><dt>Contractor</dt><dd>{{ $project->contractor_name }}</dd></div>
        <div class="dl-item"><dt>Building Type</dt><dd>{{ $typeMap[$project->building_type] ?? '' }}</dd></div>
        <div class="dl-item"><dt>Total Units</dt><dd>{{ number_format($project->total_units) }}</dd></div>
        <div class="dl-item"><dt>GFA</dt><dd>{{ number_format($project->floor_area_sqm, 2) }} m²</dd></div>
        <div class="dl-item"><dt>Inspector</dt><dd>{{ $project->creator?->name ?? '—' }}</dd></div>
    </div>
</div>

{{-- Score Breakdown --}}
<div class="section">
    <div class="section-header">Architectural Score Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th class="text-center">Max</th>
                <th class="text-center">Pass</th>
                <th class="text-center">Fail</th>
                <th class="text-center">Pass Rate</th>
                <th class="text-right">S_comp</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $code => $row)
                <tr>
                    <td><strong>{{ $code }}</strong> — {{ $row['name'] }}</td>
                    <td class="text-center muted">{{ $row['weightage'] }}</td>
                    <td class="text-center pass">{{ $row['pass'] }}</td>
                    <td class="text-center {{ $row['fail'] > 0 ? 'fail' : 'muted' }}">{{ $row['fail'] }}</td>
                    <td class="text-center {{ $row['passRate'] >= 80 ? 'pass' : ($row['passRate'] >= 60 ? '' : 'fail') }}">
                        {{ $row['total'] > 0 ? $row['passRate'] . '%' : '—' }}
                    </td>
                    <td class="text-right"><strong>{{ number_format($row['sComp'], 2) }}</strong></td>
                </tr>
            @endforeach
            <tr class="arch-row">
                <td colspan="5"><strong>S_arch (Architectural Sub-total)</strong></td>
                <td class="text-right"><strong>{{ number_format($sArch, 2) }}</strong></td>
            </tr>
            <tr class="sub-row">
                <td colspan="5">M&E Work (fixed)</td>
                <td class="text-right">{{ number_format($meScore, 2) }}</td>
            </tr>
            <tr class="sub-row">
                <td colspan="5">External Work (fixed)</td>
                <td class="text-right">{{ number_format($extScore, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5">G-IDS TOTAL SCORE</td>
                <td class="text-right">{{ number_format($totalScore, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Defects --}}
@if($project->defects->isNotEmpty())
    <div class="section">
        <div class="section-header">Defect Register ({{ $openDefects }} Open / {{ $resolvedDefects }} Resolved)</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Component</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th class="text-center">Severity</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($project->defects as $i => $defect)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $defect->component_name }}</td>
                        <td>{{ $defect->location }}</td>
                        <td>{{ Str::limit($defect->defect_description, 80) }}</td>
                        <td class="text-center {{ $sevCls[$defect->severity] ?? '' }}">{{ ucfirst($defect->severity) }}</td>
                        <td class="text-center">{{ $defect->status_label }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="footer">
    Generated by E-IDS — Electronic Inspection Defect System &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
