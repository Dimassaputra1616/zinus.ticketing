# Materi Presentasi - Zinus Ticketing

Dokumen ini siap dipakai sebagai outline slide dan script presentasi.

## 1. Tujuan Presentasi

Menjelaskan bagaimana Zinus Ticketing membantu tim IT untuk:
- menerima dan memproses tiket lebih cepat
- mengontrol peminjaman aset secara transparan
- menjaga data inventori IT tetap update
- menyediakan kanal chat user-admin yang terintegrasi

## 2. Slide Deck (Rekomendasi 10-12 slide)

## Slide 1 - Judul

**Zinus Ticketing**

Satu platform untuk Ticketing, Asset, Loan, dan Live Chat IT Support.

Catatan presenter:
- Jelaskan bahwa sistem ini bukan hanya helpdesk, tapi platform operasional IT end-to-end.

## Slide 2 - Problem Statement

Pain point sebelum sistem terpusat:
- Request IT tersebar di chat/email
- Sulit tracking status penanganan
- Peminjaman device tidak punya audit trail kuat
- Data aset tidak selalu sinkron dengan kondisi lapangan

Catatan presenter:
- Fokus ke dampak bisnis: lambat respon, potensi duplikasi, dan sulit audit.

## Slide 3 - Solusi

Zinus Ticketing menyatukan 4 modul:
- Ticketing
- Loan Management
- Asset Management
- Live Chat + n8n Integration

Catatan presenter:
- Sampaikan bahwa satu user login bisa akses fitur sesuai role.

## Slide 4 - Arsitektur Singkat

```
Web UI (Blade + Livewire)
      |
Laravel 12 App
      |-- Ticket/Loan/Asset/Chat module
      |-- API (Asset Sync + Conversation API)
      |
Database + Mail + n8n Webhook
```

Catatan presenter:
- Jelaskan pemisahan web route vs API route.

## Slide 5 - Modul Ticketing

Highlight:
- Buat tiket + lampiran
- Filter lengkap untuk admin
- Status workflow terstruktur
- Notifikasi database + email
- Idempotency key untuk cegah tiket duplikat

Demo cepat:
- Buat tiket baru
- Tunjukkan perubahan status oleh admin

## Slide 6 - Modul Loan

Highlight:
- Pengajuan pinjam asset
- Persetujuan admin
- Validasi conflict asset
- Otomatis update status asset saat approve/returned

Demo cepat:
- Ajukan pinjam -> approve -> return

## Slide 7 - Modul Asset

Highlight:
- CRUD asset admin
- Filter factory/departemen/kategori/status
- Soft delete + audit log perubahan
- Data breakdown endpoint untuk analitik dashboard

Demo cepat:
- Edit status asset dan lihat dampak di list/loan

## Slide 8 - Live Chat + Integrasi n8n

Highlight:
- User dan admin chat via widget Livewire
- Admin bisa assign conversation per user
- Handoff ke human agent (disable bot)
- Event user message dapat dikirim ke webhook n8n
- API reply admin pakai Sanctum token

Demo cepat:
- User kirim chat -> admin reply -> handoff

## Slide 9 - Security & Reliability

- Role-based access (`user`, `admin`, `super_admin`)
- Middleware proteksi endpoint admin dan asset sync token
- Idempotency di ticket create
- Atomic lock + duplicate guard di message API/chat send
- Audit log untuk asset dan ticket status change

Catatan presenter:
- Tekankan bahwa fokusnya bukan hanya fitur, tapi ketahanan proses.

## Slide 10 - Dampak Operasional

Dampak yang bisa disampaikan:
- Request IT lebih terstruktur
- SLA internal lebih mudah dipantau
- Transparansi peminjaman asset meningkat
- Komunikasi user-admin lebih cepat dan terdokumentasi

Catatan presenter:
- Kalau ada metrik internal, masukkan di slide ini (contoh: lead time turun X%).

## Slide 11 - Roadmap (Opsional)

Saran pengembangan:
- Dashboard SLA dan aging ticket per departemen
- Export laporan periodik
- Integrasi approval berjenjang untuk loan
- End-to-end test untuk flow kritis

## Slide 12 - Penutup

Key message:
- Platform ini sudah menggabungkan proses IT utama dalam satu sistem
- Fondasi sudah siap untuk scaling dan automasi lanjutan

## 3. Script Demo 7 Menit

Urutan demo yang direkomendasikan:

1. Login sebagai user, buka dashboard, buat tiket baru.
2. Login admin, buka daftar tiket, filter dan update status tiket.
3. Buka modul Loan, ajukan pinjam sebagai user, approve sebagai admin.
4. Buka modul Asset, tunjukkan perubahan status asset.
5. Buka chat widget, kirim pesan user, balas dari sisi admin.

## 4. Checklist Sebelum Presentasi

- Data contoh (user/admin/ticket/asset) sudah siap.
- `.env` untuk mail/n8n/asset sync sudah terisi.
- `php artisan migrate` dan `php artisan serve` sudah jalan.
- Jika demo chat+n8n: webhook n8n aktif.
- Jika perlu API demo: token Sanctum admin sudah dibuat.

## 5. Lampiran Endpoint Penting

- Web:
  - `/dashboard`
  - `/tickets`, `/my-tickets`
  - `/loans`
  - `/admin/assets`
  - `/admin/conversations`
- API:
  - `POST /api/asset-sync`
  - `GET /api/v1/conversations`
  - `POST /api/v1/conversations/{id}/messages`
  - `POST /api/v1/conversations/{id}/handoff`

