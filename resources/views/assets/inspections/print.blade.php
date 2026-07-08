@php
    $typeLabels = [
        'routine' => 'Routine',
        'handover' => 'Handover',
        'return' => 'Return',
        'repair' => 'Repair',
    ];
    $resultLabels = [
        'passed' => 'Passed',
        'needs_repair' => 'Needs Repair',
        'replace' => 'Replace',
        'retire' => 'Retire',
    ];
    $checklistLabels = [
        'ok' => 'OK',
        'issue' => 'Issue',
        'na' => 'N/A',
    ];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $inspection->inspection_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 18mm;
        }
        .print-bar {
            width: 210mm;
            margin: 16px auto;
            text-align: right;
        }
        .button {
            border: 0;
            border-radius: 8px;
            background: #059669;
            color: white;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 14px;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 14px;
        }
        .brand {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }
        .muted { color: #64748b; }
        h1 {
            font-size: 18px;
            letter-spacing: 0.08em;
            margin: 24px 0 6px;
            text-align: center;
            text-transform: uppercase;
        }
        .doc-number {
            margin-bottom: 22px;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            font-size: 11px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            width: 32%;
        }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin: 20px 0 8px;
            text-transform: uppercase;
        }
        .signatures {
            display: grid;
            gap: 32px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 42px;
        }
        .signature {
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #0f172a;
            height: 72px;
            margin-bottom: 8px;
        }
        @media print {
            body { background: white; }
            .print-bar { display: none; }
            .page {
                margin: 0;
                min-height: auto;
                padding: 16mm;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="button" onclick="window.print()">Print Inspection</button>
    </div>

    <main class="page">
        <header class="header">
            <div>
                <div class="brand">ZINUS IT</div>
                <div class="muted">Device Inspection Report</div>
            </div>
            <div style="text-align: right;">
                <div><strong>Date:</strong> {{ optional($inspection->inspection_date)->format('d M Y') }}</div>
                <div><strong>Result:</strong> {{ $resultLabels[$inspection->result] ?? ucwords(str_replace('_', ' ', $inspection->result)) }}</div>
            </div>
        </header>

        <h1>Inspection Device Report</h1>
        <div class="doc-number">No: <strong>{{ $inspection->inspection_number }}</strong></div>

        <div class="section-title">Asset</div>
        <table>
            <tr><th>Asset Code</th><td>{{ $inspection->asset?->asset_code ?: '-' }}</td></tr>
            <tr><th>Name</th><td>{{ $inspection->asset?->name ?: '-' }}</td></tr>
            <tr><th>Category</th><td>{{ $inspection->asset?->category ?: '-' }}</td></tr>
            <tr><th>Serial Number</th><td>{{ $inspection->asset?->serial_number ?: '-' }}</td></tr>
            <tr><th>Department</th><td>{{ $inspection->asset?->department?->name ?: '-' }}</td></tr>
            <tr><th>Assigned User</th><td>{{ $inspection->asset?->user?->name ?: '-' }}</td></tr>
        </table>

        <div class="section-title">Inspection Summary</div>
        <table>
            <tr><th>Type</th><td>{{ $typeLabels[$inspection->inspection_type] ?? ucwords(str_replace('_', ' ', $inspection->inspection_type)) }}</td></tr>
            <tr><th>Overall Condition</th><td>{{ ucwords(str_replace('_', ' ', $inspection->overall_condition)) }}</td></tr>
            <tr><th>Result</th><td>{{ $resultLabels[$inspection->result] ?? ucwords(str_replace('_', ' ', $inspection->result)) }}</td></tr>
            <tr><th>Inspector</th><td>{{ $inspection->inspector?->name ?: '-' }}</td></tr>
            <tr><th>Next Inspection</th><td>{{ optional($inspection->next_inspection_date)->format('d M Y') ?: '-' }}</td></tr>
        </table>

        <div class="section-title">Checklist</div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checklistItems as $key => $label)
                    @php $value = $inspection->checklist[$key] ?? 'na'; @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $checklistLabels[$value] ?? strtoupper($value) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Findings And Action</div>
        <table>
            <tr><th>Findings</th><td>{!! nl2br(e($inspection->findings ?: '-')) !!}</td></tr>
            <tr><th>Action Required</th><td>{!! nl2br(e($inspection->action_required ?: '-')) !!}</td></tr>
        </table>

        <div class="signatures">
            <div class="signature">
                <div>Inspected by,</div>
                <div class="signature-line"></div>
                <strong>{{ $inspection->inspector?->name ?? 'IT Admin' }}</strong>
            </div>
            <div class="signature">
                <div>Approved by,</div>
                <div class="signature-line"></div>
                <strong>IT Manager</strong>
            </div>
        </div>
    </main>
</body>
</html>
