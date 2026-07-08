<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Reset Password Portal IT Zinus</title>
</head>
<body style="margin:0;padding:0;background:#eef7f3;color:#10251f;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Kode reset password untuk akun Portal IT Zinus Anda. Kode berlaku {{ $expiresIn }} menit.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background:#eef7f3;margin:0;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;margin:0 auto;">
                    <tr>
                        <td style="padding:0 0 18px;text-align:center;">
                            <img src="{{ $logoUrl }}" alt="Zinus Dream" width="46" height="46" style="display:inline-block;width:46px;height:46px;border-radius:14px;vertical-align:middle;margin-right:10px;">
                            <span style="display:inline-block;vertical-align:middle;text-align:left;">
                                <span style="display:block;font-size:11px;line-height:14px;letter-spacing:3px;text-transform:uppercase;color:#119267;font-weight:800;">Zinus Dream</span>
                                <span style="display:block;font-size:18px;line-height:24px;color:#123c32;font-weight:900;">IT Support Portal</span>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="border-radius:30px;overflow:hidden;background:#ffffff;box-shadow:0 28px 70px rgba(15,109,63,.16);border:1px solid #d9eee5;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:34px 36px 30px;background:linear-gradient(135deg,#0b3b2f 0%,#11865b 52%,#7be6b6 100%);">
                                        <span style="display:inline-block;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.16);color:#d9fff0;font-size:11px;line-height:14px;font-weight:900;letter-spacing:2px;text-transform:uppercase;border:1px solid rgba(255,255,255,.26);">Verification Code</span>
                                        <h1 style="margin:18px 0 10px;color:#ffffff;font-size:32px;line-height:39px;font-weight:900;letter-spacing:-.8px;">Kode reset password</h1>
                                        <p style="margin:0;color:#d9fff0;font-size:15px;line-height:24px;">Halo {{ $displayName }}, gunakan kode berikut untuk mengganti password akun Portal IT Anda.</p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:34px 36px 8px;">
                                        <p style="margin:0 0 22px;color:#425466;font-size:15px;line-height:25px;">Masukkan kode ini di halaman reset password. Demi keamanan, kode hanya aktif selama <strong style="color:#123c32;">{{ $expiresIn }} menit</strong>.</p>

                                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 26px;">
                                            <tr>
                                                <td align="center" bgcolor="#101827" style="border-radius:18px;box-shadow:0 14px 30px rgba(16,24,39,.22);">
                                                    <div style="display:inline-block;padding:18px 28px;border-radius:18px;background:#101827;color:#ffffff;text-decoration:none;font-size:28px;line-height:32px;font-weight:900;letter-spacing:8px;">{{ $code }}</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background:#f6fbf8;border:1px solid #dcefe6;border-radius:20px;">
                                            <tr>
                                                <td style="padding:18px 20px;">
                                                    <p style="margin:0 0 6px;color:#0f6d3f;font-size:12px;line-height:16px;font-weight:900;letter-spacing:1.5px;text-transform:uppercase;">Catatan keamanan</p>
                                                    <p style="margin:0;color:#425466;font-size:14px;line-height:23px;">Jika Anda tidak meminta reset password, abaikan email ini. Password lama tidak akan berubah tanpa kode verifikasi ini.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:18px 36px 34px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #edf2f7;">
                                            <tr>
                                                <td style="padding-top:20px;color:#7b8c9d;font-size:12px;line-height:20px;">
                                                    Email ini dikirim otomatis oleh {{ $appName }} untuk <strong style="color:#506172;">{{ $email }}</strong>.<br>
                                                    Butuh bantuan? Hubungi <a href="mailto:{{ $supportEmail }}" style="color:#0f6d3f;text-decoration:none;font-weight:800;">{{ $supportEmail }}</a>.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 10px 0;text-align:center;color:#8aa096;font-size:12px;line-height:19px;">
                            © {{ date('Y') }} {{ $appName }}. Portal layanan IT internal Zinus Dream.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
