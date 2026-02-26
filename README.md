# Zinus Ticketing

Aplikasi internal IT Support berbasis Laravel untuk mengelola:
- ticketing helpdesk
- peminjaman device/aset
- inventori aset IT
- live chat user <-> admin (integrasi n8n)

## 1. Ringkasan

Project ini menggabungkan 4 proses operasional IT dalam satu sistem:
- Incident/request tracking lewat modul Ticketing
- Borrowing workflow lewat modul Loan
- Asset inventory dan audit log lewat modul Asset
- Real-time-ish komunikasi user-admin lewat Livewire Chat

Target utama:
- mempercepat respon IT
- meminimalkan duplikasi input
- menjaga histori perubahan untuk audit

## 2. Fitur Utama

### Ticketing
- User membuat tiket (judul, deskripsi, kategori, departemen, lampiran)
- Admin melihat semua tiket dengan filter status, departemen, tanggal, pencarian
- Update status: `open`, `assigned`, `in_progress`, `waiting_user`, `resolved`, `closed`
- Ticket log menyimpan histori perubahan status dan actor snapshot
- Notifikasi database ke admin saat tiket baru dibuat
- Email notifikasi untuk tiket baru dan perubahan status
- Idempotency key + payload hash untuk mencegah tiket duplikat

### Loan (Peminjaman)
- User mengajukan peminjaman asset (utama: kategori Laptop)
- Admin approve/reject/return
- Sinkronisasi status asset saat approve/return
- Proteksi conflict agar 1 aset tidak bisa dipinjam paralel

### Asset Management
- CRUD aset (admin only)
- Filter factory, departemen, kategori, status, search
- Status aset: `available`, `in_use`, `maintenance`, `broken`
- Soft delete + audit log (`asset_logs`)
- Endpoint breakdown data untuk dashboard (lokasi, departemen, user assets)

### Live Chat + n8n
- Widget chat Livewire tersedia di layout app
- Admin bisa handle multi-user conversation
- Assignment admin per conversation (`assigned_admin_id`)
- Handoff mode (`is_bot_active = false`) untuk stop bot
- Event user message dikirim ke webhook n8n
- API admin reply dilindungi Sanctum token
- Atomic lock + duplicate guard untuk cegah double message

## 3. Tech Stack

- Backend: PHP 8.2, Laravel 12
- Frontend: Blade, Livewire 4, Alpine.js, Tailwind CSS, Vite
- Auth/API: Laravel Sanctum
- Data: MySQL/PostgreSQL/SQLite (default test sqlite)
- Notifikasi: database + mail

## 4. Arsitektur Singkat

```
[Web User/Admin]
      |
      v
[Laravel App]
  - Web routes (Blade + Livewire)
  - API routes (Asset Sync + Conversation API)
      |
      +--> [MySQL/SQLite]
      |      - users, tickets, assets, borrow_logs,
      |        conversations, messages, notifications
      |
      +--> [Mail Provider]
      |
      +--> [n8n Webhook]
```

## 5. Role dan Akses

- `user`
  - buat tiket
  - lihat tiket milik sendiri
  - ajukan pinjam aset
  - chat dengan admin
- `admin`
  - kelola semua tiket
  - kelola user (sebagian fitur super admin)
  - kelola aset dan loan status
  - balas chat
  - akses API conversation via token Sanctum
- `super_admin`
  - ubah role user
  - reset password user
  - hapus user

## 6. Struktur Modul (ringkas)

- `app/Http/Controllers/TicketController.php`
- `app/Http/Controllers/LoanController.php`
- `app/Http/Controllers/AssetController.php`
- `app/Livewire/ChatWidget.php`
- `app/Http/Controllers/Api/AssetSyncController.php`
- `app/Http/Controllers/Api/ConversationApiController.php`
- `app/Services/AssetService.php`

## 7. Setup Lokal

### Prasyarat
- PHP 8.2+
- Composer
- Node.js + npm
- Database (MySQL direkomendasikan untuk environment dev/prod)

### Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run build
```

Untuk mode development:

```bash
composer run dev
```

Atau jalankan terpisah:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

### Environment penting
Tambahkan variabel berikut di `.env` (belum semua ada di `.env.example`):

```env
N8N_WEBHOOK_URL=

ASSET_SYNC_TOKEN=
ASSET_SYNC_TOKENS=
ASSET_SYNC_AGENT_SHA256=
ASSET_SYNC_DEPARTMENT=
ASSET_SYNC_FACTORY=

MAIL_EXTRA_ADMINS=
```

Catatan:
- `ASSET_SYNC_TOKENS` mendukung JSON scoped token.
- `MAIL_EXTRA_ADMINS` bisa dipisahkan koma/semicolon.

## 8. Command Operasional

```bash
php artisan make:admin
php artisan api:generate-token {email} {token_name?}
php artisan n8n:verify
```

## 9. API Ringkas

### Asset Sync
- `POST /api/asset-sync`
- Auth: Bearer token (`asset.sync` middleware)
- Fungsi: create/update asset dari agent endpoint

### Conversation API (v1)
- `GET /api/v1/conversations`
- `GET /api/v1/conversations/{id}`
- `POST /api/v1/conversations/{id}/messages`
- `POST /api/v1/conversations/{id}/handoff`
- Auth: Sanctum token (`auth:sanctum`)

## 10. Testing

Jalankan:

```bash
php artisan test
```

Saat dokumentasi ini dibuat, sebagian test auth/profile gagal karena mismatch skema (`users.email_verified_at`) dan error view pada halaman confirm-password. Test lain seperti `AssetSyncTest` dan beberapa auth flow sudah berjalan.

## 11. Dokumen Presentasi

Gunakan file berikut untuk materi presentasi:

- `docs/PRESENTASI.md`

