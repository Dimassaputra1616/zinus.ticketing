<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ticket Baru</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f5f7fa;padding:28px 0 38px;">
        <tr>
            <td align="center">
                <div style="max-width:720px;width:100%;background:#ffffff;border-radius:14px;box-shadow:0 18px 40px rgba(15,23,42,0.08);overflow:hidden;border:1px solid #e5e7eb;">
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                        <tr>
                            <td style="padding:18px 22px;background:#0A6533;color:#ffffff;border-bottom:1px solid #0A6533;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="width:48px;vertical-align:middle;padding-left:18px;">
                                            <img src="{{ $message->embed(public_path('images/logo-email.png')) }}" alt="Zinus Logo" style="width:32px;height:32px;border-radius:6px;display:block;object-fit:cover;" />
                                        </td>
                                        <td style="text-align:right;font-size:13px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;vertical-align:middle;">IT Support Zinus</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div style="padding:26px 26px 12px;">
                        <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#0A6533;font-weight:800;">Ringkasan Tiket</p>
                        <h1 style="margin:0 0 6px;font-size:22px;line-height:1.35;color:#0A6533;">Ticket #{{ $ticket->id }} — {{ \Illuminate\Support\Str::title($ticket->title) }}</h1>
                        <p style="margin:0 0 8px;color:#334155;font-size:14px;">Dibuat oleh <strong>{{ $actorName }}</strong>. Mohon ditindaklanjuti.</p>
                        <p style="margin:0 0 18px;font-size:12px;color:#64748b;">Dibuat pada {{ $ticket->created_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>

                    <div style="padding:0 26px 12px;">
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                <tr>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Kategori</td>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Departemen</td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:0 8px 10px;font-size:14px;color:#0f172a;font-weight:700;">{{ $categoryName }}</td>
                                    <td width="50%" style="padding:0 8px 10px;font-size:14px;color:#0f172a;font-weight:700;">{{ $departmentName }}</td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Prioritas</td>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Status</td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:6px 8px 4px;">
                                        <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(10,101,51,0.1);color:#0A6533;font-size:12px;font-weight:800;">{{ $priorityLabel }}</span>
                                    </td>
                                    <td width="50%" style="padding:6px 8px 4px;">
                                        <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(10,101,51,0.1);color:#0A6533;font-size:12px;font-weight:800;">{{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'Open')) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding:10px 6px 0;">
                                        <div style="height:1px;background:linear-gradient(90deg, rgba(15,23,42,0.06), rgba(15,23,42,0.02));border-radius:999px;"></div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div style="padding:0 26px 18px;">
                        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:20px 20px;background:#ffffff;box-shadow:0 8px 18px rgba(15,23,42,0.04);">
                            <div style="font-size:13px;font-weight:800;color:#0A6533;margin-bottom:8px;">Deskripsi</div>
                            <p style="margin:0;color:#475569;line-height:1.6;font-size:14px;">{{ $descriptionPreview }}</p>
                        </div>
                    </div>

                    <div style="padding:0 26px 20px;text-align:center;margin-top:12px;">
                        <a href="{{ $ticketUrl }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#0A6533;color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;box-shadow:0 12px 22px rgba(10,101,51,0.18);">
                            Lihat Detail Tiket <span style="display:inline-block;margin-left:8px;">→</span>
                        </a>
                    </div>

                    <div style="padding:0 26px 26px;">
                        <p style="margin:0;font-size:12px;color:#94a3b8;text-align:center;line-height:1.5;border-top:1px solid #e5e7eb;padding-top:14px;">
                            Hubungi IT: it.support@zinus.com — Jangan balas email ini karena dikirim otomatis.
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
