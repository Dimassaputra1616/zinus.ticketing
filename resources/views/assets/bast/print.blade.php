@php
    $typeLabels = [
        'handover' => 'Serah Terima',
        'return' => 'Pengembalian',
        'replacement' => 'Penggantian',
        'loan' => 'Peminjaman',
    ];
    $statusLabels = [
        'draft' => 'Draft',
        'issued' => 'Diterbitkan',
        'signed' => 'Ditandatangani',
        'void' => 'Dibatalkan',
    ];
    $snapshot = $bast->asset_snapshot ?? [];
    $photos = $bast->photos ?? [];
    $appName = setting('app_name', 'Zinus Dream');
    $logoUrl = setting('app_logo')
        ? \Illuminate\Support\Facades\Storage::disk('public')->url(setting('app_logo'))
        : asset('images/logo.png');
    $statusLabel = $statusLabels[$bast->status] ?? ucwords(str_replace('_', ' ', $bast->status));
    $typeLabel = $typeLabels[$bast->bast_type] ?? ucwords(str_replace('_', ' ', $bast->bast_type));
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
        .statement {
            margin: 0 0 14px;
            text-align: justify;
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
        .responsibility {
            border: 1px solid #9ca3af;
            padding: 10px 12px;
        }
        .responsibility p {
            margin: 0;
        }
        .responsibility ol {
            margin: 7px 0 0;
            padding-left: 18px;
        }
        .responsibility li + li {
            margin-top: 3px;
        }
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
        <button class="button" onclick="window.print()">Print BAST</button>
    </div>

    <main class="page">
        <header class="letterhead">
            <div class="brand">
                <img src="{{ $logoUrl }}" alt="{{ $appName }}">
                <div>
                    <div class="brand-name">{{ $appName }}</div>
                    <div class="brand-subtitle">IT Asset Management</div>
                </div>
            </div>
            <div class="doc-meta">
                Tanggal: {{ optional($bast->bast_date)->format('d M Y') }}<br>
                Jenis: {{ $typeLabel }}<br>
                Status: {{ $statusLabel }}
            </div>
        </header>

        <section class="doc-title">
            <h1>Berita Acara Serah Terima Asset</h1>
            <p>Nomor: <strong>{{ $bast->document_number }}</strong></p>
        </section>

        <p class="statement">
            Pada tanggal {{ optional($bast->bast_date)->format('d M Y') }}, pihak IT dan
            <strong>{{ $bast->recipient_name }}</strong> telah melakukan proses
            <strong>{{ strtolower($typeLabel) }}</strong> asset IT. Asset diterima sesuai data dan kondisi yang tercantum dalam berita acara ini untuk digunakan sebagai fasilitas kerja.
        </p>

        <div class="two-columns">
            <section>
                <div class="section-title">Data Asset</div>
                <table class="data-table">
                    <tr><th>Kode Asset</th><td>{{ $snapshot['asset_code'] ?? $bast->asset?->asset_code ?? '-' }}</td></tr>
                    <tr><th>Nama Asset</th><td>{{ $snapshot['name'] ?? $bast->asset?->name ?? '-' }}</td></tr>
                    <tr><th>Kategori</th><td>{{ $snapshot['category'] ?? $bast->asset?->category ?? '-' }}</td></tr>
                    <tr><th>Serial Number</th><td>{{ $snapshot['serial_number'] ?? $bast->asset?->serial_number ?? '-' }}</td></tr>
                    <tr><th>Hostname</th><td>{{ $snapshot['hostname'] ?? $bast->asset?->hostname ?? '-' }}</td></tr>
                    <tr><th>Merek / Model</th><td>{{ trim(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?: '-' }}</td></tr>
                </table>
            </section>

            <section>
                <div class="section-title">Penerima</div>
                <table class="data-table">
                    <tr><th>Nama</th><td>{{ $bast->recipient_name }}</td></tr>
                    <tr><th>Email</th><td>{{ $bast->recipient_email ?: '-' }}</td></tr>
                    <tr><th>Departemen</th><td>{{ $bast->recipient_department ?: $bast->department?->name ?: '-' }}</td></tr>
                    <tr><th>Lokasi</th><td>{{ $bast->handover_location ?: ($snapshot['location'] ?? '-') }}</td></tr>
                    <tr><th>Kondisi</th><td>{{ $bast->condition_summary ?: ($snapshot['condition'] ?? '-') }}</td></tr>
                    <tr><th>User Terdaftar</th><td>{{ $snapshot['assigned_user'] ?? '-' }}</td></tr>
                </table>
            </section>
        </div>

        <section>
            <div class="section-title">Kelengkapan dan Catatan</div>
            <table class="data-table">
                <tr><th>Kelengkapan</th><td>{{ count($bast->accessories ?? []) ? implode(', ', $bast->accessories) : '-' }}</td></tr>
                <tr><th>Catatan</th><td>{!! nl2br(e($bast->notes ?: '-')) !!}</td></tr>
                @if (count($photos))
                    <tr><th>Dokumentasi</th><td>{{ count($photos) }} foto tersimpan di sistem.</td></tr>
                @endif
            </table>
        </section>

        <section>
            <div class="section-title">Pernyataan Tanggung Jawab Asset</div>
            <div class="responsibility">
                <p>Dengan menandatangani berita acara ini, penerima menyetujui ketentuan berikut:</p>
                <ol>
                    <li>Asset digunakan untuk kebutuhan pekerjaan dan wajib dijaga dengan baik.</li>
                    <li>Pemindahan, peminjaman, atau perubahan konfigurasi asset harus mendapat persetujuan IT.</li>
                    <li>Kerusakan, kehilangan, atau perubahan pengguna wajib segera dilaporkan kepada IT.</li>
                    <li>Asset dikembalikan kepada IT apabila sudah tidak digunakan atau saat diminta oleh perusahaan.</li>
                </ol>
            </div>
        </section>

        <div class="signature-grid">
            <div class="signature">
                <div>Diserahkan oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $bast->creator?->name ?? 'IT Admin' }}</div>
            </div>
            <div class="signature">
                <div>Diterima oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $bast->recipient_name }}</div>
            </div>
        </div>

        <footer class="footer">
            <span>{{ $appName }} - IT Asset Management</span>
            <span>Generated {{ now()->format('d M Y H:i') }}</span>
        </footer>
    </main>
</body>
</html>
