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
    $photos = $inspection->photos ?? [];
    $appName = setting('app_name', 'Zinus Dream');
    $logoUrl = setting('app_logo')
        ? \Illuminate\Support\Facades\Storage::disk('public')->url(setting('app_logo'))
        : asset('images/logo.png');
    $typeLabel = $typeLabels[$inspection->inspection_type] ?? ucwords(str_replace('_', ' ', $inspection->inspection_type));
    $resultLabel = $resultLabels[$inspection->result] ?? ucwords(str_replace('_', ' ', $inspection->result));
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
            background: #e9eef4;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .print-bar {
            margin: 14px auto;
            text-align: right;
            width: 210mm;
        }
        .button {
            background: #047857;
            border: 0;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            padding: 9px 13px;
        }
        .page {
            background: #fff;
            margin: 0 auto 16px;
            min-height: 297mm;
            padding: 15mm 17mm;
            width: 210mm;
        }
        .letterhead {
            align-items: center;
            border-bottom: 2px solid #111827;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding-bottom: 10px;
        }
        .brand {
            align-items: center;
            display: flex;
            gap: 10px;
        }
        .brand img {
            height: 42px;
            object-fit: contain;
            width: 42px;
        }
        .brand-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .brand-subtitle {
            color: #4b5563;
            font-size: 11px;
            margin-top: 2px;
        }
        .doc-meta {
            color: #374151;
            font-size: 11px;
            line-height: 1.6;
            text-align: right;
        }
        .doc-title {
            margin: 20px 0 16px;
            text-align: center;
        }
        .doc-title h1 {
            font-size: 18px;
            letter-spacing: 0.05em;
            margin: 0;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title p {
            margin: 5px 0 0;
        }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            margin: 16px 0 7px;
            text-transform: uppercase;
        }
        .data-table {
            border-collapse: collapse;
            width: 100%;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #9ca3af;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }
        .data-table th {
            background: #f3f4f6;
            font-size: 11px;
            font-weight: 800;
            width: 28%;
        }
        .two-columns {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr 1fr;
        }
        .photo-strip {
            align-items: flex-start;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .photo-thumb {
            border: 1px solid #9ca3af;
            display: inline-block;
            padding: 2px;
            text-align: center;
        }
        .photo-thumb img {
            display: block;
            height: 28mm;
            object-fit: cover;
            width: 42mm;
        }
        .photo-thumb span {
            color: #374151;
            display: block;
            font-size: 10px;
            font-weight: 700;
            margin-top: 2px;
        }
        .photo-note {
            color: #4b5563;
            font-size: 11px;
            margin-top: 4px;
        }
        .status-ok { color: #047857; font-weight: 800; }
        .status-issue { color: #b45309; font-weight: 800; }
        .status-na { color: #6b7280; font-weight: 800; }
        .signature-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr 1fr;
            margin-top: 34px;
        }
        .signature {
            text-align: center;
        }
        .signature-space {
            border-bottom: 1px solid #111827;
            height: 68px;
            margin: 0 10px 6px;
        }
        .signature-name {
            font-weight: 800;
        }
        .footer {
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            display: flex;
            font-size: 10px;
            justify-content: space-between;
            margin-top: 18px;
            padding-top: 8px;
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
                margin: 0;
                min-height: 297mm;
                width: 210mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="button" onclick="window.print()">Print Inspection</button>
    </div>

    <main class="page">
        <header class="letterhead">
            <div class="brand">
                <img src="{{ $logoUrl }}" alt="{{ $appName }}">
                <div>
                    <div class="brand-name">{{ $appName }}</div>
                    <div class="brand-subtitle">Device Inspection Report</div>
                </div>
            </div>
            <div class="doc-meta">
                Tanggal: {{ optional($inspection->inspection_date)->format('d M Y') }}<br>
                Jenis: {{ $typeLabel }}<br>
                Hasil: {{ $resultLabel }}
            </div>
        </header>

        <section class="doc-title">
            <h1>Inspection Device Report</h1>
            <p>Nomor: <strong>{{ $inspection->inspection_number }}</strong></p>
        </section>

        <div class="two-columns">
            <section>
                <div class="section-title">Data Asset</div>
                <table class="data-table">
                    <tr><th>Kode Asset</th><td>{{ $inspection->asset?->asset_code ?: '-' }}</td></tr>
                    <tr><th>Nama Asset</th><td>{{ $inspection->asset?->name ?: '-' }}</td></tr>
                    <tr><th>Kategori</th><td>{{ $inspection->asset?->category ?: '-' }}</td></tr>
                    <tr><th>Serial Number</th><td>{{ $inspection->asset?->serial_number ?: '-' }}</td></tr>
                    <tr><th>Departemen</th><td>{{ $inspection->asset?->department?->name ?: '-' }}</td></tr>
                    <tr><th>User Terdaftar</th><td>{{ $inspection->asset?->user?->name ?: '-' }}</td></tr>
                </table>
            </section>

            <section>
                <div class="section-title">Ringkasan Inspection</div>
                <table class="data-table">
                    <tr><th>Jenis</th><td>{{ $typeLabel }}</td></tr>
                    <tr><th>Kondisi</th><td>{{ ucwords(str_replace('_', ' ', $inspection->overall_condition)) }}</td></tr>
                    <tr><th>Hasil</th><td>{{ $resultLabel }}</td></tr>
                    <tr><th>Inspector</th><td>{{ $inspection->inspector?->name ?: '-' }}</td></tr>
                    <tr><th>Next Check</th><td>{{ optional($inspection->next_inspection_date)->format('d M Y') ?: '-' }}</td></tr>
                </table>
            </section>
        </div>

        <section>
            <div class="section-title">Checklist</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checklistItems as $key => $label)
                        @php
                            $value = $inspection->checklist[$key] ?? 'na';
                            $statusClass = match ($value) {
                                'ok' => 'status-ok',
                                'issue' => 'status-issue',
                                default => 'status-na',
                            };
                        @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="{{ $statusClass }}">{{ $checklistLabels[$value] ?? strtoupper($value) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section>
            <div class="section-title">Temuan dan Tindak Lanjut</div>
            <table class="data-table">
                <tr><th>Temuan</th><td>{!! nl2br(e($inspection->findings ?: '-')) !!}</td></tr>
                <tr><th>Tindak Lanjut</th><td>{!! nl2br(e($inspection->action_required ?: '-')) !!}</td></tr>
                @if (count($photos))
                    <tr>
                        <th>Dokumentasi</th>
                        <td>
                            <div class="photo-strip">
                                @foreach (array_slice($photos, 0, 2) as $photo)
                                    @php $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($photo['path']); @endphp
                                    <div class="photo-thumb">
                                        <img src="{{ $photoUrl }}" alt="Foto inspection {{ $loop->iteration }}">
                                        <span>Foto {{ $loop->iteration }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @if (count($photos) > 2)
                                <div class="photo-note">+{{ count($photos) - 2 }} foto lain tersimpan di sistem.</div>
                            @endif
                        </td>
                    </tr>
                @endif
            </table>
        </section>

        <div class="signature-grid">
            <div class="signature">
                <div>Inspected by,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $inspection->inspector?->name ?? 'IT Admin' }}</div>
            </div>
            <div class="signature">
                <div>Approved by,</div>
                <div class="signature-space"></div>
                <div class="signature-name">IT Manager</div>
            </div>
        </div>

        <footer class="footer">
            <span>{{ $appName }} - Device Inspection Report</span>
            <span>Generated {{ now()->format('d M Y H:i') }}</span>
        </footer>
    </main>
</body>
</html>
