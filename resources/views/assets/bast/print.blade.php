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
    $brandColor = setting('theme_color', '#12824C');
    $brandStrong = setting('theme_color_strong', '#0F6D3F');
    $brandSoft = setting('theme_color_secondary', '#53B77A');
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
            border-bottom: 1.5px solid #1e293b;
            margin-top: 24px;
            padding: 0 0 12px;
            text-align: center;
        }
        h1 {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.06em;
            line-height: 1.25;
            margin: 0;
            text-transform: uppercase;
        }
        .document-number {
            color: #334155;
            font-size: 12px;
            margin-top: 7px;
        }
        .document-number strong {
            color: #0f172a;
        }
        .statement {
            color: #1e293b;
            line-height: 1.75;
            margin: 18px 0 16px;
            text-align: justify;
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
        .photo-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .photo-grid.one-photo {
            grid-template-columns: minmax(0, 96mm);
        }
        .photo-item {
            border: 1px solid #cbd5e1;
            border-radius: 2px;
            overflow: hidden;
        }
        .photo-item img {
            aspect-ratio: 4 / 3;
            display: block;
            object-fit: cover;
            width: 100%;
        }
        .photo-caption {
            background: #fff;
            border-top: 1px solid #cbd5e1;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
            overflow: hidden;
            padding: 5px 7px;
            text-align: center;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .responsibility-card {
            background: #f8fbfa;
            border: 1px solid var(--line);
            border-radius: 8px;
            color: #334155;
            font-weight: 700;
            line-height: 1.7;
            padding: 12px 14px;
        }
        .responsibility-card p {
            margin: 0;
        }
        .responsibility-card p + p {
            margin-top: 7px;
        }
        .responsibility-card ol {
            margin: 7px 0 0;
            padding-left: 18px;
        }
        .responsibility-card li + li {
            margin-top: 4px;
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
                <div class="document-number">
                    Nomor: <strong>{{ $bast->document_number }}</strong>
                </div>
            </section>

            <p class="statement">
                Pada tanggal {{ optional($bast->bast_date)->format('d M Y') }}, pihak IT dan
                <strong>{{ $bast->recipient_name }}</strong> telah melakukan proses
                <strong>{{ strtolower($typeLabel) }}</strong> asset IT. Asset diterima sesuai data dan kondisi yang tercantum dalam berita acara ini untuk digunakan sebagai fasilitas kerja.
            </p>

            <div class="two-columns">
                <section class="section">
                    <div class="section-header">
                        <span class="section-dot"></span>
                        <span class="section-title">Data Asset</span>
                    </div>
                    <table class="data-table">
                        <tr><th>Kode Asset</th><td>{{ $snapshot['asset_code'] ?? $bast->asset?->asset_code ?? '-' }}</td></tr>
                        <tr><th>Nama Asset</th><td>{{ $snapshot['name'] ?? $bast->asset?->name ?? '-' }}</td></tr>
                        <tr><th>Kategori</th><td>{{ $snapshot['category'] ?? $bast->asset?->category ?? '-' }}</td></tr>
                        <tr><th>Serial Number</th><td>{{ $snapshot['serial_number'] ?? $bast->asset?->serial_number ?? '-' }}</td></tr>
                        <tr><th>Hostname</th><td>{{ $snapshot['hostname'] ?? $bast->asset?->hostname ?? '-' }}</td></tr>
                        <tr><th>Merek / Model</th><td>{{ trim(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?: '-' }}</td></tr>
                    </table>
                </section>

                <section class="section">
                    <div class="section-header">
                        <span class="section-dot"></span>
                        <span class="section-title">Penerima</span>
                    </div>
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

            <section class="section">
                <div class="section-header">
                    <span class="section-dot"></span>
                    <span class="section-title">Kelengkapan dan Catatan</span>
                </div>
                <table class="data-table">
                    <tr><th>Kelengkapan</th><td>{{ count($bast->accessories ?? []) ? implode(', ', $bast->accessories) : '-' }}</td></tr>
                    <tr><th>Catatan</th><td>{!! nl2br(e($bast->notes ?: '-')) !!}</td></tr>
                </table>
            </section>

            <section class="section">
                <div class="section-header">
                    <span class="section-dot"></span>
                    <span class="section-title">Pernyataan Tanggung Jawab Asset</span>
                </div>
                <div class="responsibility-card">
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

            @if (count($photos))
                <section class="section">
                    <div class="section-header">
                        <span class="section-dot"></span>
                        <span class="section-title">Lampiran Foto Asset</span>
                    </div>
                    <div class="photo-grid {{ count($photos) === 1 ? 'one-photo' : '' }}">
                        @foreach ($photos as $photo)
                            @php $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($photo['path']); @endphp
                            <div class="photo-item">
                                <img src="{{ $photoUrl }}" alt="Foto dokumentasi asset {{ $loop->iteration }}">
                                <div class="photo-caption">Foto {{ $loop->iteration }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <footer class="footer">
                <span>{{ $appName }} - IT Asset Management</span>
                <span>Generated {{ now()->format('d M Y H:i') }}</span>
            </footer>
        </div>
    </main>
</body>
</html>
