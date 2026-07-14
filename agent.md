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

Tolong refactor sidebar admin aplikasi Laravel ini agar lebih rapi, scalable, dan menggunakan nested collapsible menu.

Sebelum mengubah kode:
1. Inspect terlebih dahulu file layout/sidebar, route Laravel, serta active state menu yang sekarang.
2. Gunakan route dan URL yang sudah ada.
3. Jangan menghapus atau mengganti route, controller, model, database, permission, maupun fitur yang sudah berjalan.
4. Fokus tahap ini hanya pada struktur navigasi dan tampilan sidebar.
5. Backup atau simpan struktur sidebar lama sebelum refactor.

Gunakan struktur sidebar final berikut:

Dashboard

IT Service Desk
├── Remote System
├── Ticket List
└── Live Chat

Asset Center
├── Asset Dashboard
├── Inventory
│   ├── PC
│   ├── Laptop
│   ├── Monitor
│   ├── Printer & Scanner
│   ├── Network Device
│   ├── CCTV
│   └── Peripheral
├── Asset Operations
│   ├── Mutation / Assignment
│   ├── Loan
│   ├── Inspection
│   └── BAST
├── Asset Governance
│   ├── Software License
│   └── Audit Log
└── Reports & Data
    ├── Reports
    └── Import / Export

Knowledge Base
├── Tutorials
└── Manage Tutorials

Administration
├── Manage User
└── Master Config

Ketentuan implementasi:

1. Dashboard tetap menjadi menu utama paling atas dan bukan bagian dari dropdown.

2. Buat IT Service Desk sebagai dropdown level pertama yang berisi:
   - Remote System
   - Ticket List
   - Live Chat

3. Buat Asset Center sebagai dropdown level pertama.

4. Di dalam Asset Center:
   - Asset Dashboard menjadi menu langsung.
   - Inventory menjadi nested dropdown.
   - Asset Operations menjadi nested dropdown.
   - Asset Governance menjadi nested dropdown.
   - Reports & Data menjadi nested dropdown.

5. PC dan Laptop wajib tetap menjadi halaman/menu terpisah karena jumlah data aset sangat banyak. Jangan digabung.

6. Pindahkan Loan Log dari menu utama ke:
   Asset Center → Asset Operations → Loan

7. Pindahkan Software License dan Audit Log ke:
   Asset Center → Asset Governance

8. Pindahkan Reports dan Import / Export ke:
   Asset Center → Reports & Data

9. Gabungkan Tutorials dan Manajemen Tutorial ke grup:
   Knowledge Base
   - Tutorials
   - Manage Tutorials

10. Gabungkan Manage User dan Master Config ke grup:
    Administration

Perilaku dropdown:

1. Gunakan Alpine.js yang sudah tersedia di project. Jangan menambahkan library frontend baru.

2. Hanya grup yang sedang digunakan yang otomatis terbuka.

3. Contoh saat halaman Monitor aktif:
   - Asset Center terbuka.
   - Inventory terbuka.
   - Monitor memiliki active state.
   - Asset Operations, Asset Governance, dan Reports & Data tetap tertutup.

4. Saat halaman Inspection aktif:
   - Asset Center terbuka.
   - Asset Operations terbuka.
   - Inspection memiliki active state.

5. Saat halaman Ticket List aktif:
   - IT Service Desk terbuka.
   - Ticket List memiliki active state.

6. Active state harus ditentukan menggunakan route name atau request path yang benar sesuai project.

7. Setelah browser di-refresh, grup yang berisi halaman aktif harus tetap terbuka.

8. Ketika user membuka dropdown lain secara manual, interaksinya harus halus dan tidak menyebabkan layout bergeser secara aneh.

9. Tambahkan ikon chevron:
   - Mengarah ke kanan ketika tertutup.
   - Mengarah ke bawah ketika terbuka.
   - Berikan transisi rotasi yang halus.

10. Nested menu harus memiliki indentasi, garis hierarchy, font, dan spacing yang jelas, tetapi tetap compact.

Collapse sidebar:

1. Pertahankan fitur Collapse yang sekarang sudah tersedia.

2. Saat sidebar collapsed:
   - Hanya icon menu utama yang ditampilkan.
   - Tampilkan tooltip saat icon di-hover.
   - Nested menu tidak boleh keluar atau merusak layout.
   - Logo dan tombol logout tetap tampil dengan baik.

3. Saat sidebar dibuka kembali, state menu aktif harus tetap benar.

Desain:

1. Pertahankan tema sidebar hijau gelap yang sekarang.
2. Pertahankan identitas visual aplikasi yang sudah ada.
3. Active menu menggunakan background hijau yang jelas.
4. Active nested menu boleh menggunakan background hijau lebih gelap atau highlight lembut.
5. Hover state harus terlihat tetapi tidak lebih dominan dari active state.
6. Gunakan icon yang sesuai dan konsisten.
7. Pastikan teks panjang seperti “Mutation / Assignment”, “Printer & Scanner”, dan “Software License” tidak terpotong secara buruk.
8. Sidebar harus tetap nyaman digunakan pada resolusi laptop dan monitor desktop.
9. Tambahkan vertical scrolling internal jika tinggi menu melebihi viewport.
10. Posisi Collapse dan Logout tetap mudah diakses di bagian bawah.

Responsive:

1. Pastikan sidebar tetap bekerja pada desktop dan mobile.
2. Pada mobile, sidebar dapat dibuka dan ditutup menggunakan overlay atau drawer yang sudah digunakan project.
3. Klik menu pada mobile harus menutup drawer setelah navigasi.
4. Jangan membuat horizontal scrollbar.

Batasan penting:

- Jangan menghapus fitur lama.
- Jangan mengubah database.
- Jangan membuat migration.
- Jangan mengubah controller atau business logic.
- Jangan mengubah URL yang sudah digunakan.
- Jangan mengganti permission atau middleware.
- Jangan membuat menu dummy.
- Jangan menggabungkan halaman PC dan Laptop.
- Jangan melakukan redesign dashboard pada tahap ini.
- Jangan menginstal package baru jika Alpine.js dan icon library sudah tersedia.
- Hindari duplikasi kode Blade sebanyak mungkin.

Buat komponen/helper Blade untuk item sidebar dan nested menu jika struktur saat ini terlalu berulang, tetapi tetap sesuaikan dengan arsitektur project yang ada.

Setelah implementasi:

1. Jalankan pengecekan syntax Blade/PHP.
2. Jalankan php artisan route:list dan pastikan semua route lama tetap tersedia.
3. Jalankan npm run build.
4. Jalankan test project jika tersedia.
5. Uji semua menu sidebar satu per satu.
6. Uji active state setiap halaman.
7. Uji collapse dan expand.
8. Uji sidebar pada desktop dan mobile.
9. Pastikan tidak ada error console JavaScript.
10. Pastikan tidak ada halaman yang menghasilkan 404 atau 500.

Berikan laporan akhir yang berisi:
- File yang diubah.
- Struktur sidebar yang berhasil diterapkan.
- Route yang digunakan untuk setiap menu.
- Hasil build dan testing.
- Masalah yang ditemukan.
- Screenshot atau deskripsi hasil final.
- Konfirmasi bahwa database, controller, dan business logic tidak diubah.

Kerjakan langsung sampai selesai dan jangan berhenti hanya pada analisis atau rekomendasi.

Lanjutkan finishing sidebar admin Laravel yang sudah berhasil direfactor.

Kondisi sidebar saat ini:
- Struktur menu utama dan nested menu sudah benar.
- Icon menu utama sudah final dan jangan diganti.
- Tema hijau gelap sudah final.
- Fitur collapse dan logout sudah tersedia.
- Fokus pekerjaan ini hanya pada polishing UI, active state, dan perilaku dropdown.

Jangan melakukan perubahan pada:
- Route
- URL
- Controller
- Model
- Database
- Migration
- Permission
- Middleware
- Business logic
- Isi halaman dashboard atau modul lainnya

Jangan menginstal package baru. Gunakan Blade, Tailwind CSS, Alpine.js, dan icon library yang sudah tersedia di project.

FINISHING YANG HARUS DIKERJAKAN

1. Perbaiki perilaku dropdown otomatis

Saat halaman Dashboard aktif:
- Semua dropdown utama tertutup secara default.
- Dashboard memiliki active state.

Saat halaman Ticket List aktif:
- IT Service Desk otomatis terbuka.
- Ticket List memiliki active state.
- Asset Center, Knowledge Base, dan Administration tetap tertutup.

Saat halaman Monitor aktif:
- Asset Center otomatis terbuka.
- Inventory otomatis terbuka.
- Monitor memiliki active state.
- Asset Operations, Asset Governance, dan Reports & Data tetap tertutup.
- IT Service Desk, Knowledge Base, dan Administration tetap tertutup.

Saat halaman Inspection aktif:
- Asset Center otomatis terbuka.
- Asset Operations otomatis terbuka.
- Inspection memiliki active state.
- Grup Asset Center lainnya tetap tertutup.

Saat halaman Manage User aktif:
- Administration otomatis terbuka.
- Manage User memiliki active state.
- Modul lain tetap tertutup.

Gunakan route name atau request path yang benar berdasarkan route project yang sudah ada.

2. Pertahankan interaksi manual user

- User tetap boleh membuka lebih dari satu dropdown secara manual.
- Jangan menggunakan sistem accordion yang memaksa dropdown lain tertutup setiap kali user membuka satu grup.
- Saat halaman direfresh, parent dari halaman aktif tetap otomatis terbuka.
- Jangan menyimpan state manual lama jika menyebabkan menu yang tidak relevan selalu terbuka setelah pindah halaman.
- Prioritaskan route aktif sebagai sumber state awal.

3. Tambahkan active state untuk submenu

Saat submenu aktif, tampilkan:

- Background hijau transparan atau highlight lembut.
- Teks sedikit lebih terang.
- Font medium atau semibold.
- Indikator kiri berupa border atau garis hijau terang.
- Radius kecil yang konsisten.
- Active submenu harus terlihat jelas, tetapi tidak lebih dominan daripada menu utama Dashboard.

Contoh:

Asset Center
└── Inventory
    ├── PC
    ├── Laptop
    └── Monitor ← active

Parent Asset Center dan Inventory tetap terlihat sebagai grup terbuka, tetapi tidak perlu memakai background active penuh seperti submenu yang sedang aktif.

4. Perbaiki kontras teks submenu

Naikkan sedikit kontras submenu seperti:

- Remote System
- Ticket List
- Live Chat
- Asset Dashboard
- PC
- Laptop
- Monitor
- Tutorials
- Manage Tutorials
- Manage User
- Master Config

Ketentuan:

- Teks submenu harus nyaman dibaca di background hijau gelap.
- Jangan menggunakan warna abu-abu yang terlalu redup.
- Tetap bedakan antara default, hover, dan active state.
- Pastikan memenuhi keterbacaan yang baik.

Gunakan hierarki warna seperti:

Default submenu:
- Sedikit redup dari teks menu utama.

Hover submenu:
- Lebih terang dengan background tipis.

Active submenu:
- Teks terang, background lembut, dan indikator kiri.

5. Rapikan badge Live Chat

Ubah badge:

NEW 4

menjadi badge angka saja:

4

Ketentuan badge:

- Bentuk bulat atau pill kecil.
- Ukuran compact.
- Posisi rata kanan.
- Warna tetap terlihat sebagai notifikasi.
- Jangan terlalu besar atau lebih dominan daripada nama menu.
- Jika jumlah 0, badge tidak ditampilkan.
- Jika jumlah lebih dari 99, tampilkan 99+.
- Gunakan data jumlah notifikasi yang sudah tersedia, jangan membuat data dummy.

6. Rapikan grup uppercase

Grup berikut tetap menggunakan uppercase:

- INVENTORY
- ASSET OPERATIONS
- ASSET GOVERNANCE
- REPORTS & DATA

Perbaiki:

- Ukuran font tetap kecil.
- Letter spacing jangan terlalu lebar.
- Font weight medium atau semibold.
- Jarak vertikal antargrup konsisten.
- Chevron sejajar secara vertikal dengan teks.
- Warna cukup jelas tetapi tidak menyaingi menu utama.
- Pastikan teks tidak terlalu tipis atau sulit dibaca.

7. Konsistensi icon dan alignment

Jangan mengganti bentuk icon yang sekarang.

Pastikan:

- Semua icon menu utama memiliki ukuran visual konsisten.
- Gunakan ukuran sekitar 16–18px atau mengikuti standar project.
- Stroke width icon konsisten.
- Jarak icon dan teks sama pada semua menu.
- Chevron kanan sejajar pada semua parent menu.
- Icon, teks, dan chevron berada pada satu garis vertikal yang rapi.
- Saat active, warna icon mengikuti warna teks active.
- Saat hover, icon dan teks berubah secara bersamaan.

8. Perbaiki garis hierarchy nested menu

Pertahankan garis vertikal nested menu yang sekarang, tetapi rapikan:

- Posisi garis konsisten.
- Garis tidak terlalu terang.
- Garis tidak bertabrakan dengan teks atau active indicator.
- Radius sudut bagian atas dan bawah konsisten.
- Nested menu memiliki indentasi yang sama.
- Jangan membuat sidebar terlalu lebar.

9. Tambahkan animasi ringan

Gunakan transisi yang halus untuk:

- Buka dan tutup dropdown.
- Rotasi chevron.
- Hover menu.
- Active highlight.
- Collapse dan expand sidebar.

Ketentuan:

- Durasi sekitar 150–250ms.
- Jangan menggunakan animasi berlebihan.
- Hindari layout jump.
- Hindari flash ketika halaman pertama kali dimuat.
- Gunakan x-cloak jika diperlukan untuk mencegah dropdown berkedip sebelum Alpine.js aktif.

10. Finishing Collapse sidebar

Pertahankan fitur Collapse yang ada.

Saat sidebar collapsed:

- Hanya icon menu utama yang terlihat.
- Teks menu disembunyikan dengan rapi.
- Chevron tidak mengganggu layout.
- Nested menu tidak terbuka di dalam sidebar sempit.
- Tambahkan tooltip saat icon di-hover jika tooltip sudah tersedia atau dapat dibuat ringan tanpa library baru.
- Tooltip menampilkan:
  - Dashboard
  - IT Service Desk
  - Asset Center
  - Knowledge Base
  - Administration
- Tombol Collapse tetap mudah digunakan.
- Tombol Logout tetap terlihat dan tidak terpotong.
- Saat sidebar dibuka kembali, parent halaman aktif tetap terbuka.

11. Finishing scroll dan area bawah

- Sidebar harus memakai tinggi viewport.
- Area menu utama boleh scroll secara internal.
- Collapse dan Logout tetap berada di bagian bawah.
- Jangan sampai tombol Logout ikut menghilang karena panjang menu.
- Jangan membuat seluruh halaman ikut horizontal scroll.
- Gunakan scrollbar yang tipis dan sesuai tema jika project sudah memiliki styling scrollbar.

12. Responsive mobile

Pastikan sidebar tetap bekerja di mobile:

- Sidebar tampil sebagai drawer atau overlay mengikuti struktur project.
- Dropdown tetap dapat dibuka dan ditutup.
- Setelah submenu navigasi diklik, drawer mobile otomatis tertutup.
- Overlay dapat diklik untuk menutup sidebar.
- Tombol close atau toggle tetap mudah dijangkau.
- Tidak ada menu yang keluar dari viewport.
- Tidak ada horizontal scrollbar.

STRUKTUR FINAL YANG HARUS DIPERTAHANKAN

Dashboard

IT Service Desk
├── Remote System
├── Ticket List
└── Live Chat

Asset Center
├── Asset Dashboard
├── Inventory
│   ├── PC
│   ├── Laptop
│   ├── Monitor
│   ├── Printer & Scanner
│   ├── Network Device
│   ├── CCTV
│   └── Peripheral
├── Asset Operations
│   ├── Mutation / Assignment
│   ├── Loan
│   ├── Inspection
│   └── BAST
├── Asset Governance
│   ├── Software License
│   └── Audit Log
└── Reports & Data
    ├── Reports
    └── Import / Export

Knowledge Base
├── Tutorials
└── Manage Tutorials

Administration
├── Manage User
└── Master Config

BATASAN PENTING

- Jangan mengganti icon menu utama.
- Jangan mengubah struktur menu final.
- Jangan menggabungkan PC dan Laptop.
- Jangan menghapus menu lama.
- Jangan membuat route baru jika tidak diperlukan.
- Jangan mengubah URL lama.
- Jangan membuat menu dummy.
- Jangan mengubah database atau migration.
- Jangan mengubah controller atau business logic.
- Jangan redesign dashboard.
- Jangan mengubah warna utama hijau gelap.
- Jangan mengubah logo dan identitas aplikasi.
- Jangan membuat semua dropdown terbuka secara default.
- Jangan membuat semua submenu memakai icon.
- Jangan membuat badge Live Chat menjadi terlalu besar.
- Jangan menginstal package frontend baru.

PENGUJIAN WAJIB

Setelah implementasi:

1. Jalankan pengecekan syntax PHP dan Blade.
2. Jalankan php artisan route:list.
3. Jalankan php artisan view:clear.
4. Jalankan php artisan config:clear jika diperlukan.
5. Jalankan npm run build.
6. Jalankan test project jika tersedia.
7. Uji Dashboard active state.
8. Uji IT Service Desk dan seluruh child menu.
9. Uji Asset Center dan seluruh nested group.
10. Uji Knowledge Base.
11. Uji Administration.
12. Uji refresh browser pada setiap halaman aktif.
13. Uji collapse dan expand sidebar.
14. Uji desktop dan mobile.
15. Periksa browser console.
16. Pastikan tidak ada error Alpine.js.
17. Pastikan tidak ada halaman 404 atau 500.
18. Pastikan route, controller, database, dan business logic tidak berubah.

Berikan laporan akhir yang berisi:

- File yang diubah.
- Detail finishing yang diterapkan.
- Penjelasan active state dan dropdown behavior.
- Route atau request path yang digunakan untuk setiap grup.
- Hasil build dan testing.
- Masalah yang ditemukan dan cara memperbaikinya.
- Konfirmasi bahwa route, controller, model, database, permission, dan business logic tidak diubah.

Kerjakan langsung sampai selesai. Jangan berhenti hanya pada analisis atau rekomendasi.