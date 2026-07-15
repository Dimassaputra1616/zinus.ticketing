@php
    $assetCode = filled($asset->asset_code) ? $asset->asset_code : 'ASSET-' . $asset->id;
    $assetTitle = filled($asset->name) ? $asset->name : $assetCode;
    $category = filled($asset->category) ? $asset->category : 'Asset';
    $department = $asset->department?->name ?: 'No department';
    $location = $asset->location ?: $asset->factory ?: 'No location';
    $assignedTo = $asset->assigned_to_display_name ?: 'Not assigned';
    $serial = $asset->serial_number ?: 'No serial';
    $identity = $asset->hostname ?: $asset->asset_code ?: $serial;
    $statusKey = strtolower((string) ($asset->lifecycle_status ?: $asset->status ?: 'active'));
    $statusLabel = [
        'active' => 'Active',
        'assigned' => 'Assigned',
        'in_use' => 'Active',
        'available' => 'Spare',
        'spare' => 'Spare',
        'maintenance' => 'In Repair',
        'in_repair' => 'In Repair',
        'broken' => 'Retired',
        'retired' => 'Retired',
        'disposed' => 'Disposed',
        'lost' => 'Lost',
        'replaced' => 'Replaced',
    ][$statusKey] ?? \Illuminate\Support\Str::of($statusKey)->replace('_', ' ')->title();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Label - {{ $assetCode }}</title>
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
            --paper: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 18% 12%, rgba(14, 165, 233, 0.14), transparent 28%),
                radial-gradient(circle at 85% 22%, rgba(16, 185, 129, 0.18), transparent 30%),
                linear-gradient(135deg, #eef8f4 0%, #f8fafc 46%, #edf4ff 100%);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .toolbar {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 10;
            display: flex;
            gap: 10px;
        }

        .toolbar a,
        .toolbar button {
            height: 40px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.92);
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
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        }

        .sheet {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 86px 24px 48px;
        }

        .label {
            position: relative;
            width: min(920px, 100%);
            min-height: 560px;
            overflow: hidden;
            border: 1px solid rgba(6, 78, 59, 0.18);
            border-radius: 24px;
            background:
                linear-gradient(90deg, rgba(4, 120, 87, 0.08) 1px, transparent 1px) 0 0 / 34px 34px,
                linear-gradient(0deg, rgba(14, 165, 233, 0.07) 1px, transparent 1px) 0 0 / 34px 34px,
                #ffffff;
            box-shadow: 0 26px 70px rgba(15, 23, 42, 0.18);
        }

        .label::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 16px;
            background: linear-gradient(180deg, var(--brand-dark), var(--brand), var(--sky));
        }

        .label::after {
            content: "VERIFY";
            position: absolute;
            right: -36px;
            bottom: 42px;
            color: rgba(4, 120, 87, 0.08);
            font-size: 76px;
            font-weight: 950;
            letter-spacing: 10px;
            transform: rotate(-90deg);
        }

        .label-inner {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(280px, 0.82fr) minmax(0, 1.18fr);
            gap: 28px;
            padding: 34px 36px 32px 52px;
        }

        .qr-panel {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-lockup img {
            height: 38px;
            width: 38px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 8px 22px rgba(4, 120, 87, 0.2);
        }

        .brand-kicker,
        .eyebrow,
        .meta-label {
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .brand-name {
            margin-top: 3px;
            color: var(--brand-dark);
            font-size: 15px;
            font-weight: 950;
            letter-spacing: 0.08em;
        }

        .status-pill {
            border: 1px solid rgba(4, 120, 87, 0.18);
            border-radius: 999px;
            background: #ecfdf5;
            color: var(--brand-dark);
            padding: 7px 11px;
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .qr-stage {
            position: relative;
            display: grid;
            place-items: center;
            width: 100%;
            aspect-ratio: 1;
            border: 1px solid rgba(6, 78, 59, 0.16);
            border-radius: 22px;
            background:
                linear-gradient(135deg, rgba(167, 243, 208, 0.5), rgba(255, 255, 255, 0.82)),
                #ffffff;
            padding: 16px;
            box-shadow: inset 0 0 0 8px rgba(236, 253, 245, 0.9);
        }

        .qr-stage svg {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 14px;
        }

        .qr-mark {
            position: absolute;
            left: 50%;
            top: 50%;
            display: grid;
            height: 54px;
            width: 54px;
            place-items: center;
            border: 6px solid #ffffff;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
            transform: translate(-50%, -50%);
        }

        .qr-mark img {
            height: 36px;
            width: 36px;
            border-radius: 9px;
            object-fit: cover;
        }

        .scan-url {
            overflow: hidden;
            color: var(--muted);
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 10px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .content-panel {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
        }

        .asset-code {
            margin: 10px 0 0;
            color: var(--brand-dark);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: clamp(34px, 5vw, 58px);
            font-weight: 950;
            letter-spacing: 0.02em;
            line-height: 0.95;
            overflow-wrap: anywhere;
        }

        .asset-title {
            margin: 14px 0 0;
            color: var(--ink);
            font-size: clamp(22px, 3vw, 34px);
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.08;
            overflow-wrap: anywhere;
        }

        .category {
            margin-top: 10px;
            color: var(--muted);
            font-size: 15px;
            font-weight: 800;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .meta-item {
            min-width: 0;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(248, 250, 252, 0.78);
            padding: 12px;
        }

        .meta-value {
            margin-top: 5px;
            overflow: hidden;
            color: var(--ink);
            font-size: 14px;
            font-weight: 850;
            line-height: 1.25;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            border-top: 1px solid var(--line);
            padding-top: 18px;
        }

        .footer-code {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--brand-dark);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .footer-code span {
            display: inline-block;
            height: 10px;
            width: 10px;
            border-radius: 50%;
            background: var(--sky);
            box-shadow: 18px 0 0 var(--brand), 36px 0 0 var(--mint);
        }

        .printed-at {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-align: right;
        }

        .print-sheet {
            display: none;
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
                height: 29mm !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }

            body {
                background: #ffffff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .toolbar,
            .sheet {
                display: none !important;
            }

            .print-sheet {
                display: block !important;
                width: 60mm;
                height: 27mm;
                margin: 0.8mm 1mm;
                padding: 0;
                overflow: hidden;
                background: #ffffff;
                break-after: avoid;
                break-before: avoid;
                break-inside: avoid;
                page-break-after: avoid;
                page-break-before: avoid;
                page-break-inside: avoid;
            }

            .print-sticker {
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

            .print-sticker::before {
                content: "";
                position: absolute;
                inset: 0 auto 0 0;
                width: 1.6mm;
                background: linear-gradient(180deg, var(--brand-dark), var(--brand), var(--sky));
            }

            .print-inner {
                position: relative;
                z-index: 1;
                display: grid;
                grid-template-columns: 23.5mm minmax(0, 1fr);
                gap: 1.7mm;
                width: 100%;
                height: 100%;
                padding: 1.8mm 2mm 1.7mm 3.3mm;
            }

            .print-left {
                min-width: 0;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
            }

            .print-qr-box {
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

            .print-qr-box svg {
                display: block;
                width: 100%;
                height: 100%;
            }

            .print-mark {
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

            .print-mark img {
                width: 3.3mm;
                height: 3.3mm;
                border-radius: 0.8mm;
                object-fit: cover;
            }

            .print-url {
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

            .print-right {
                min-width: 0;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 0.55mm;
                height: 100%;
            }

            .print-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1mm;
                min-width: 0;
            }

            .print-brand {
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 0.8mm;
            }

            .print-brand img {
                width: 4.8mm;
                height: 4.8mm;
                border-radius: 1mm;
                object-fit: cover;
            }

            .print-kicker {
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

            .print-company {
                margin-top: 0.25mm;
                color: var(--brand-dark);
                font-size: 4pt;
                font-weight: 950;
                letter-spacing: 0.03em;
                line-height: 1;
                white-space: nowrap;
            }

            .print-status {
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

            .print-code {
                overflow: hidden;
                color: var(--brand-dark);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                font-size: 9.4pt;
                font-weight: 950;
                line-height: 1;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .print-title {
                display: -webkit-box;
                overflow: hidden;
                color: var(--ink);
                font-size: 4.7pt;
                font-weight: 850;
                line-height: 1.08;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
            }

            .print-meta {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.7mm;
            }

            .print-meta-item {
                min-width: 0;
                overflow: hidden;
                border: 0.14mm solid var(--line);
                border-radius: 1mm;
                background: rgba(248, 250, 252, 0.78);
                padding: 0.45mm 0.65mm;
            }

            .print-meta-label {
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

            .print-meta-value {
                overflow: hidden;
                margin-top: 0.3mm;
                color: var(--ink);
                font-size: 3.7pt;
                font-weight: 850;
                line-height: 1;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .print-footer {
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

            .print-verify {
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
        }

        @media (max-width: 760px) {
            .sheet {
                padding-top: 76px;
            }

            .label-inner {
                grid-template-columns: 1fr;
                padding: 30px;
            }

            .label::before {
                width: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('assets.show', $asset) }}">Open Asset</a>
        <button type="button" onclick="window.print()">Print Label</button>
    </div>

    <main class="sheet">
        <section class="label" aria-label="Zinus asset QR label">
            <div class="label-inner">
                <div class="qr-panel">
                    <div class="brand-row">
                        <div class="brand-lockup">
                            <img src="{{ asset('images/logo.png') }}" alt="Zinus">
                            <div>
                                <div class="brand-kicker">IT Support Center</div>
                                <div class="brand-name">ZINUS DREAM</div>
                            </div>
                        </div>
                        <div class="status-pill">{{ $statusLabel }}</div>
                    </div>

                    <div class="qr-stage">
                        {!! $qrSvg !!}
                        <div class="qr-mark" aria-hidden="true">
                            <img src="{{ asset('images/logo.png') }}" alt="">
                        </div>
                    </div>

                    <span class="scan-url">{{ $qrTargetUrl }}</span>
                </div>

                <div class="content-panel">
                    <div>
                        <div class="eyebrow">Zinus Asset Passport</div>
                        <h1 class="asset-code">{{ $assetCode }}</h1>
                        <p class="asset-title">{{ $assetTitle }}</p>
                        <p class="category">{{ $category }}</p>
                    </div>

                    <div class="meta-grid">
                        <div class="meta-item">
                            <div class="meta-label">Identity</div>
                            <div class="meta-value" title="{{ $identity }}">{{ $identity }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Serial</div>
                            <div class="meta-value" title="{{ $serial }}">{{ $serial }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Department</div>
                            <div class="meta-value" title="{{ $department }}">{{ $department }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Assigned To</div>
                            <div class="meta-value" title="{{ $assignedTo }}">{{ $assignedTo }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Location</div>
                            <div class="meta-value" title="{{ $location }}">{{ $location }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Updated</div>
                            <div class="meta-value">{{ optional($asset->updated_at)->format('d M Y') ?: 'Not set' }}</div>
                        </div>
                    </div>

                    <div class="footer">
                        <div class="footer-code"><span></span>Scan To Verify</div>
                        <div class="printed-at">Printed {{ now()->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <section class="print-sheet" aria-label="Printable Zinus asset QR label">
        <div class="print-sticker">
            <div class="print-inner">
                <div class="print-left">
                    <div class="print-qr-box">
                        {!! $qrSvg !!}
                        <div class="print-mark" aria-hidden="true">
                            <img src="{{ asset('images/logo.png') }}" alt="">
                        </div>
                    </div>
                    <span class="print-url">{{ $qrTargetUrl }}</span>
                </div>

                <div class="print-right">
                    <div class="print-head">
                        <div class="print-brand">
                            <img src="{{ asset('images/logo.png') }}" alt="Zinus">
                            <div>
                                <div class="print-kicker">IT Support Center</div>
                                <div class="print-company">ZINUS DREAM</div>
                            </div>
                        </div>
                        <div class="print-status">{{ $statusLabel }}</div>
                    </div>

                    <div>
                        <div class="print-code">{{ $assetCode }}</div>
                        <div class="print-title">{{ $assetTitle }}</div>
                    </div>

                    <div class="print-meta">
                        <div class="print-meta-item">
                            <div class="print-meta-label">Dept</div>
                            <div class="print-meta-value" title="{{ $department }}">{{ $department }}</div>
                        </div>
                        <div class="print-meta-item">
                            <div class="print-meta-label">Owner</div>
                            <div class="print-meta-value" title="{{ $assignedTo }}">{{ $assignedTo }}</div>
                        </div>
                    </div>

                    <div class="print-footer">
                        <span class="print-verify">Scan To Verify</span>
                        <span class="print-date">{{ now()->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
