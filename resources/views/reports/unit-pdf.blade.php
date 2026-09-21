<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Defects Report: {{ $unit->unit_reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 34px 32px 60px 32px; }
        html { margin: 34px 32px 60px 32px; }

        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #24292f; line-height: 1.5; }

        h1, .section-header {
            font-family: 'DejaVu Serif', serif;
        }

        .masthead { border-bottom: 2.5px solid #00342b; padding-bottom: 10px; margin-bottom: 14px; }
        .kicker { font-size: 8px; letter-spacing: 1.6px; text-transform: uppercase; color: #00342b; font-weight: bold; margin-bottom: 6px; }
        h1 { font-size: 17px; font-weight: bold; color: #101828; }

        .dl { display: table; width: 100%; table-layout: fixed; padding: 12px 0; }
        .dl-row { display: table-row; }
        .dl-item { display: table-cell; width: 33.33%; padding: 0 10px 10px 0; vertical-align: top; }
        .dl-item dt { font-size: 7.5px; color: #8c959f; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .dl-item dd { font-weight: bold; color: #101828; font-size: 10px; }

        .section { border: 1px solid #d0d7de; margin-bottom: 16px; overflow: hidden; }
        .section-header { background: #f6f8fa; padding: 8px 14px; font-weight: bold; font-size: 10.5px;
                          border-bottom: 1.5px solid #00342b; color: #00342b; text-transform: uppercase; letter-spacing: 0.8px; }

        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead { display: table-header-group; }
        tr    { page-break-inside: avoid; }
        th    { background: #f6f8fa; padding: 7px 10px; text-align: left; font-weight: bold;
                color: #57606a; text-transform: uppercase; font-size: 7.5px; letter-spacing: 0.7px;
                border-top: 1px solid #d0d7de; border-bottom: 1px solid #d0d7de; }
        td    { padding: 7px 10px; border-bottom: 1px solid #eaeef2; color: #24292f; vertical-align: top; }
        .col-item { width: 6%; text-align: center; font-weight: bold; }
        .col-area { width: 22%; font-weight: bold; }
        .defect-line { margin-bottom: 4px; }
        .defect-line:last-child { margin-bottom: 0; }

        .note { padding: 10px 14px; font-size: 8.5px; color: #57606a; font-style: italic; background: #fffbeb;
                border: 1px solid #fde68a; border-radius: 3px; margin-bottom: 16px; }

        .sign-block { display: table; width: 100%; table-layout: fixed; margin-top: 10px; }
        .sign-col { display: table-cell; width: 33.33%; padding-right: 14px; vertical-align: top; }
        .sign-label { font-size: 8px; color: #57606a; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 30px; }
        .sign-line { border-top: 1px solid #24292f; padding-top: 4px; font-size: 8.5px; color: #57606a; }

        .doc-footer {
            position: fixed; bottom: -44px; left: 0; right: 0; text-align: center;
            color: #8c959f; font-size: 7.5px; font-style: italic; padding-top: 8px;
            border-top: 1px solid #d0d7de; letter-spacing: 0.2px;
        }
        .doc-footer .page-num:after { content: "Page " counter(page); }
    </style>
</head>
<body>

<div class="masthead">
    <div class="kicker">Electronic Inspection Defect System &nbsp;·&nbsp; Handover Defects Report</div>
    <h1>Defects Inspection Report — {{ $unit->unit_reference }}</h1>
</div>

<div class="dl">
    <div class="dl-row">
        <div class="dl-item">
            <dt>House Address</dt>
            <dd>{{ $unit->owner_address ?: $unit->project->location }}</dd>
        </div>
        <div class="dl-item">
            <dt>House Type</dt>
            <dd>{{ $unit->house_type ?: '—' }}</dd>
        </div>
        <div class="dl-item">
            <dt>Report Date</dt>
            <dd>{{ $unit->report_date?->format('d M Y') ?? '—' }}</dd>
        </div>
    </div>
    <div class="dl-row">
        <div class="dl-item">
            <dt>Owner</dt>
            <dd>{{ $unit->owner_name ?: '—' }}</dd>
        </div>
        <div class="dl-item">
            <dt>Owner Contact Number</dt>
            <dd>{{ $unit->owner_phone ?: '—' }}</dd>
        </div>
        <div class="dl-item">
            <dt>Rectification Deadline</dt>
            <dd>{{ $unit->rectification_deadline?->format('d M Y') ?? '—' }}</dd>
        </div>
    </div>
    <div class="dl-row">
        <div class="dl-item">
            <dt>Project</dt>
            <dd>{{ $unit->project->project_name }}</dd>
        </div>
        <div class="dl-item"></div>
        <div class="dl-item">
            <dt>Handover Date</dt>
            <dd>{{ $unit->handover_date?->format('d M Y') ?? '—' }}</dd>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-header">Defects List</div>
    <table>
        <thead>
            <tr>
                <th class="col-item">Item</th>
                <th class="col-area">Area</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($defects as $area => $areaDefects)
                <tr>
                    <td class="col-item">{{ $loop->iteration }}</td>
                    <td class="col-area">{{ $area }}</td>
                    <td>
                        @foreach($areaDefects as $defect)
                            <div class="defect-line">
                                &middot; {{ $defect->component_name }} — {{ $defect->defect_description }}
                            </div>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center; color:#8c959f;">No defects recorded for this house.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="note">
    Note: Every defect noted above is general in nature — the same defect must be rectified wherever it appears
    on the property, even in areas not individually listed here.
</div>

<div class="section" style="padding: 14px;">
    <div class="section-header" style="margin: -14px -14px 14px -14px;">Sign-off</div>
    <div class="sign-block">
        <div class="sign-col">
            <div class="sign-label">Prepared By</div>
            <div class="sign-line">Name &amp; Date</div>
        </div>
        <div class="sign-col">
            <div class="sign-label">Received By</div>
            <div class="sign-line">Name &amp; Date</div>
        </div>
        <div class="sign-col">
            <div class="sign-label">Rectification Work Confirmed Complete By</div>
            <div class="sign-line">Name &amp; Date</div>
        </div>
    </div>
</div>

@php
    $defectNo   = 0;
    $photoCards = $defects->flatMap(function ($areaDefects, $area) use (&$defectNo) {
        return $areaDefects->flatMap(function ($defect) use (&$defectNo, $area) {
            $sources = $defect->photos_base64;
            if ($sources->isEmpty()) {
                return collect();
            }
            $defectNo++;
            return $sources->map(fn ($src, $i) => [
                'defect' => $defect, 'area' => $area, 'src' => $src,
                'number' => $defectNo, 'index' => $i + 1, 'total' => $sources->count(),
            ]);
        });
    })->values();
@endphp

@if($photoCards->isNotEmpty())
    <div style="page-break-before: always;"></div>
    <div class="section">
        <div class="section-header">Annex: Defect Photographs ({{ $photoCards->count() }})</div>
        <div style="padding: 8px;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 8px;">
                @foreach($photoCards->chunk(2) as $chunk)
                    <tr>
                        @foreach($chunk as $card)
                            @php $defect = $card['defect']; @endphp
                            <td style="width: 50%; vertical-align: top; border: 1px solid #d0d7de; padding: 0; background: #fff; overflow: hidden;">
                                <div style="width: 100%; height: 160px; text-align: center; background: #f6f8fa; border-bottom: 1px solid #d0d7de; overflow: hidden;">
                                    @if($card['src'])
                                        <img src="{{ $card['src'] }}" style="max-width: 100%; max-height: 160px; vertical-align: middle;" />
                                    @else
                                        <div style="padding-top: 65px; color: #8c959f; font-size: 9px;">No image available</div>
                                    @endif
                                </div>
                                <div style="padding: 9px 10px;">
                                    <div style="font-family: 'DejaVu Serif', serif; font-weight: bold; font-size: 9.5px; color: #101828; margin-bottom: 3px;">
                                        Defect #{{ $card['number'] }}: {{ $card['area'] }}@if($card['total'] > 1) <span style="font-weight: normal; color: #57606a;">&middot; Photo {{ $card['index'] }} of {{ $card['total'] }}</span>@endif
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
