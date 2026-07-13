# Zinus Ticketing - AI Agent Instructions (agent.md)

Dokumen ini berisi panduan, instruksi, dan aturan khusus untuk AI Agent yang bekerja pada repositori **Zinus Ticketing**.

## 1. Identitas & Peran Agent
Anda adalah AI Coding Assistant yang bertugas untuk memelihara, memperbaiki, dan mengembangkan aplikasi **Zinus Ticketing** (IT Support Center Ticketing system untuk Zinus Dream Indonesia).

## 2. Aturan Utama (Critical Rules)
1. **Bahasa:** Selalu gunakan Bahasa Indonesia dalam komunikasi dengan user, penjelasan, serta komentar di dalam kode.
2. **Desain & Tema UI:** Ikuti tema warna Zinus (Zinus Green/Mint) untuk semua elemen antarmuka. Gunakan Tailwind CSS.
3. **No Unasked Changes:** Jangan pernah mengubah kode, struktur, atau CSS yang tidak berhubungan dengan tugas kecuali diinstruksikan secara eksplisit (seperti penyesuaian tata letak "ratain", perbaikan bug, atau fitur baru).
4. **Auth Compliance:** Selalu pastikan pengecekan user menggunakan `Auth::user()` dan role-role yang sesuai (`user`, `admin`, `super_admin`).
5. **Context First:** Sebelum melakukan riset mendalam atau menulis kode baru, selalu periksa `CHANGELOG.md` atau `README.md` terlebih dahulu.

## 3. Tech Stack & Directory Map
- **Framework:** Laravel 11.x / 12.x (PHP 8.2+)
- **Frontend:** Livewire 3.x / 4.x, Alpine.js, Tailwind CSS (Vite)
- **Database:** MySQL (untuk development/production), SQLite (untuk testing)
- **Komunikasi:** Integrasi Live Chat dengan n8n

### Peta Direktori Utama
- **Views & Blade:** `resources/views/`
- **Livewire Components:** `app/Livewire/`
- **Controllers & API:** `app/Http/Controllers/`
- **Models:** `app/Models/` (termasuk `app/Models/Setting.php` dengan helper `setting()`)
- **Routes:** `routes/web.php` dan `routes/api.php`
- **Assets:** `resources/css/app.css` & `resources/js/app.js`

## 4. Command yang Sering Digunakan
- **Menjalankan Server Lokal:** `php artisan serve`
- **Menjalankan Frontend (Vite):** `npm run dev` atau `npm run build`
- **Menjalankan Antrean (Queue):** `php artisan queue:listen --tries=1`
- **Menjalankan Unit/Feature Test:** `php artisan test`
- **Migrasi Database:** `php artisan migrate`

## 5. Akun Login Admin untuk Testing
- **Email:** `dimassputra1616@gmail.com`
- **Password:** `0838jangan`

- Inspect and improve the Zinus Asset Sync installer.

Goals:
1. Remove hardcoded API token and require token as a parameter.
2. Create scheduled task as SYSTEM using /RU SYSTEM.
3. Change schedule from monthly to daily at 09:00 and also add an ONSTART task trigger if possible.
4. Improve Get-PrimaryIpv4 so it selects the active adapter with default gateway, not random adapter/VPN.
5. In monitor detection, skip internal laptop display where connection is Internal or Embedded DisplayPort.
6. Keep monitor payload as nested monitors[] under PC/laptop payload.
7. Add monitor identity fields:
   - identity_source: serial or wmi_hash
   - is_identity_verified: true if serial exists, false if fallback hash is used
8. Ensure backend Laravel /api/asset-sync upserts PC/Laptop asset, upserts monitor assets, and creates/updates active attach relation between PC/Laptop and monitors.
9. If a monitor was previously attached to another PC, close the old relation and create a new active relation.
10. Add sync log result response from API so endpoint returns created/updated/attached counts.

- Review and patch the updated ZinusAssetInstaller.

Required fixes:
1. In tools/sync-asset.ps1, scheduled task runs as SYSTEM, so do not use $env:USERNAME for user_name. Add Get-LoggedOnUser using Win32_ComputerSystem.UserName and explorer.exe owner fallback, then set $username = Get-LoggedOnUser.
2. Add identity_source and is_identity_verified for the main PC/laptop asset too. Use serial if available, UUID fallback if available, hostname fallback only as last resort.
3. Do not exit when serial number is missing. Generate fallback asset_code using UUID or hostname, but mark is_identity_verified = false.
4. Remove token from JSON payload if backend already validates Authorization: Bearer token. Keep backward compatibility only if needed.
5. Add simple lock file/mutex so daily and startup scheduled tasks cannot run at the same time.
6. Add RUN-DEPLOY-ALL.cmd wrapper that reads computers.txt and runs Deploy-ZinusAssetSync.ps1 with credential prompt.
7. In backend Laravel /api/asset-sync, upsert monitor assets by asset_code/serial_number, not hostname.
8. When a monitor moves to a different PC/laptop, close the old active relation and create a new relation.
9. API response should return counts: pc_created, pc_updated, monitors_created, monitors_updated, monitors_attached, monitor_links_closed.
10. Agent should log the API response summary after successful sync.
