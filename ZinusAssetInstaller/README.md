# Deployment Zinus Asset Sync

Zinus Asset Sync bisa dipasang secara terpusat supaya IT tidak perlu membuka
setiap PC dan laptop satu per satu. Endpoint tetap membutuhkan agent lokal,
tetapi instalasinya bisa didorong lewat GPO, Intune, SCCM, PDQ, atau PowerShell
Remoting.

## Isi Paket

- `Install-ZinusAssetSync.ps1` - installer utama. Script ini menyalin agent ke
  `C:\ProgramData\ZinusAssetSync`, menulis `config.json`, dan membuat scheduled
  task harian serta startup sebagai `SYSTEM`.
- `Install-ZinusAssetSync.cmd` - wrapper manual dengan pause di akhir.
- `Install-ZinusAssetSync-Silent.cmd` - wrapper silent untuk GPO, Intune, SCCM,
  PDQ, atau deployment jarak jauh.
- `Install-ZinusAssetSync-Auto.ps1` dan `INSTALL-AUTO.cmd` - mode tinggal klik
  yang membaca token dan setting dari `install-config.json`.
- `install-config.example.json` - template config deployment. Copy menjadi
  `install-config.json`, lalu isi token yang sesuai server.
- `Bootstrap-ZinusWinRM.ps1` dan `RUN-BOOTSTRAP-WINRM-SEGMENTS.cmd` - bootstrap
  WinRM massal lewat PsExec untuk environment workgroup/non-AD yang punya akun
  local Administrator seragam.
- `Discover-ZinusNetwork.ps1` dan `RUN-DISCOVER-SEGMENTS.cmd` - discovery
  jaringan ala GLPI untuk melihat IP online, DNS name, MAC dari ARP neighbor,
  dan status port WinRM tanpa memasang agent.
- `Resolve-ZinusHostnames.ps1` dan `RUN-RESOLVE-HOSTNAMES.cmd` - melengkapi
  hostname yang kosong dengan membacanya langsung dari Windows lewat PsExec
  dan credential local Administrator.
- `Scan-ZinusAssetsRemote.ps1` dan `RUN-SCAN-ALL.cmd` - mode remote scan satu
  kali jalan untuk menarik data banyak PC/laptop tanpa memasang agent permanen.
- `Scan-ZinusSegments.ps1` dan `RUN-SCAN-SEGMENTS.cmd` - mode remote scan
  berdasarkan IP segment, default untuk segment `10.62.38`, `10.62.39`, dan
  `10.62.36`.
- `RUN-DEPLOY-ALL.cmd` - wrapper bulk deploy yang membaca `computers.txt`,
  meminta token dan credential admin, lalu menjalankan deployer.
- `Deploy-ZinusAssetSync.ps1` - helper bulk deployment memakai PowerShell
  Remoting.
- `Build-ZinusAssetPackage.ps1` - membuat folder dan zip deployable yang bersih
  untuk share folder, GPO, Intune, SCCM, atau PDQ.
- `tools\sync-asset.ps1` - agent inventaris yang mengirim data PC/laptop dan
  monitor ke `/api/asset-sync`.

## Build Paket Deployment

Jalankan dari folder `ZinusAssetInstaller`:

```powershell
.\Build-ZinusAssetPackage.ps1
```

Output akan dibuat di:

```text
.\dist\ZinusAssetInstaller
.\dist\ZinusAssetInstaller.zip
```

Gunakan folder hasil build untuk network share, atau upload zip/folder ke
Intune, SCCM, PDQ, atau tool deployment lain.

## Opsi Cepat - Tinggal Klik

1. Copy `install-config.example.json` menjadi `install-config.json`.
2. Isi `token` dengan token yang sama seperti `ASSET_SYNC_TOKEN` atau salah satu
   token di `ASSET_SYNC_TOKENS` pada server Laravel.
3. Sesuaikan `factory`, `department`, dan `server_url` jika perlu.
4. Klik kanan `INSTALL-AUTO.cmd`, lalu pilih **Run as administrator**.

Contoh isi `install-config.json`:

```json
{
  "token": "TOKEN_DARI_SERVER",
  "factory": "GCI-HWANG",
  "department": "IT",
  "server_url": "https://app.it-ticketing.web.id/api/asset-sync",
  "agent_version": "1.1.0",
  "skip_run": false,
  "rustdesk_id_server": "",
  "rustdesk_relay_server": "",
  "rustdesk_key": ""
}
```

File `install-config.json` berisi secret. Simpan hanya di share folder internal
yang aksesnya dibatasi untuk admin/IT deployment.

## Opsi Discovery Ala GLPI - Tanpa WinRM/Agent

Gunakan opsi ini untuk tahap awal seperti network discovery GLPI. Mode ini
tidak mengambil serial/RAM/monitor, tapi bisa menemukan device yang online di
segment IP.

Jalankan:

```cmd
RUN-DISCOVER-SEGMENTS.cmd
```

Default segment:

```text
10.62.38.1-254
10.62.39.1-254
10.62.36.1-254
```

Output:

```text
.\zinus-network-discovery-results.csv
.\zinus-network-discovery-online.csv
```

File `zinus-network-discovery-online.csv` hanya berisi perangkat yang terdeteksi
hidup agar pasangan IP dan hostname lebih mudah diperiksa.

Kolom output:

- `ip_address`
- `online`
- `hostname`
- `name_source` (`DNS` atau `NetBIOS`)
- `detection` (`Ping` atau port TCP Windows yang terbuka)
- `dns_name`
- `mac_address`
- `neighbor_state`
- `wsman_5985`

Discovery mencoba ping dan port layanan Windows `135`, `139`, serta `445`.
Karena itu PC yang memblokir ping masih dapat terdeteksi dan dicari hostname-nya
melalui DNS atau NetBIOS.

Jika banyak hostname kosong karena kantor tidak memiliki reverse DNS dan
NetBIOS diblokir/nonaktif, taruh `PsExec.exe` di folder installer lalu jalankan:

```cmd
RUN-RESOLVE-HOSTNAMES.cmd
```

Masukkan credential local Administrator target. Script membaca file
`zinus-network-discovery-online.csv`, melewati hostname yang sudah ditemukan,
dan membaca `$env:COMPUTERNAME` langsung dari Windows untuk sisanya. Output:

```text
.\zinus-network-hostnames.csv
```

Kolom `status` menjelaskan hasil tiap IP. `smb_closed` berarti port `445`
tertutup, sedangkan `failed` biasanya menunjukkan credential ditolak, remote
UAC membatasi local admin, target bukan Windows, atau admin share tidak aktif.

Jika `wsman_5985 = True`, target tersebut bisa lanjut ke remote inventory
dengan `RUN-SCAN-SEGMENTS.cmd`. Jika `online = True` tetapi `wsman_5985 = False`,
device terdeteksi hidup, tetapi belum bisa diambil data hardware lengkap tanpa
WinRM/agent/SNMP.

## Opsi Bootstrap WinRM Dengan Local Administrator Seragam

Gunakan opsi ini jika kantor tidak punya AD/GPO, tapi semua PC/laptop punya akun
local Administrator yang username dan password-nya seragam. Script ini membuka
WinRM dari satu admin machine supaya tahap berikutnya bisa remote scan by
segment.

Syarat awal:

- Jalankan dari Windows admin machine yang satu jaringan dengan target.
- Akun local admin target valid untuk semua PC/laptop yang akan dibootstrap.
- Target mengizinkan akses remote admin via SMB/admin share/PsExec.
- Taruh `PsExec.exe` dari Microsoft Sysinternals di folder installer. File
  `PsExec.exe` tidak dibundel di repo ini.

Jalankan Command Prompt atau PowerShell sebagai Administrator, lalu:

```cmd
RUN-BOOTSTRAP-WINRM-SEGMENTS.cmd
```

Saat prompt muncul:

- base IP prefix: tekan Enter untuk `10.62`
- segment: tekan Enter untuk `38,39,36`
- mulai host: tekan Enter untuk `1`
- akhir host: tekan Enter untuk `254`
- path PsExec: tekan Enter jika `PsExec.exe` ada di folder installer
- credential: isi akun local Administrator target
- TrustedHosts: jawab `Y` untuk workgroup/IP-based remoting
- LocalAccountTokenFilterPolicy: jawab `Y` jika memakai local admin workgroup

Output:

```text
.\zinus-winrm-bootstrap-results.csv
```

Jika bootstrap sudah sukses, lanjutkan:

```cmd
RUN-SCAN-SEGMENTS.cmd
```

Catatan keamanan:

- PsExec membutuhkan password dalam proses eksekusi. Jalankan hanya dari admin
  machine terpercaya dan jangan simpan screenshot/log yang menampilkan secret.
- `LocalAccountTokenFilterPolicy=1` membuat local admin bisa mendapat token
  admin penuh lewat remote access. Pakai hanya di jaringan internal yang
  dipercaya, dan rotasi password local Administrator setelah rollout besar.
- Jika target menutup SMB/admin share dan WinRM sekaligus, bootstrap jarak jauh
  tidak bisa masuk. Target tersebut perlu dibuka manual sekali, lewat tool RMM,
  atau dipasang agent lokal.

## Opsi Remote Scan - Sekali Jalan Tanpa Install Agent

Gunakan mode ini kalau ingin menarik data banyak PC/laptop dari satu admin
machine tanpa memasang agent permanen di masing-masing komputer.

1. Buat `computers.txt` di folder installer:

   ```text
   PC-001
   PC-002
   LAPTOP-003
   ```

2. Jalankan:

   ```cmd
   RUN-SCAN-ALL.cmd
   ```

3. Masukkan Asset Sync token dan credential admin saat diminta.

Script akan melakukan remote scan via PowerShell Remoting/WinRM, mengumpulkan
data PC/laptop dan monitor, lalu mengirim payload ke `/api/asset-sync`.

Command manualnya:

```powershell
.\Scan-ZinusAssetsRemote.ps1 `
  -ComputerList .\computers.txt `
  -Token "YOUR_TOKEN" `
  -Factory "GCI-HWANG" `
  -Department "IT" `
  -ServerUrl "https://app.it-ticketing.web.id/api/asset-sync" `
  -Credential (Get-Credential)
```

Hasil scan disimpan ke:

```text
.\zinus-asset-remote-scan-results.csv
```

Syarat remote scan:

- Target PC/laptop online dan bisa di-resolve lewat hostname/IP.
- PowerShell Remoting/WinRM aktif di target.
- Firewall target mengizinkan WinRM.
- Credential yang dipakai punya local admin atau hak remote management di target.
- Untuk workgroup/non-domain yang dipanggil via IP, admin machine perlu
  `TrustedHosts`; `RUN-BOOTSTRAP-WINRM-SEGMENTS.cmd` bisa mengaturnya otomatis.

Tes dari admin machine:

```powershell
Test-WSMan 10.62.38.10
```

Jika gagal dengan pesan WinRM/firewall, aktifkan WinRM di target. Untuk domain,
sebaiknya aktifkan lewat GPO satu kali untuk seluruh OU komputer:

```powershell
Enable-PSRemoting -Force
Set-NetFirewallRule -Name "WINRM-HTTP-In-TCP" -Enabled True
```

Remote scanner akan melakukan preflight ke port WinRM `5985`. Target yang belum
terbuka akan diberi status `skipped` di CSV, bukan membuat scan berhenti total.

Mode ini cocok untuk inventory sekali jalan. Untuk sinkron otomatis harian,
pakai opsi install agent via GPO/Intune/PDQ.

## Opsi Remote Scan By IP Segment

Gunakan opsi ini kalau daftar hostname belum rapi, tapi segment IP sudah jelas.
Default wrapper sudah disiapkan untuk segment:

```text
10.62.38.1-254
10.62.39.1-254
10.62.36.1-254
```

Jalankan:

```cmd
RUN-SCAN-SEGMENTS.cmd
```

Saat prompt muncul:

- base IP prefix: tekan Enter untuk `10.62`
- segment: tekan Enter untuk `38,39,36`
- mulai host: tekan Enter untuk `1`
- akhir host: tekan Enter untuk `254`
- server API: tekan Enter untuk
  `https://app.it-ticketing.web.id/api/asset-sync`
- factory/location dan department: dipakai sebagai nilai awal untuk aset baru;
  data organisasi aset lama yang sudah terisi tidak ditimpa
- masukkan Asset Sync token
- masukkan credential admin target
- periksa ringkasan target, lalu jawab `Y` untuk mulai

Command manual:

```powershell
.\Scan-ZinusAssetsRemote.ps1 `
  -IpSegment "10.62.38","10.62.39","10.62.36" `
  -StartHost 1 `
  -EndHost 254 `
  -Token "YOUR_TOKEN" `
  -Credential (Get-Credential)
```

Untuk pilot kecil dulu:

```powershell
.\Scan-ZinusAssetsRemote.ps1 `
  -IpSegment "10.62.38" `
  -StartHost 1 `
  -EndHost 20 `
  -Token "YOUR_TOKEN" `
  -Credential (Get-Credential)
```

## Opsi 1 - GPO Startup Script

1. Simpan folder `ZinusAssetInstaller` di shared path yang bisa dibaca komputer
   domain, contoh:

   ```powershell
   \\fileserver\it-tools\ZinusAssetInstaller
   ```

2. Buat atau edit Group Policy:

   ```text
   Computer Configuration
   > Windows Settings
   > Scripts (Startup/Shutdown)
   > Startup
   ```

3. Tambahkan script ini:

   ```cmd
   \\fileserver\it-tools\ZinusAssetInstaller\Install-ZinusAssetSync-Silent.cmd
   ```

4. Tambahkan argumen setelah path `.cmd`:

   ```cmd
   -Token "YOUR_TOKEN" -Factory "GCI-HWANG" -Department "IT" -ServerUrl "https://app.it-ticketing.web.id/api/asset-sync" -SkipRun
   ```

5. Jalankan `gpupdate /force` atau tunggu policy refresh. Agent akan terpasang
   saat komputer reboot/startup.

## Opsi 2 - Intune, SCCM, atau PDQ

Gunakan command install:

```cmd
Install-ZinusAssetSync-Silent.cmd -Token "YOUR_TOKEN" -Factory "GCI-HWANG" -Department "IT" -ServerUrl "https://app.it-ticketing.web.id/api/asset-sync" -SkipRun
```

`-Token` wajib diisi. Setting RustDesk opsional dan harus dikirim eksplisit
jika diperlukan:

```cmd
-RustdeskIdServer "YOUR_SERVER" -RustdeskRelayServer "YOUR_RELAY" -RustdeskKey "YOUR_KEY"
```

Detection path yang disarankan:

```text
C:\ProgramData\ZinusAssetSync\config.json
```

## Opsi 3 - Bulk Push Dengan PowerShell Remoting

Buat file teks berisi satu hostname atau IP per baris:

```text
PC-001
PC-002
LAPTOP-003
```

Simpan file tersebut sebagai `computers.txt` di folder installer, lalu jalankan:

```cmd
RUN-DEPLOY-ALL.cmd
```

Wrapper ini akan meminta Asset Sync token dan credential admin target.

Atau jalankan deployer PowerShell langsung dari PowerShell elevated di admin
machine:

```powershell
.\Deploy-ZinusAssetSync.ps1 `
  -ComputerList .\computers.txt `
  -Token "YOUR_TOKEN" `
  -Factory "GCI-HWANG" `
  -Department "IT" `
  -ServerUrl "https://app.it-ticketing.web.id/api/asset-sync" `
  -RunNow
```

Jika user Windows saat ini bukan admin di target device:

```powershell
$cred = Get-Credential
.\Deploy-ZinusAssetSync.ps1 -ComputerList .\computers.txt -Token "YOUR_TOKEN" -Credential $cred -RunNow
```

Hasil deployment disimpan ke:

```text
.\zinus-asset-deploy-results.csv
```

## Syarat Bulk Push

- PowerShell Remoting aktif di komputer target.
- Firewall mengizinkan WinRM.
- Akun deployment punya hak local admin di komputer target.
- Komputer target bisa mengakses URL ticketing server.

Untuk environment domain, GPO atau Intune biasanya lebih stabil daripada direct
remote push karena instalasi akan retry otomatis saat laptop online.
