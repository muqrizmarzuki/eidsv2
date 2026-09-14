<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-IDS Report: {{ $project->project_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin: 34px 32px 60px 32px;
        }

        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #24292f; line-height: 1.5; }

        h1, h2, .serif, .header h1, .score-val, .section-header, .total-row td, .kicker {
            font-family: 'DejaVu Serif', serif;
        }

        {{-- Masthead --}}
        .masthead { border-bottom: 2.5px solid #00342b; padding-bottom: 10px; margin-bottom: 4px; }
        .kicker { font-size: 8px; letter-spacing: 1.6px; text-transform: uppercase; color: #00342b; font-weight: bold; margin-bottom: 6px; }

        .header { color: #101828; padding: 0 0 16px 0; margin-bottom: 18px; }
        .header h1 { font-size: 19px; font-weight: bold; margin-bottom: 5px; letter-spacing: 0.2px; }
        .header .sub { font-size: 9px; color: #57606a; }

        .score-hero { display: table; width: 100%; table-layout: fixed; }
        .score-hero-left { display: table-cell; vertical-align: bottom; }
        .score-block { display: table-cell; vertical-align: bottom; width: 220px; text-align: right;
                        border-left: 1.5px solid #d0d7de; padding-left: 18px; }
        .score-val { font-size: 30px; font-weight: bold; color: #00342b; letter-spacing: 0.5px; }
        .score-val .of { font-size: 12px; font-weight: normal; color: #8c959f; }
        .score-label { font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #57606a; margin: 3px 0 6px; }
        .badge { display: inline-block; padding: 3px 14px; border-radius: 2px; font-size: 8.5px; font-weight: bold;
                 letter-spacing: 0.8px; text-transform: uppercase; }
        .badge-good     { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-moderate { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-weak     { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .section { border: 1px solid #d0d7de; margin-bottom: 16px; overflow: hidden; }
        .section-compact { page-break-inside: avoid; }
        .section-header { background: #f6f8fa; padding: 8px 14px; font-weight: bold; font-size: 10.5px;
                          border-bottom: 1.5px solid #00342b; color: #00342b; text-transform: uppercase; letter-spacing: 0.8px; }

        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead { display: table-header-group; }
        tr    { page-break-inside: avoid; }
        th    { background: #f6f8fa; padding: 7px 10px; text-align: left; font-weight: bold;
                color: #57606a; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.7px;
                border-top: 1px solid #d0d7de; border-bottom: 1px solid #d0d7de; }
        td    { padding: 6.5px 10px; border-bottom: 1px solid #eaeef2; color: #24292f; }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }

        .pass { color: #047857; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .muted { color: #8c959f; }

        .total-row td { background: #00342b; color: #fff; font-weight: bold; font-size: 11px; padding: 9px 10px; letter-spacing: 0.4px; }
        .sub-row  td  { background: #f6f8fa; color: #57606a; font-style: italic; }
        .arch-row td  { background: #eaeef2; font-weight: bold; }

        .dl { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px 6px; padding: 12px 14px; }
        .dl-item dt { font-size: 7.5px; color: #8c959f; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .dl-item dd { font-weight: bold; color: #101828; font-size: 10px; }

        .doc-footer {
            position: fixed;
            bottom: -44px;
            left: 0;
            right: 0;
            text-align: center;
            color: #8c959f;
            font-size: 7.5px;
            font-style: italic;
            padding-top: 8px;
            border-top: 1px solid #d0d7de;
            letter-spacing: 0.2px;
        }
        .doc-footer .page-num:after {
            content: "Page " counter(page) " of " counter(pages);
        }
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
<div class="masthead">
    <div class="kicker">Electronic Inspection Defect System &nbsp;·&nbsp; Official Inspection Report</div>
</div>
<div class="header">
    <div class="score-hero">
        <div class="score-hero-left">
            <h1>{{ $project->project_name }}</h1>
            <div class="sub">{{ $project->project_no }} &nbsp;·&nbsp; {{ $typeMap[$project->building_type] ?? '' }} &nbsp;·&nbsp; {{ $project->location }}</div>
            <div class="sub" style="margin-top:4px">Report Date: {{ $project->updated_at ? $project->updated_at->format('d M Y') : now()->format('d M Y') }}</div>
        </div>
        <div class="score-block">
            <div class="score-val">{{ number_format($totalScore, 2) }}<span class="of"> / 100</span></div>
            <div class="score-label">E-IDS Composite Score</div>
            <span class="badge {{ $badgeCls[$rating] ?? '' }}">{{ $ratingMap[$rating] ?? $rating }}</span>
        </div>
    </div>
</div>

{{-- Project Info --}}
<div class="section section-compact">
    <div class="section-header">Project Information</div>
    <div class="dl">
        <div class="dl-item"><dt>Developer</dt><dd>{{ $project->developer_name }}</dd></div>
        <div class="dl-item"><dt>Contractor</dt><dd>{{ $project->contractor_name }}</dd></div>
        <div class="dl-item"><dt>Building Type</dt><dd>{{ $typeMap[$project->building_type] ?? '' }}</dd></div>
        <div class="dl-item"><dt>Total Units</dt><dd>{{ number_format($project->total_units) }}</dd></div>
        <div class="dl-item"><dt>GFA</dt><dd>{{ number_format($project->floor_area_sqm, 2) }} m²</dd></div>
        <div class="dl-item"><dt>Inspector</dt><dd>{{ $project->creator?->name ?? '—' }}</dd></div>
        <div class="dl-item"><dt>CIS 7:2021 Category</dt><dd>Category {{ $project->building_category }}</dd></div>
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
                    <td><strong>{{ $code }}</strong> &middot; {{ $row['name'] }}</td>
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
                <td colspan="5"><strong>S_arch (Architectural Sub-total, max {{ number_format($archPct, 2) }})</strong></td>
                <td class="text-right"><strong>{{ number_format($sArch, 2) }}</strong></td>
            </tr>
            <tr class="sub-row">
                <td colspan="4">M&amp;E Fittings (Annex B) &middot; {{ $meRow['passRate'] }}% pass ({{ $meRow['pass'] }}/{{ $meRow['total'] }})</td>
                <td class="text-center muted">max {{ number_format($mePct, 2) }}</td>
                <td class="text-right">{{ number_format($meScore, 2) }}</td>
            </tr>
            <tr class="sub-row">
                <td colspan="4">External Works (Annex C) &middot; {{ $externalRow['passRate'] }}% pass ({{ $externalRow['pass'] }}/{{ $externalRow['total'] }})</td>
                <td class="text-center muted">max {{ number_format($extPct, 2) }}</td>
                <td class="text-right">{{ number_format($extScore, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5">E-IDS TOTAL SCORE</td>
                <td class="text-right">{{ number_format($totalScore, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- QP Declarations --}}
<div class="section section-compact">
    <div class="section-header">QP Declarations (Material &amp; Functional Test)</div>
    <table>
        <thead>
            <tr><th>Item</th><th class="text-center">Status</th></tr>
        </thead>
        <tbody>
            @php
                $qp = $project->qpDeclarations->keyBy('item_code');
                $qpItems = ['QP_SKIM_COAT' => 'Skim Coat or Prepacked Plaster', 'QP_WATER_TIGHTNESS' => 'Wet-area Water-tightness Test'];
            @endphp
            @foreach($qpItems as $code => $label)
                @php $decl = $qp->get($code); @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td class="text-center {{ $decl?->is_earned ? 'pass' : 'fail' }}">{{ $decl?->is_earned ? 'Declared' : 'Not Declared' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Detailed Findings --}}
@if(!empty($findings))
    <div class="section">
        <div class="section-header">Detailed Findings: Failed Checklist Items ({{ count($findings) }})</div>
        <table>
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Location</th>
                    <th>Question</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($findings as $f)
                    <tr>
                        <td><strong>{{ $f['component'] }}</strong></td>
                        <td>{{ $f['location'] }}</td>
                        <td class="fail">{{ $f['question'] }}@if($f['value'] !== null) ({{ $f['value'] }} mm)@endif</td>
                        <td class="muted">{{ $f['remarks'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

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

{{-- Defect Photo Annex --}}
@php
    $defectsWithPhotos = $project->defects->filter(fn($d) => !empty($d->photo_base64) || !empty($d->local_photo_path) || !empty($d->photo_url));
@endphp

@if($defectsWithPhotos->isNotEmpty())
    <div style="page-break-before: always;"></div>
    <div class="section">
        <div class="section-header">Annex: Defect Photographic Evidence ({{ $defectsWithPhotos->count() }} Photo{{ $defectsWithPhotos->count() > 1 ? 's' : '' }})</div>
        <div style="padding: 8px;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 8px;">
                @foreach($defectsWithPhotos->chunk(2) as $chunkIndex => $chunk)
                    <tr>
                        @foreach($chunk as $itemIndex => $defect)
                            @php
                                $imgSrc = $defect->photo_base64 ?? $defect->local_photo_path ?? $defect->photo_url;
                            @endphp
                            <td style="width: 50%; vertical-align: top; border: 1px solid #d0d7de; padding: 0; background: #fff; overflow: hidden;">
                                <div style="width: 100%; height: 160px; text-align: center; background: #f6f8fa; border-bottom: 1px solid #d0d7de; overflow: hidden;">
                                    @if($imgSrc)
                                        <img src="{{ $imgSrc }}" style="max-width: 100%; max-height: 160px; vertical-align: middle;" />
                                    @else
                                        <div style="padding-top: 65px; color: #8c959f; font-size: 9px;">No image available</div>
                                    @endif
                                </div>
                                <div style="padding: 9px 10px;">
                                    <div style="font-family: 'DejaVu Serif', serif; font-weight: bold; font-size: 9.5px; color: #101828; margin-bottom: 3px;">
                                        Defect #{{ $chunkIndex * 2 + $itemIndex + 1 }}: {{ $defect->component_name }}
                                    </div>
                                    <div style="font-size: 7.5px; color: #57606a; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.4px;">
                                        Location: <strong>{{ $defect->location }}</strong> &nbsp;|&nbsp;
                                        Severity: <strong>{{ ucfirst($defect->severity) }}</strong> &nbsp;|&nbsp;
                                        Status: <strong>{{ $defect->status_label }}</strong>
                                    </div>
                                    <div style="font-size: 8.5px; color: #24292f; line-height: 1.35;">
                                        {{ Str::limit($defect->defect_description, 140) }}
                                    </div>
                                </div>
                            </td>
                        @endforeach
                        @if($chunk->count() < 2)
                            <td style="width: 50%; border: none;"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endif

<div class="doc-footer">
    Generated by E-IDS, Electronic Inspection Defect System &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
    &nbsp;·&nbsp; <span class="page-num"></span>
</div>

</body>
</html>
