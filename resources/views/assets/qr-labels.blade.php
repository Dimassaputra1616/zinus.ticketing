@php
    $labelCount = $labels->count();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $printTitle }} - {{ $labelCount }} Labels</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #d7e4df;
            --brand: #047857;
            --brand-dark: #064e3b;
            --mint: #a7f3d0;
            --sky: #0ea5e9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 12% 8%, rgba(14, 165, 233, 0.14), transparent 28%),
                radial-gradient(circle at 86% 18%, rgba(16, 185, 129, 0.16), transparent 30%),
                linear-gradient(135deg, #eef8f4 0%, #f8fafc 46%, #edf4ff 100%);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.1);
            background: rgba(255, 255, 255, 0.92);
            padding: 16px 24px;
            backdrop-filter: blur(16px);
        }

        .toolbar h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .toolbar p {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .toolbar a,
        .toolbar button {
            height: 40px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 10px;
            background: #ffffff;
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.1);
        }

        .toolbar button {
            border-color: rgba(4, 120, 87, 0.24);
            background: var(--brand);
            color: #ffffff;
        }

        .preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 22px;
            padding: 28px;
        }

        .empty-state {
            margin: 80px auto;
            max-width: 460px;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 12px;
            background: #ffffff;
            padding: 28px;
            text-align: center;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }

        .empty-state h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
        }

        .empty-state p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 650;
            line-height: 1.5;
        }

        .label-page {
            display: flex;
            width: 62mm;
            height: 29mm;
            margin: 0 auto;
            padding: 0.8mm 1mm;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
        }

        .sticker {
            position: relative;
            width: 60mm;
            height: 27mm;
            overflow: hidden;
            border: 0.25mm solid rgba(6, 78, 59, 0.24);
            border-radius: 2.2mm;
            background:
                linear-gradient(90deg, rgba(4, 120, 87, 0.1) 0.12mm, transparent 0.12mm) 0 0 / 4.2mm 4.2mm,
                linear-gradient(0deg, rgba(14, 165, 233, 0.1) 0.12mm, transparent 0.12mm) 0 0 / 4.2mm 4.2mm,
                #ffffff;
        }

        .sticker::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 1.6mm;
            background: linear-gradient(180deg, var(--brand-dark), var(--brand), var(--sky));
        }

        .sticker-inner {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 23.5mm minmax(0, 1fr);
            gap: 1.7mm;
            width: 100%;
            height: 100%;
            padding: 1.8mm 2mm 1.7mm 3.3mm;
        }

        .sticker-left {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .qr-box {
            position: relative;
            display: grid;
            place-items: center;
            width: 21.8mm;
            height: 21.8mm;
            border: 0.18mm solid rgba(6, 78, 59, 0.16);
            border-radius: 2.4mm;
            background:
                linear-gradient(135deg, rgba(167, 243, 208, 0.58), rgba(255, 255, 255, 0.92)),
                #ffffff;
            padding: 1.2mm;
        }

        .qr-box svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .qr-mark {
            position: absolute;
            left: 50%;
            top: 50%;
            display: grid;
            width: 5mm;
            height: 5mm;
            place-items: center;
            border: 0.85mm solid #ffffff;
            border-radius: 1.25mm;
            background: #ffffff;
            transform: translate(-50%, -50%);
        }

        .qr-mark img {
            width: 3.3mm;
            height: 3.3mm;
            border-radius: 0.8mm;
            object-fit: cover;
        }

        .scan-url {
            overflow: hidden;
            width: 22.5mm;
            color: var(--muted);
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 3pt;
            font-weight: 750;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sticker-right {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.55mm;
            height: 100%;
        }

        .sticker-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1mm;
            min-width: 0;
        }

        .brand-lockup {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 0.8mm;
        }

        .brand-lockup img {
            width: 4.8mm;
            height: 4.8mm;
            border-radius: 1mm;
            object-fit: cover;
        }

        .brand-kicker {
            overflow: hidden;
            color: var(--muted);
            font-size: 3pt;
            font-weight: 950;
            letter-spacing: 0.05em;
            line-height: 1;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .brand-name {
            margin-top: 0.25mm;
            color: var(--brand-dark);
            font-size: 4pt;
            font-weight: 950;
            letter-spacing: 0.03em;
            line-height: 1;
            white-space: nowrap;
        }

        .status-pill {
            flex: 0 0 auto;
            max-width: 13mm;
            overflow: hidden;
            border: 0.16mm solid rgba(4, 120, 87, 0.22);
            border-radius: 999px;
            background: #ecfdf5;
            color: var(--brand-dark);
            padding: 0.55mm 0.9mm;
            font-size: 3.2pt;
            font-weight: 950;
            line-height: 1;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .asset-code {
            overflow: hidden;
            color: var(--brand-dark);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 9.4pt;
            font-weight: 950;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .asset-title {
            display: -webkit-box;
            overflow: hidden;
            color: var(--ink);
            font-size: 4.7pt;
            font-weight: 850;
            line-height: 1.08;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7mm;
        }

        .meta-item {
            min-width: 0;
            overflow: hidden;
            border: 0.14mm solid var(--line);
            border-radius: 1mm;
            background: rgba(248, 250, 252, 0.78);
            padding: 0.45mm 0.65mm;
        }

        .meta-label {
            overflow: hidden;
            color: var(--muted);
            font-size: 2.75pt;
            font-weight: 950;
            letter-spacing: 0.04em;
            line-height: 1;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .meta-value {
            overflow: hidden;
            margin-top: 0.3mm;
            color: var(--ink);
            font-size: 3.7pt;
            font-weight: 850;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sticker-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1mm;
            border-top: 0.14mm solid var(--line);
            padding-top: 0.55mm;
            color: var(--muted);
            font-size: 3pt;
            font-weight: 850;
            line-height: 1;
        }

        .verify-text {
            color: var(--brand-dark);
            font-weight: 950;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .print-date {
            overflow: hidden;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @page {
            size: 62mm 29mm;
            margin: 0;
        }

        @media print {
            * {
                box-shadow: none !important;
            }

            html,
            body {
                width: 62mm !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                background: #ffffff !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .toolbar,
            .empty-state {
                display: none !important;
            }

            .preview {
                display: block;
                padding: 0;
            }

            .label-page {
                width: 62mm;
                height: 29mm;
                margin: 0;
                padding: 0.8mm 1mm;
                overflow: hidden;
                break-after: page;
                break-before: auto;
                break-inside: avoid;
                page-break-after: always;
                page-break-before: auto;
                page-break-inside: avoid;
            }

            .label-page:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <header class="toolbar">
        <div>
            <h1>{{ $printTitle }}</h1>
            <p>{{ number_format($labelCount) }} {{ Str::plural('label', $labelCount) }} ready to print</p>
        </div>
        <div class="toolbar-actions">
            <a href="{{ url()->previous() }}">Back</a>
            <button type="button" onclick="window.print()">Print Labels</button>
        </div>
    </header>

    @if ($labels->isEmpty())
        <section class="empty-state">
            <h2>No labels to print</h2>
            <p>No asset matched the current selection or filters. Go back to inventory and adjust the filters.</p>
        </section>
    @else
        <main class="preview" aria-label="Bulk QR label preview">
            @foreach ($labels as $label)
                <section class="label-page" aria-label="QR label for {{ $label['assetCode'] }}">
                    <div class="sticker">
                        <div class="sticker-inner">
                            <div class="sticker-left">
                                <div class="qr-box">
                                    {!! $label['qrSvg'] !!}
                                    <div class="qr-mark" aria-hidden="true">
                                        <img src="{{ asset('images/logo.png') }}" alt="">
                                    </div>
                                </div>
                                <span class="scan-url">{{ $label['qrTargetUrl'] }}</span>
                            </div>

                            <div class="sticker-right">
                                <div class="sticker-head">
                                    <div class="brand-lockup">
                                        <img src="{{ asset('images/logo.png') }}" alt="Zinus">
                                        <div>
                                            <div class="brand-kicker">IT Support Center</div>
                                            <div class="brand-name">ZINUS DREAM</div>
                                        </div>
                                    </div>
                                    <div class="status-pill">{{ $label['statusLabel'] }}</div>
                                </div>

                                <div>
                                    <div class="asset-code">{{ $label['assetCode'] }}</div>
                                    <div class="asset-title">{{ $label['assetTitle'] }}</div>
                                </div>

                                <div class="meta-grid">
                                    <div class="meta-item">
                                        <div class="meta-label">Dept</div>
                                        <div class="meta-value" title="{{ $label['department'] }}">{{ $label['department'] }}</div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Owner</div>
                                        <div class="meta-value" title="{{ $label['assignedTo'] }}">{{ $label['assignedTo'] }}</div>
                                    </div>
                                </div>

                                <div class="sticker-footer">
                                    <span class="verify-text">Scan To Verify</span>
                                    <span class="print-date">{{ now()->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </main>
    @endif
</body>
</html>
