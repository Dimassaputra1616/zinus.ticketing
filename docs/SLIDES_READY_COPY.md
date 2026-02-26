# Zinus Ticketing - Slide Ready Copy

Dokumen ini dibuat untuk langsung di-copy ke Google Slides / PowerPoint.

Cara pakai cepat:
1. Buat deck baru (12 slide).
2. Copy isi tiap bagian `SLIDE X` ke slide terkait.
3. Pakai bagian `Speaker Notes` untuk catatan saat presentasi.

## SLIDE 1 - Cover

Title:
Zinus Ticketing

Subtitle:
Satu Platform untuk Ticketing, Asset, Loan, dan Live Chat IT Support

Footer kecil:
Tim IT Support Zinus Dream

Speaker Notes:
Sistem ini kita bangun untuk menyatukan proses operasional IT yang sebelumnya tersebar di banyak channel.

## SLIDE 2 - Agenda

Title:
Agenda Presentasi

Body:
- Latar belakang masalah
- Solusi dan arsitektur sistem
- Demo alur utama per modul
- Dampak operasional
- Roadmap pengembangan

Speaker Notes:
Kita akan fokus pada value bisnis dulu, lalu bagaimana implementasi teknisnya mendukung proses tersebut.

## SLIDE 3 - Problem Statement

Title:
Masalah Sebelum Sistem Terpusat

Body:
- Request IT tersebar (chat, email, pesan langsung)
- Sulit tracking status penanganan per request
- Peminjaman perangkat belum terdokumentasi rapi
- Data aset tidak selalu sinkron dengan kondisi aktual

Highlight box:
Dampak: respon lambat, duplikasi kerja, dan audit sulit.

Speaker Notes:
Poin utamanya bukan kurang effort tim IT, tapi prosesnya memang belum punya satu sumber data yang konsisten.

## SLIDE 4 - Solusi

Title:
Solusi: Zinus Ticketing

Body:
- Ticketing untuk incident/request tracking
- Loan management untuk alur peminjaman aset
- Asset management untuk inventori dan histori perubahan
- Live chat user-admin dengan integrasi n8n

Highlight box:
Satu login, fitur sesuai role user.

Speaker Notes:
Platform ini menyatukan alur dari laporan masalah sampai eksekusi dan dokumentasi hasilnya.

## SLIDE 5 - Arsitektur Singkat

Title:
Arsitektur Sistem

Body:
- Frontend: Blade + Livewire
- Backend: Laravel 12 (Web + API)
- Database: users, tickets, assets, borrow logs, conversations, messages
- Integrasi eksternal: Mail + n8n webhook

Diagram text (opsional):
Web UI -> Laravel App -> Database
                 -> Mail
                 -> n8n Webhook

Speaker Notes:
Ada dua jalur utama: web route untuk aplikasi internal, dan API route untuk integrasi seperti asset sync dan chatbot workflow.

## SLIDE 6 - Modul Ticketing

Title:
Modul 1 - Ticketing

Body:
- User buat tiket + lampiran
- Admin filter tiket (status, departemen, tanggal, keyword)
- Workflow status terstandar
- Notifikasi database dan email
- Idempotency guard untuk cegah tiket duplikat

Status workflow:
open -> assigned -> in_progress -> waiting_user -> resolved -> closed

Speaker Notes:
Ini modul inti untuk memastikan semua request IT punya jejak status yang jelas dari awal sampai selesai.

## SLIDE 7 - Modul Loan

Title:
Modul 2 - Loan Management

Body:
- User ajukan peminjaman perangkat
- Admin approve/reject/return
- Validasi conflict agar asset tidak dipinjam ganda
- Status asset otomatis berubah saat approve/returned

Highlight box:
Kontrol peminjaman lebih transparan dan terukur.

Speaker Notes:
Dengan validasi conflict, kita kurangi risiko double booking perangkat yang sering jadi masalah di operasional harian.

## SLIDE 8 - Modul Asset

Title:
Modul 3 - Asset Management

Body:
- CRUD asset oleh admin
- Filter factory, departemen, kategori, status
- Soft delete untuk keamanan data
- Audit log perubahan (status/user assignment)
- Data breakdown endpoint untuk analitik dashboard

Speaker Notes:
Modul asset tidak hanya daftar inventori, tapi juga jejak perubahan untuk kebutuhan monitoring dan audit.

## SLIDE 9 - Modul Live Chat + n8n

Title:
Modul 4 - Live Chat & Automasi

Body:
- Chat widget user-admin berbasis Livewire
- Admin dapat assignment conversation per user
- Handoff ke human agent (bot bisa dinonaktifkan)
- Pesan user dapat diteruskan ke n8n webhook
- API balasan admin diamankan dengan Sanctum token

Speaker Notes:
Ini penting untuk kebutuhan respon cepat, sambil tetap menjaga opsi automasi dan eskalasi ke admin manusia.

## SLIDE 10 - Security & Reliability

Title:
Keamanan dan Keandalan

Body:
- Role-based access: user, admin, super_admin
- Middleware proteksi untuk endpoint sensitif
- Idempotency untuk request ticket creation
- Atomic lock + duplicate guard pada chat message
- Histori perubahan untuk audit operasional

Speaker Notes:
Fokus kita bukan hanya fitur jalan, tapi juga bagaimana sistem tetap aman dan stabil saat dipakai harian.

## SLIDE 11 - Dampak Operasional

Title:
Dampak untuk Tim dan Bisnis

Body:
- Request IT lebih terstruktur dan mudah diprioritaskan
- Progress penanganan lebih transparan
- Peminjaman perangkat lebih tertib
- Data inventori lebih terjaga konsistensinya
- Komunikasi user-admin terdokumentasi

Placeholder metrik (isi sesuai data internal):
- Lead time turun: ...%
- Ticket terselesaikan tepat waktu: ...%
- Insiden duplikasi pengajuan turun: ...%

Speaker Notes:
Kalau ada data kuantitatif internal, slide ini jadi yang paling kuat untuk meyakinkan stakeholder.

## SLIDE 12 - Penutup

Title:
Kesimpulan

Body:
- Zinus Ticketing sudah mengintegrasikan proses IT utama dalam satu platform
- Fondasi teknis siap untuk scaling dan automasi lanjutan
- Next step: SLA dashboard, laporan periodik, dan perluasan integrasi

Closing line:
Terima kasih - Q&A

Speaker Notes:
Tutup dengan menekankan bahwa sistem ini bukan proyek sekali jalan, tapi fondasi jangka panjang untuk operasional IT.

## Appendix - Demo Flow (7 Menit)

Urutan demo yang disarankan:
1. Login user -> buat tiket baru.
2. Login admin -> update status tiket.
3. Loan flow -> approve -> return.
4. Asset list -> cek status dan detail.
5. Chat user-admin -> handoff scenario.

## Appendix - Checklist Sebelum Presentasi

- Data demo sudah disiapkan (user/admin/ticket/asset/conversation).
- `php artisan migrate` sudah sukses.
- Server lokal aktif (`php artisan serve`).
- Jika demo automasi: `N8N_WEBHOOK_URL` aktif.
- Jika demo API: token Sanctum admin sudah dibuat.

