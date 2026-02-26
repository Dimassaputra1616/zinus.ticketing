# Cheat Sheet Presenter (5 Menit) - Zinus Ticketing

Dokumen ini untuk dipakai saat presentasi cepat. Fokus: jelas, padat, dan meyakinkan.

## 0:00 - 0:30 | Opening

Kalimat pembuka:
"Hari ini saya perkenalkan Zinus Ticketing, platform internal yang menyatukan ticketing, peminjaman aset, inventori, dan live chat IT support dalam satu sistem."

Tujuan presentasi:
- Tunjukkan masalah yang diselesaikan
- Tunjukkan solusi dan dampaknya ke operasional

## 0:30 - 1:10 | Problem

Poin utama:
- Request IT tersebar di banyak channel
- Tracking status sulit
- Peminjaman device kurang transparan
- Data aset tidak selalu sinkron

Kalimat kunci:
"Masalah utamanya bukan di tim, tapi di proses yang belum terpusat."

## 1:10 - 2:00 | Solusi dan Arsitektur

Poin utama:
- 4 modul utama: Ticketing, Loan, Asset, Live Chat
- Laravel sebagai backend + Blade/Livewire di frontend
- Terhubung ke database, email, dan n8n webhook

Kalimat transisi:
"Setelah fondasi sistemnya, saya lanjut ke alur operasional tiap modul."

## 2:00 - 3:20 | Modul Inti

Ticketing:
- User buat tiket + lampiran
- Admin proses dengan workflow status jelas
- Ada notifikasi dan anti-duplikasi ticket create

Loan:
- Pengajuan pinjam -> approve/reject/return
- Ada conflict validation agar aset tidak double dipinjam

Asset:
- CRUD admin + filter lengkap
- Audit log perubahan untuk kebutuhan kontrol

Live Chat:
- User-admin chat langsung
- Bisa handoff dari bot ke admin manusia
- Integrasi n8n untuk automasi

## 3:20 - 4:10 | Security & Reliability

Poin utama:
- Role-based access: user/admin/super admin
- Middleware untuk endpoint sensitif
- Idempotency + atomic lock untuk cegah duplikasi proses
- Histori perubahan tersimpan untuk audit

Kalimat kunci:
"Jadi fokusnya bukan hanya fitur jalan, tapi juga aman dan stabil dipakai harian."

## 4:10 - 4:45 | Dampak Bisnis

Poin utama:
- Proses IT lebih terstruktur
- Progress lebih transparan
- Peminjaman aset lebih tertib
- Komunikasi user-admin terdokumentasi

Jika ada metrik internal, sebut cepat:
- Lead time turun ...%
- SLA tercapai ...%

## 4:45 - 5:00 | Closing

Kalimat penutup:
"Kesimpulannya, Zinus Ticketing sudah jadi fondasi operasional IT yang terintegrasi dan siap dikembangkan untuk skala yang lebih besar. Terima kasih, saya siap untuk Q&A."

## Backup Jawaban Cepat (Q&A)

Q: "Apa bedanya dengan pakai chat group biasa?"
A: "Di sini semua request punya status, histori, dan owner yang jelas, jadi bisa diukur dan diaudit."

Q: "Kalau user kirim request berulang?"
A: "Untuk ticketing sudah ada idempotency guard, dan untuk chat/API ada duplicate guard + lock untuk mencegah double process."

Q: "Apakah siap untuk automasi?"
A: "Ya, karena sudah ada API terproteksi Sanctum dan integrasi webhook n8n."

