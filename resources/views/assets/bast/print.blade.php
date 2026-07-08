@php
    $typeLabels = [
        'handover' => 'Handover',
        'return' => 'Return',
        'replacement' => 'Replacement',
        'loan' => 'Loan',
    ];
    $snapshot = $bast->asset_snapshot ?? [];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bast->document_number }}</title>
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
        .statement {
            line-height: 1.7;
            margin: 18px 0;
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
        <button class="button" onclick="window.print()">Print BAST</button>
    </div>

    <main class="page">
        <header class="header">
            <div>
                <div class="brand">ZINUS IT</div>
                <div class="muted">Asset Management</div>
            </div>
            <div style="text-align: right;">
                <div><strong>Date:</strong> {{ optional($bast->bast_date)->format('d M Y') }}</div>
                <div><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $bast->status)) }}</div>
            </div>
        </header>

        <h1>Berita Acara Serah Terima Asset</h1>
        <div class="doc-number">No: <strong>{{ $bast->document_number }}</strong></div>

        <p class="statement">
            Pada tanggal {{ optional($bast->bast_date)->format('d M Y') }}, telah dilakukan proses
            {{ strtolower($typeLabels[$bast->bast_type] ?? $bast->bast_type) }} asset IT dengan detail sebagai berikut.
        </p>

        <div class="section-title">Asset</div>
        <table>
            <tr><th>Asset Code</th><td>{{ $snapshot['asset_code'] ?? $bast->asset?->asset_code ?? '-' }}</td></tr>
            <tr><th>Name</th><td>{{ $snapshot['name'] ?? $bast->asset?->name ?? '-' }}</td></tr>
            <tr><th>Category</th><td>{{ $snapshot['category'] ?? $bast->asset?->category ?? '-' }}</td></tr>
            <tr><th>Serial Number</th><td>{{ $snapshot['serial_number'] ?? $bast->asset?->serial_number ?? '-' }}</td></tr>
            <tr><th>Hostname</th><td>{{ $snapshot['hostname'] ?? $bast->asset?->hostname ?? '-' }}</td></tr>
            <tr><th>Brand / Model</th><td>{{ trim(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?: '-' }}</td></tr>
            <tr><th>Condition</th><td>{{ $bast->condition_summary ?: ($snapshot['condition'] ?? '-') }}</td></tr>
            <tr><th>Location</th><td>{{ $bast->handover_location ?: ($snapshot['location'] ?? '-') }}</td></tr>
        </table>

        <div class="section-title">Recipient</div>
        <table>
            <tr><th>Name</th><td>{{ $bast->recipient_name }}</td></tr>
            <tr><th>Email</th><td>{{ $bast->recipient_email ?: '-' }}</td></tr>
            <tr><th>Department</th><td>{{ $bast->recipient_department ?: $bast->department?->name ?: '-' }}</td></tr>
        </table>

        <div class="section-title">Accessories And Notes</div>
        <table>
            <tr><th>Accessories</th><td>{{ count($bast->accessories ?? []) ? implode(', ', $bast->accessories) : '-' }}</td></tr>
            <tr><th>Notes</th><td>{!! nl2br(e($bast->notes ?: '-')) !!}</td></tr>
        </table>

        <div class="signatures">
            <div class="signature">
                <div>Diserahkan oleh,</div>
                <div class="signature-line"></div>
                <strong>{{ $bast->creator?->name ?? 'IT Admin' }}</strong>
            </div>
            <div class="signature">
                <div>Diterima oleh,</div>
                <div class="signature-line"></div>
                <strong>{{ $bast->recipient_name }}</strong>
            </div>
        </div>
    </main>
</body>
</html>
