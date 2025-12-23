<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembaruan Status Tiket</title>
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
                        <h1 style="margin:0 0 8px;font-size:24px;line-height:1.35;color:#0A6533;">Ticket #{{ $ticket->id }} — {{ \Illuminate\Support\Str::title($ticket->title) }}</h1>
                        <p style="margin:0 0 8px;color:#0A6533;font-size:14px;font-weight:700;">Status tiket Anda telah diperbarui dari {{ $oldStatusLabel }} → {{ $newStatusLabel }}</p>
                        <p style="margin:0 0 18px;font-size:12px;color:#64748b;">Dibuat pada {{ $ticket->created_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>

                    <div style="padding:0 26px 12px;">
                        <div style="margin:0 0 18px;padding:14px 16px;border:1px solid #dbe9e1;border-radius:12px;background:rgba(10,101,51,0.05);">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;">Status Lama</td>
                                    <td style="width:52px;text-align:center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="18" viewBox="0 0 26 18" fill="none" style="display:block;margin:0 auto;">
                                            <path d="M4 9h17" stroke="#0A6533" stroke-width="2.5" stroke-linecap="round" />
                                            <path d="M15 4l6 5-6 5" stroke="#0A6533" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </td>
                                    <td style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;">Status Baru</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;font-size:14px;font-weight:800;">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:10px;background:#0b7a3d;color:#ffffff;">{{ $oldStatusLabel }}</span>
                                    </td>
                                    <td style="text-align:center;">&nbsp;</td>
                                    <td style="padding:6px 8px;font-size:14px;font-weight:800;">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:10px;background:#0A6533;color:#ffffff;">{{ $newStatusLabel }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 8px 4px;font-size:12px;color:#64748b;font-weight:700;">Diperbarui oleh</td>
                                    <td></td>
                                    <td style="padding:10px 8px 4px;font-size:12px;color:#64748b;font-weight:700;">Waktu update</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 8px 4px;font-size:14px;color:#0f172a;font-weight:700;">{{ $adminName }}</td>
                                    <td></td>
                                    <td style="padding:0 8px 4px;font-size:14px;color:#0f172a;font-weight:700;">{{ $updatedAt }}</td>
                                </tr>
                            </table>
                        </div>

                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;">
                                <tr>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Kategori</td>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Departemen</td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:0 8px 10px;font-size:14px;color:#0f172a;font-weight:700;">{{ $ticket->category?->name ?? 'Tidak ada kategori' }}</td>
                                    <td width="50%" style="padding:0 8px 10px;font-size:14px;color:#0f172a;font-weight:700;">{{ $ticket->department?->name ?? 'Tidak ada departemen' }}</td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Prioritas</td>
                                    <td width="50%" style="padding:6px 8px;font-size:12px;color:#64748b;font-weight:700;">Status</td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:6px 8px 4px;">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:12px;background:#0b7a3d;color:#ffffff;font-size:12px;font-weight:800;">{{ ucfirst(str_replace('_', ' ', $ticket->priority ?? 'Tidak ditentukan')) }}</span>
                                    </td>
                                    <td width="50%" style="padding:6px 8px 4px;">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:12px;background:#0A6533;color:#ffffff;font-size:12px;font-weight:800;">{{ $newStatusLabel }}</span>
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

                    <div style="padding:0 26px 16px;">
                        <div style="border-top:1px solid #e5e7eb;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;background:#ffffff;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
                            <div style="font-size:13px;font-weight:800;color:#0A6533;margin-bottom:6px;">Deskripsi</div>
                            <p style="margin:0;color:#475569;line-height:1.55;font-size:14px;">{{ $ticket->description ?? 'Tidak ada deskripsi tambahan.' }}</p>
                        </div>
                    </div>

                    <div style="padding:0 26px 20px;text-align:center;margin-top:14px;">
                        <a href="{{ $ticketUrl }}" style="display:inline-block;padding:15px 30px;border-radius:999px;background:#0A6533;color:#ffffff;text-decoration:none;font-weight:900;font-size:15px;box-shadow:0 12px 22px rgba(10,101,51,0.18);">
                            Lihat Detail Tiket <span style="display:inline-block;margin-left:8px;">→</span>
                        </a>
                    </div>

                    <div style="padding:0 26px 26px;">
                        <div style="height:1px;background:linear-gradient(90deg, rgba(15,23,42,0.06), rgba(15,23,42,0.02));border-radius:999px;margin:2px 0 12px;"></div>
                        <p style="margin:0;font-size:12px;color:#94a3b8;text-align:center;line-height:1.55;border-top:1px solid #e5e7eb;padding-top:14px;">
                            Email ini dikirim otomatis oleh Sistem IT Helpdesk Zinus. Untuk bantuan, hubungi it.ticketing@gmail.com.
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
