# Tirtafield Web (Laravel 11 Edition)

Aplikasi Web dan API Backend Absensi Karyawan **PERUMDA Air Minum Tirta Kepri**, dibangun dengan arsitektur modern **Laravel 11** dengan tampilan visual, alur interaksi, dan fungsi yang **100% identik dengan aplikasi mobile Flutter/Dart**.

Mendukung sistem **Approval Berjenjang**, **4 Slot Presensi Harian**, **Verifikasi Geofencing Peta Interaktif Leaflet**, **Swafoto Kamera Langsung dengan Panduan Biometrik**, **Pusat Review Direksi**, serta **Kompatibilitas Penuh (Dual-Compatibility)** dengan aplikasi Mobile Flutter maupun sistem legacy `*.php`.

---

## 🎨 Tampilan & Fitur Identik Flutter/Dart

| Komponen Mobile Flutter | Implementasi Laravel Web | Deskripsi & Alur Kerja |
| :--- | :--- | :--- |
| **SplashScreen** | Splash Screen (`/` & `/splash`) | Layar pembuka animasi gradien Deep Navy (`#001453` ke `#00288E`), emblem glowing logo, tipografi `TIRTAFIELD`, badge `SISTEM PRESENSI LAPANGAN`, spinner loading, dan auto-navigasi 2.2 detik (persis Flutter `splash_screen.dart`). |
| **CorporateHeroClock** | Hero Digital Clock Card | Jam digital real-time `HH:mm:ss WIB` yang sinkron setiap detik, sapaan dinamis (*Selamat Pagi / Siang / Sore / Malam*), tanggal berbahasa Indonesia, badge penempatan kantor, dan tombol cepat permohonan. |
| **CorporateAttendanceCards** | 4 Sesi Kartu Presensi | Urutan 4 sesi presensi: **1. Masuk (08:00 WIB)**, **2. Istirahat Keluar (12:00 WIB)**, **3. Istirahat Masuk (13:00 WIB)**, dan **4. Pulang (17:00 WIB)** dengan status *Active CTA*, *Completed (Selesai)*, atau *Locked (Terkunci)*. |
| **VerifikasiLokasiScreen** | Modal Peta Interaktif Leaflet | Peta visual real-time menampilkan koordinat GPS pengguna, titik kantor penempatan, garis vektor konektor, lingkaran toleransi radius (200m), dan perhitungan jarak instan (*Di Dalam Radius* vs *Di Luar Radius*). |
| **AmbilFotoAbsensiScreen** | Live Webcam & Panduan Biometrik | Viewfinder kamera langsung via HTML5 `getUserMedia` dengan outline oval wajah, animasi pemindai laser biometrik (*Skor 99.4% Validated*), stempel watermark digital (Waktu, Tanggal, Kantor, Koordinat GPS), dan efek flash rana kamera. |
| **BuktiPresensiBerhasilScreen** | Dialog Tanda Bukti Presensi | Bukti tanda terima digital dengan animasi checklist sukses, rincian waktu presensi, preview swafoto terverifikasi, status radius kantor, dan rekomendasi sesi berikutnya. |
| **AdminMonitoringScreen** | Dashboard Direksi & Approval | Ringkasan KPI eksekutif (Total Karyawan, Hadir, Terlambat, Pending Review) dan tab review permohonan (Menunggu, Disetujui, Ditolak) dengan modal 1-klik setujui/tolak beserta catatan. |
| **DashboardITScreen** | Pusat Kontrol Superadmin IT | Monitoring status server & database real-time, konfigurasi jam kerja 4 slot, manajemen radius geofencing kantor, dan direktori data pegawai. |
| **Bottom Role Switcher** | Demo Switch Role Bar | Bar navigasi bawah untuk beralih peran instan: **1. Karyawan**, **2. Direktur**, dan **3. Staff IT**. |

---

## 👥 3 Tingkatan Role & Hak Akses

| Role | Kategori | Fitur & Wewenang Utama |
| :--- | :--- | :--- |
| **Karyawan** | `karyawan` | • Jam digital real-time `HH:MM:SS WIB`<br>• **4x Slot Presensi**: Masuk, Istirahat Keluar, Istirahat Masuk, Pulang<br>• Verifikasi GPS & Swafoto Berstempel Digital<br>• Pengajuan Permohonan (*Cuti, Sakit, Lembur, Izin, Koreksi Absen*)<br>• Tab Akun Karyawan & status approval pengajuan |
| **Direktur** | `direktur` | • Dashboard eksekutif & monitoring kehadiran harian<br>• **Pusat Approval**: Review permohonan cuti, izin, lembur, dan absensi di luar radius<br>• Persetujuan (*Approve*) atau penolakan (*Reject*) dengan catatan direksi<br>• Lightbox inspeksi swafoto bukti kehadiran pegawai |
| **Staff IT** | `staffIT` | • **Manajemen Pengguna**: Tambah pegawai, ubah penempatan kantor & role, reset PIN<br>• **Pengaturan 4 Slot Jam Kerja**: Jam masuk, istirahat keluar, istirahat masuk, jam pulang, & batas toleransi menit<br>• **Geofencing Kantor**: Titik koordinat GPS dan radius (meter) setiap kantor cabang / instalasi |

---

## 🚀 Panduan Menjalankan di Laragon

### 1. Konfigurasi Lingkungan (`.env`)
Pastikan file `.env` telah disesuaikan:
```env
APP_NAME="Tirtafield Web"
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tirtafield
DB_USERNAME=root
DB_PASSWORD=
```
*(Aplikasi juga mendukung fallback SQLite lokal di `database/database.sqlite` jika MySQL belum aktif)*.

### 2. Jalankan Migrasi & Seeder Database
```bash
php artisan migrate --seed
```
*Atau import langsung file SQL siap pakai: `database/tirtafield_initial.sql` ke database `tirtafield` di HeidiSQL / phpMyAdmin Laragon.*

### 3. Jalankan Aplikasi
Jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Buka browser di: **`http://127.0.0.1:8000`** atau melalui Virtual Host Laragon: **`http://tirtafieldweb.test/`**

---

## 📡 Daftar Endpoint API (Kompatibel Penuh)

### 1. Autentikasi & Profil
- `POST /api/auth/login` atau `POST /api/login.php`
- `POST /api/update-profile` atau `POST /api/update_profile.php`

### 2. Presensi 4 Slot
- `GET /api/attendance/today?user_id={id}` atau `GET /api/attendance.php?action=today`
- `POST /api/attendance/record` atau `POST /api/attendance.php?action=record`
- `GET /api/attendance/history` atau `GET /api/attendance.php?action=history`
- `GET /api/attendance/recap` atau `GET /api/attendance.php?action=recap`

### 3. Approval Berjenjang
- `GET /api/approvals?role={role}&user_id={id}` atau `GET /api/approval.php?action=list`
- `POST /api/approvals/submit` atau `POST /api/approval.php?action=submit`
- `POST /api/approvals/review` atau `POST /api/approval.php?action=review`

### 4. Manajemen Pegawai & Kantor
- `GET /api/employees` atau `GET /api/employees.php?action=list`
- `POST /api/employees` atau `POST /api/employees.php?action=add`
- `PUT /api/employees/{id}` atau `POST /api/employees.php?action=update`
- `DELETE /api/employees/{id}` atau `POST /api/employees.php?action=delete`
- `GET /api/offices` atau `GET /api/offices.php?action=list`

---

## 🧪 Pengujian Unit & Fitur Otomatis
Seluruh 86 assertion pengujian fitur dan API berjalan sukses:
```bash
php artisan test
```
