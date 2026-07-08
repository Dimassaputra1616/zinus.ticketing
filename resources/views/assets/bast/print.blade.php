@php
    $typeLabels = [
        'handover' => 'Handover',
        'return' => 'Return',
        'replacement' => 'Replacement',
        'loan' => 'Loan',
    ];
    $snapshot = $bast->asset_snapshot ?? [];
    $appName = setting('app_name', 'Zinus Dream');
    $logoUrl = setting('app_logo')
        ? \Illuminate\Support\Facades\Storage::disk('public')->url(setting('app_logo'))
        : asset('images/logo.png');
    $brandColor = setting('theme_color', '#12824C');
    $brandStrong = setting('theme_color_strong', '#0F6D3F');
    $brandSoft = setting('theme_color_secondary', '#53B77A');
    $statusLabel = ucwords(str_replace('_', ' ', $bast->status));
    $typeLabel = $typeLabels[$bast->bast_type] ?? ucwords(str_replace('_', ' ', $bast->bast_type));
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bast->document_number }}</title>
    <style>
        :root {
            --brand: {{ $brandColor }};
            --brand-strong: {{ $brandStrong }};
            --brand-soft: {{ $brandSoft }};
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe4ef;
            --paper: #ffffff;
            --wash: #f6f8fb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #edf2f7;
            color: var(--ink);
            font-family: Inter, Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.55;
        }
        .print-bar {
            align-items: center;
            display: flex;
            justify-content: flex-end;
            margin: 16px auto;
            width: 210mm;
        }
        .button {
            background: var(--brand);
            border: 0;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 109, 63, 0.18);
            color: #fff;
            cursor: pointer;
            font-weight: 800;
            padding: 10px 16px;
        }
        .page {
            background: var(--paper);
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
            margin: 0 auto 28px;
            min-height: 297mm;
            overflow: hidden;
            position: relative;
            width: 210mm;
        }
        .accent {
            background: linear-gradient(90deg, var(--brand-strong), var(--brand), var(--brand-soft));
            height: 8px;
        }
        .content {
            padding: 16mm 17mm 14mm;
        }
        .header {
            align-items: flex-start;
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }
        .brand-lockup {
            align-items: center;
            display: flex;
            gap: 12px;
        }
        .logo-box {
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 14px;
            display: flex;
            height: 54px;
            justify-content: center;
            padding: 7px;
            width: 54px;
        }
        .logo-box img {
            height: 100%;
            object-fit: contain;
            width: 100%;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0.04em;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .brand-subtitle {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            margin-top: 4px;
        }
        .meta-card {
            background: var(--wash);
            border: 1px solid var(--line);
            border-radius: 8px;
            min-width: 190px;
            padding: 10px 12px;
            text-align: right;
        }
        .meta-row {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .meta-row + .meta-row { margin-top: 6px; }
        .meta-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .meta-value {
            font-weight: 900;
        }
        .title-panel {
            background: #f8fbfa;
            border: 1px solid var(--line);
            border-radius: 8px;
            margin-top: 22px;
            padding: 18px 20px;
            position: relative;
        }
        .title-panel:before {
            background: var(--brand);
            border-radius: 999px;
            content: "";
            height: calc(100% - 24px);
            left: 0;
            position: absolute;
            top: 12px;
            width: 4px;
        }
        h1 {
            font-size: 21px;
            font-weight: 900;
            letter-spacing: 0.07em;
            line-height: 1.25;
            margin: 0;
            text-transform: uppercase;
        }
        .doc-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .pill {
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.08em;
            padding: 5px 9px;
            text-transform: uppercase;
        }
        .pill-brand {
            background: rgba(18, 130, 76, 0.1);
            color: var(--brand-strong);
        }
        .pill-neutral {
            background: #eef2f7;
            color: #334155;
        }
        .statement {
            border-left: 3px solid var(--brand);
            color: #334155;
            line-height: 1.7;
            margin: 18px 0 16px;
            padding: 2px 0 2px 12px;
        }
        .section {
            margin-top: 16px;
        }
        .section-header {
            align-items: center;
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        .section-dot {
            background: var(--brand);
            border-radius: 999px;
            height: 9px;
            width: 9px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }
        .data-table {
            border: 1px solid var(--line);
            border-collapse: separate;
            border-radius: 8px;
            border-spacing: 0;
            overflow: hidden;
            width: 100%;
        }
        .data-table th,
        .data-table td {
            border-bottom: 1px solid var(--line);
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        .data-table tr:last-child th,
        .data-table tr:last-child td {
            border-bottom: 0;
        }
        .data-table th {
            background: #f3f7f6;
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            width: 31%;
        }
        .data-table td {
            background: #fff;
            font-weight: 700;
        }
        .two-columns {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr 1fr;
        }
        .signature-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 34px;
        }
        .signature-card {
            border: 1px solid var(--line);
            border-radius: 8px;
            min-height: 126px;
            padding: 13px 16px 10px;
            text-align: center;
        }
        .signature-role {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }
        .signature-line {
            border-bottom: 1.5px solid #1e293b;
            height: 62px;
            margin-bottom: 8px;
        }
        .signature-name {
            font-size: 12px;
            font-weight: 900;
        }
        .footer {
            align-items: center;
            border-top: 1px solid var(--line);
            color: var(--muted);
            display: flex;
            font-size: 10px;
            justify-content: space-between;
            margin-top: 18px;
            padding-top: 10px;
        }
        @page {
            margin: 0;
            size: A4;
        }
        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-bar { display: none; }
            .page {
                border-radius: 0;
                box-shadow: none;
                margin: 0;
                min-height: 297mm;
                width: 210mm;
            }
            .content {
                padding: 15mm 16mm 13mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="button" onclick="window.print()">Print BAST</button>
    </div>

    <main class="page">
        <div class="accent"></div>
        <div class="content">
            <header class="header">
                <div class="brand-lockup">
                    <div class="logo-box">
                        <img src="{{ $logoUrl }}" alt="{{ $appName }}">
                    </div>
                    <div>
                        <div class="brand-name">{{ $appName }}</div>
                        <div class="brand-subtitle">IT Asset Management</div>
                    </div>
                </div>
                <div class="meta-card">
                    <div class="meta-row">
                        <span class="meta-label">Date</span>
                        <span class="meta-value">{{ optional($bast->bast_date)->format('d M Y') }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Type</span>
                        <span class="meta-value">{{ $typeLabel }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Status</span>
                        <span class="meta-value">{{ $statusLabel }}</span>
                    </div>
                </div>
            </header>

            <section class="title-panel">
                <h1>Berita Acara Serah Terima Asset</h1>
                <div class="doc-row">
                    <span class="pill pill-brand">{{ $bast->document_number }}</span>
                    <span class="pill pill-neutral">{{ $typeLabel }}</span>
                    <span class="pill pill-neutral">{{ $statusLabel }}</span>
                </div>
            </section>

            <p class="statement">
                Pada tanggal {{ optional($bast->bast_date)->format('d M Y') }}, telah dilakukan proses
                <strong>{{ strtolower($typeLabel) }}</strong> asset IT dengan detail, kondisi, dan penerima sebagaimana tercatat pada dokumen ini.
            </p>

            <div class="two-columns">
                <section class="section">
                    <div class="section-header">
                        <span class="section-dot"></span>
                        <span class="section-title">Asset</span>
                    </div>
                    <table class="data-table">
                        <tr><th>Asset Code</th><td>{{ $snapshot['asset_code'] ?? $bast->asset?->asset_code ?? '-' }}</td></tr>
                        <tr><th>Name</th><td>{{ $snapshot['name'] ?? $bast->asset?->name ?? '-' }}</td></tr>
                        <tr><th>Category</th><td>{{ $snapshot['category'] ?? $bast->asset?->category ?? '-' }}</td></tr>
                        <tr><th>Serial No.</th><td>{{ $snapshot['serial_number'] ?? $bast->asset?->serial_number ?? '-' }}</td></tr>
                        <tr><th>Hostname</th><td>{{ $snapshot['hostname'] ?? $bast->asset?->hostname ?? '-' }}</td></tr>
                        <tr><th>Brand / Model</th><td>{{ trim(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?: '-' }}</td></tr>
                    </table>
                </section>

                <section class="section">
                    <div class="section-header">
                        <span class="section-dot"></span>
                        <span class="section-title">Recipient</span>
                    </div>
                    <table class="data-table">
                        <tr><th>Name</th><td>{{ $bast->recipient_name }}</td></tr>
                        <tr><th>Email</th><td>{{ $bast->recipient_email ?: '-' }}</td></tr>
                        <tr><th>Department</th><td>{{ $bast->recipient_department ?: $bast->department?->name ?: '-' }}</td></tr>
                        <tr><th>Location</th><td>{{ $bast->handover_location ?: ($snapshot['location'] ?? '-') }}</td></tr>
                        <tr><th>Condition</th><td>{{ $bast->condition_summary ?: ($snapshot['condition'] ?? '-') }}</td></tr>
                        <tr><th>Owner</th><td>{{ $snapshot['assigned_user'] ?? '-' }}</td></tr>
                    </table>
                </section>
            </div>

            <section class="section">
                <div class="section-header">
                    <span class="section-dot"></span>
                    <span class="section-title">Accessories And Notes</span>
                </div>
                <table class="data-table">
                    <tr><th>Accessories</th><td>{{ count($bast->accessories ?? []) ? implode(', ', $bast->accessories) : '-' }}</td></tr>
                    <tr><th>Notes</th><td>{!! nl2br(e($bast->notes ?: '-')) !!}</td></tr>
                </table>
            </section>

            <div class="signature-grid">
                <div class="signature-card">
                    <div class="signature-role">Diserahkan oleh,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $bast->creator?->name ?? 'IT Admin' }}</div>
                </div>
                <div class="signature-card">
                    <div class="signature-role">Diterima oleh,</div>
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $bast->recipient_name }}</div>
                </div>
            </div>

            <footer class="footer">
                <span>{{ $appName }} - IT Asset Management</span>
                <span>Generated {{ now()->format('d M Y H:i') }}</span>
            </footer>
        </div>
    </main>
</body>
</html>
