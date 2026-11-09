<?php

namespace Tests\Feature;

use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TirtafieldWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_splash_screen_renders()
    {
        $response = $this->get('/splash');
        $response->assertStatus(200)
            ->assertSee('ABSENSI LAPANGAN')
            ->assertSee('PERUMDA AIR MINUM TIRTA KEPRI')
            ->assertSee('Memuat Data Sistem...');
    }

    public function test_login_page_renders()
    {
        $response = $this->get('/login');
        $response->assertStatus(200)
            ->assertSee('ABSENSI LAPANGAN')
            ->assertSee('NIK')
            ->assertSee('PIN Keamanan')
            ->assertDontSee('PILIH AKUN LOGIN CEPAT')
            ->assertDontSee('<header', false);

        $portalResponse = $this->get('/portal-login');
        $portalResponse->assertStatus(200)
            ->assertDontSee('<header', false);
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $empRes = $this->get('/employees');
        $empRes->assertRedirect('/login');

        $appRes = $this->get('/approvals');
        $appRes->assertRedirect('/login');
    }

    public function test_web_login_flow()
    {
        $response = $this->post('/login', [
            'nik' => 'P242101',
            'pin' => '123456',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_dashboard_renders_with_auth()
    {
        $user = User::where('nik', 'P242101')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200)
            ->assertSee('<header', false)
            ->assertSee('Total Karyawan')
            ->assertSee('Hadir Hari Ini')
            ->assertSee('Persetujuan &amp; Riwayat Presensi Lapangan', false)
            ->assertDontSee('Monitor Presensi Hari Ini')
            ->assertDontSee('title="Notifikasi"', false)
            ->assertDontSee('title="Keluar sesi"', false);
    }

    public function test_employees_page_renders()
    {
        $user = User::where('nik', 'P242101')->first();

        $response = $this->actingAs($user)->get('/employees');
        $response->assertStatus(200)
            ->assertSee('Manajemen Pegawai')
            ->assertSee('Tambah Pegawai Baru')
            ->assertSee('IR. H. ABDUL KHOLIK F, M.M.');
    }

    public function test_approvals_page_renders()
    {
        $user = User::where('nik', 'D001')->first();

        $response = $this->actingAs($user)->get('/approvals');
        $response->assertStatus(200)
            ->assertSee('Manajemen Approval Presensi')
            ->assertSee('Menunggu Review');
    }

    public function test_settings_page_renders()
    {
        $user = User::where('nik', 'P242101')->first();

        $response = $this->actingAs($user)->get('/settings');
        $response->assertStatus(200)
            ->assertSee('Pengaturan Sistem Presensi')
            ->assertSee('4 Slot Waktu Presensi Harian')
            ->assertSee('Geofencing');
    }

    public function test_karyawan_dashboard_renders_hero_clock_and_slots()
    {
        $user = User::where('nik', 'EMP001')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200)
            ->assertSee('Budi Santoso')
            ->assertSee('Presensi Hari Ini')
            ->assertSee('4 Sesi Presensi')
            ->assertSee('Presensi Masuk')
            ->assertSee('Presensi Istirahat Keluar')
            ->assertSee('Presensi Istirahat Masuk')
            ->assertSee('Presensi Pulang')
            ->assertSee('Penempatan:');
    }

    public function test_direktur_dashboard_renders_executive_kpis_and_approvals()
    {
        $user = User::where('nik', 'D001')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200)
            ->assertSee('Ringkasan Operasional &amp; Persetujuan', false)
            ->assertSee('Karyawan Lapangan')
            ->assertSee('Menunggu Review')
            ->assertDontSee('Executive Monitoring')
            ->assertDontSee('Monitor Presensi Hari Ini');
    }

    public function test_switch_role_flow()
    {
        $response = $this->get('/switch-role/karyawan');
        $response->assertRedirect('/dashboard');
        $this->assertEquals('EMP001', auth()->user()->nik);

        $response = $this->get('/switch-role/direktur');
        $response->assertRedirect('/dashboard');
        $this->assertEquals('D001', auth()->user()->nik);

        $response = $this->get('/switch-role/staffIT');
        $response->assertRedirect('/dashboard');
        $this->assertEquals('P242101', auth()->user()->nik);
    }

    public function test_direktur_can_view_attendance_detail_modal_elements()
    {
        $user = User::where('nik', 'D001')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200)
            ->assertSee('Rincian Riwayat Presensi')
            ->assertSee('Bukti Swafoto Presensi')
            ->assertSee('Waktu Perekaman Presensi')
            ->assertSee('Keterangan Keperluan Presensi')
            ->assertSee('Lokasi & Jarak ke Kantor Penempatan', false)
            ->assertSee('Swafoto Asli Kamera')
            ->assertSee('Ketuk Perbesar')
            ->assertSee('Lihat Penuh')
            ->assertSee('Buka di Maps')
            ->assertSee('Kamera Asli Terverifikasi');
    }

    public function test_superadmin_can_view_attendance_detail_modal_elements()
    {
        $user = User::where('nik', 'P242101')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200)
            ->assertSee('Rincian Riwayat Presensi')
            ->assertSee('Bukti Swafoto Presensi')
            ->assertSee('Lokasi & Jarak ke Kantor Penempatan', false)
            ->assertSee('attendanceDetailMap');
    }

    public function test_presensi_model_to_detail_array_matches_flutter_schema()
    {
        $presensi = \App\Models\Presensi::first();
        $this->assertNotNull($presensi);

        $detail = $presensi->toDetailArray();
        $this->assertArrayHasKey('userName', $detail);
        $this->assertArrayHasKey('nip', $detail);
        $this->assertArrayHasKey('department', $detail);
        $this->assertArrayHasKey('recordedTimeStr', $detail);
        $this->assertArrayHasKey('status', $detail);
        $this->assertArrayHasKey('isLate', $detail);
        $this->assertArrayHasKey('absenUntukApa', $detail);
        $this->assertArrayHasKey('locationName', $detail);
        $this->assertArrayHasKey('address', $detail);
        $this->assertArrayHasKey('latitude', $detail);
        $this->assertArrayHasKey('longitude', $detail);
        $this->assertArrayHasKey('distancePlacement', $detail);
        $this->assertArrayHasKey('officePlacement', $detail);
        $this->assertArrayHasKey('targetLat', $detail);
        $this->assertArrayHasKey('targetLng', $detail);
        $this->assertArrayHasKey('targetRad', $detail);
        $this->assertArrayHasKey('fotoUrl', $detail);
        $this->assertArrayHasKey('slotTitle', $detail);
        $this->assertArrayHasKey('isApproval', $detail);

        $presensi->tipe_pengajuan = 'luarRadius';
        $presensi->approval_status = 'pending';
        $detailLuar = $presensi->toDetailArray();
        $this->assertEquals('Luar Radius', $detailLuar['tipe_pengajuan']);
        $this->assertEquals('Pengajuan Luar Radius', $detailLuar['slotTitle']);
        $this->assertEquals('Luar Radius', $presensi->formatted_tipe_pengajuan);
    }

    public function test_karyawan_and_direktur_account_profile_fields_locked_for_superadmin()
    {
        // 1. Karyawan Profile
        $karyawan = User::where('nik', 'EMP001')->first();
        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('Informasi Kepegawaian')
            ->assertSee('Nomor Induk Karyawan (NIK)')
            ->assertSee('Hanya Superadmin yang dapat merubah')
            ->assertDontSee('Hanya admin yang dapat merubah');

        // 2. Direktur Profile (AdminAkunScreen.dart)
        $direktur = User::where('nik', 'D001')->first();
        $resDirektur = $this->actingAs($direktur)->get('/dashboard');
        $resDirektur->assertStatus(200)
            ->assertSee('Informasi Kepegawaian Admin')
            ->assertSee('Hanya Superadmin yang dapat merubah')
            ->assertSee('Simpan Pengaturan Akun Admin')
            ->assertSee('Ubah PIN Otorisasi Direktur')
            ->assertSee('Notifikasi Presensi Lapangan')
            ->assertSee('Notifikasi Persetujuan Masuk')
            ->assertDontSee('Hanya admin yang dapat merubah');
    }

    public function test_superadmin_has_editable_profile_and_save_button()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $resSuperadmin = $this->actingAs($superadmin)->get('/dashboard');
        $resSuperadmin->assertStatus(200)
            ->assertSee('Informasi Kepegawaian Superadmin')
            ->assertSee('Akses Superadmin')
            ->assertSee('Simpan Pengaturan Akun Superadmin')
            ->assertSee('Edit Data Pegawai');
    }

    public function test_superadmin_profile_screen_structure_and_modals()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $res = $this->actingAs($superadmin)->get('/dashboard');

        $res->assertStatus(200)
            // Header / Shell
            ->assertSee('Akun & Profil Superadmin')
            // Module 1: Hero Profile Card
            ->assertSee('Foto Profil Superadmin')
            ->assertSee('Ubah Foto Profil')
            ->assertSee('NIK:')
            // Module 2: Informasi Kepegawaian Superadmin
            ->assertSee('Informasi Kepegawaian Superadmin')
            ->assertSee('Akses Superadmin')
            ->assertSee('Nama Lengkap')
            ->assertSee('Bidang')
            ->assertSee('Seksi Bidang')
            ->assertSee('Nomor WhatsApp / HP')
            ->assertSee('Kantor Penempatan (Acuan Radius Presensi)')
            ->assertSee('Titik acuan operasional dan konfigurasi pemantauan wilayah')
            ->assertSee('Ubah PIN Otorisasi Superadmin')
            ->assertSee('Terakhir diperbarui 30 hari yang lalu')
            // Module 3: Pengaturan & Preferensi
            ->assertSee('Pengaturan & Preferensi')
            ->assertSee('Notifikasi Sistem & Presensi')
            ->assertSee('Peringatan anomali presensi dan sinkronisasi server Pusimpel')
            ->assertSee('Pengingat Pencadangan Otomatis')
            ->assertSee('Pemberitahuan berkala jadwal pencadangan database & sistem')
            // Module 4: Actions
            ->assertSee('Simpan Pengaturan Akun Superadmin')
            ->assertSee('Keluar dari Akun Superadmin')
            // Modals & Sheets
            ->assertSee('Pilih Foto Profil Superadmin')
            ->assertSee('Ambil Foto Kamera')
            ->assertSee('Pilih dari Galeri')
            ->assertSee('Apakah Anda yakin ingin keluar dari sesi Superadmin IT saat ini?');
    }

    public function test_admin_direktur_akun_screen_structure_and_modals()
    {
        $direktur = User::where('nik', 'D001')->first();
        $res = $this->actingAs($direktur)->get('/dashboard');

        $res->assertStatus(200)
            ->assertSee('Akun & Profil Admin')
            ->assertSee('Informasi Kepegawaian Admin')
            ->assertSee('Nomor WhatsApp / HP')
            ->assertSee('Kantor Penempatan (Acuan Radius Presensi)')
            ->assertSee('Titik acuan operasional dan pemantauan presensi wilayah')
            ->assertSee('Ubah PIN Otorisasi Direktur')
            ->assertSee('Pengaturan & Preferensi', false)
            ->assertSee('Notifikasi Presensi Lapangan')
            ->assertSee('Peringatan instan presensi di luar radius atau terlambat')
            ->assertSee('Notifikasi Persetujuan Masuk')
            ->assertSee('Pemberitahuan saat ada permohonan koreksi atau izin pegawai')
            ->assertSee('Simpan Pengaturan Akun Admin')
            ->assertSee('Keluar dari Akun Admin')
            ->assertSee('Pilih Foto Profil Admin')
            ->assertSee('Ambil Foto Kamera')
            ->assertSee('Pilih dari Galeri')
            ->assertSee('Ubah PIN Otorisasi Admin')
            ->assertSee('Simpan PIN Baru');
    }

    public function test_superadmin_operational_screen_structure_and_modals()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $res = $this->actingAs($superadmin)->get('/dashboard');

        $res->assertStatus(200)
            ->assertSee('Jam Operasional')
            ->assertSee('Titik Kantor')
            ->assertSee('Pilih Hari Operasional (Senin - Minggu)')
            ->assertSee('Pilih hari terlebih dahulu untuk mengatur jam kerja khusus')
            ->assertSee('Absensi 1: Masuk')
            ->assertSee('Absensi 2: Istirahat Keluar')
            ->assertSee('Absensi 3: Istirahat Masuk')
            ->assertSee('Absensi 4: Pulang')
            ->assertSee('Toleransi Keterlambatan Hari')
            ->assertSee('Kunci Tombol di Luar Jadwal')
            ->assertSee('Simpan Jadwal Hari')
            ->assertSee('Pilih Titik Kantor Pratinjau')
            ->assertSee('Daftar Titik Kantor')
            ->assertSee('Tambah Titik Baru')
            ->assertSee('Simpan Acuan Lokasi Kantor')
            ->assertSee('Tambah Titik Lokasi Kantor Baru')
            ->assertSee('Klasifikasi Titik Kantor:')
            ->assertSee('Tautan Google Maps / Share Lokasi:')
            ->assertSee('Radius Presensi Geofence:')
            ->assertSee('Titik Koordinat Manual:')
            ->assertSee('Hapus Titik Kantor');
    }

    public function test_superadmin_employee_screen_structure_and_modals()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $res = $this->actingAs($superadmin)->get('/dashboard');

        $res->assertStatus(200)
            // Segmented subtab controller
            ->assertSee('Peran & Akses', false)
            ->assertSee('Data Pegawai')
            // Sub-tab 0: Peran & Akses
            ->assertSee('Role')
            ->assertSee('Superadmin')
            ->assertSee('Admin')
            ->assertSee('Pengguna')
            ->assertSee('Cari akun nama, NIK, atau bidang...')
            ->assertSee('Reset PIN')
            ->assertSee('Ubah Role')
            // Sub-tab 1: Data Pegawai
            ->assertSee('Direktori Kepegawaian')
            ->assertSee('Cari nama pegawai, NIK, bidang, kantor...')
            ->assertSee('Semua Kantor')
            ->assertSee('Tambah Pegawai Baru')
            ->assertSee('Detail')
            ->assertSee('Ubah Data')
            // Modals & Sheets
            ->assertSee('Urutkan Direktori Pegawai')
            ->assertSee('Pilih kriteria urutan daftar pegawai & akun', false)
            ->assertSee('Nama (A - Z)')
            ->assertSee('Nama (Z - A)')
            ->assertSee('NIK (Terkecil - Terbesar)')
            ->assertSee('NIK (Terbesar - Terkecil)')
            ->assertSee('Ubah Peran & Hak Akses', false)
            ->assertSee('Simpan Perubahan Peran')
            ->assertSee('Reset PIN Karyawan')
            ->assertSee('Simpan PIN Baru')
            ->assertSee('Ubah Data Pegawai')
            ->assertSee('Tutup Detail Pegawai');
    }

    public function test_superadmin_dashboard_screen_structure_and_modals()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $res = $this->actingAs($superadmin)->get('/dashboard');

        $res->assertStatus(200)
            // Module 0: Header Banner
            ->assertSee('Aplikasi Presensi Lapangan')
            ->assertSee('Perusahaan Umum Daerah<br>Air Minum Tirta Kepri', false)
            ->assertSee('Budaya Bersih & Melayani', false)
            ->assertSee('Superadmin IT')
            // Module 1: Ringkasan Operasional & Persetujuan (_buildMainStatisticsBento)
            ->assertSee('Ringkasan Operasional &amp; Persetujuan', false)
            ->assertSee('Total Pegawai')
            ->assertSee('Persetujuan')
            ->assertSee('Disetujui')
            // Module 2: Menu Kontrol Superadmin (_buildCoreFeatureGrid)
            ->assertSee('Menu Kontrol Superadmin')
            ->assertSee('Peran &amp; Akun', false)
            ->assertSee('Data Pegawai')
            ->assertSee('Jam Operasional')
            ->assertSee('Lokasi Kantor')
            ->assertSee('Log Aktivitas Sistem')
            ->assertSee('Audit trail, penempatan kantor, perubahan jam kerja &amp; absensi', false)
            // Module 3: Persetujuan & Riwayat Presensi Lapangan (SuperadminMonitoringScreen)
            ->assertSee('Persetujuan &amp; Riwayat Presensi Lapangan', false)
            ->assertSee('Hadir Hari Ini')
            ->assertSee('Tepat Waktu')
            ->assertSee('Terlambat')
            ->assertSee('Cari nama pegawai, NIK, atau keterangan presensi...')
            ->assertSee('Menunggu')
            ->assertSee('Disetujui')
            ->assertSee('Ditolak')
            ->assertSee('Lihat Rincian')
            ->assertSee('Disetujui pada pukul')
            ->assertSee('WIB oleh')
            // Module 4: Superadmin Activity Log Modal (SuperadminActivityLogScreen)
            ->assertSee('Log Aktivitas Superadmin')
            ->assertSee('Audit Trail &amp; Rekam Jejak Sistem', false)
            ->assertSee('Total Aktivitas')
            ->assertSee('Rekam Jejak')
            ->assertSee('Aksi Aktif')
            ->assertSee('Cari aksi, nama aktor, NIK, atau objek...')
            ->assertSee('Semua Kategori')
            ->assertSee('Pegawai &amp; Akun', false)
            ->assertSee('Keamanan &amp; Sesi', false)
            ->assertSee('Rincian Rekam Jejak Audit (Audit Trail):', false)
            ->assertSee('Salin Log');
    }

    public function test_admin_direktur_dashboard_screen_structure_and_approvals()
    {
        $direktur = User::where('nik', 'D001')->first();
        $res = $this->actingAs($direktur)->get('/dashboard');

        $res->assertStatus(200)
            // Module 0: Executive Header Banner
            ->assertSee('Aplikasi Presensi Lapangan')
            ->assertSee('Perusahaan Umum Daerah<br>Air Minum Tirta Kepri', false)
            ->assertSee('Budaya Bersih &amp; Melayani', false)
            ->assertSee('Direktur Utama')
            // Module 1: Statistik Utama Direktur (_buildMainStatisticsBento)
            ->assertSee('Ringkasan Operasional &amp; Persetujuan', false)
            ->assertSee('Total Pegawai')
            ->assertSee('Karyawan Lapangan')
            ->assertSee('Persetujuan')
            ->assertSee('Menunggu Review')
            ->assertSee('Disetujui')
            ->assertSee('Telah Disetujui')
            // Module 2: Live Presence Summary Badges (_buildLivePresenceSummary)
            ->assertSee('Hadir Hari Ini')
            ->assertSee('Tepat Waktu')
            ->assertSee('Terlambat')
            // Module 3: Sleek Search Bar (_buildSearchBar)
            ->assertSee('Cari nama pegawai, NIK, atau keterangan presensi...')
            // Module 4: Status Segmented Tabs (_buildStatusTabBar)
            ->assertSee('Menunggu')
            ->assertSee('Disetujui')
            ->assertSee('Ditolak')
            // Module 5: Approval Cards & Empty States
            ->assertSee('Lihat Rincian')
            ->assertSee('Disetujui pada pukul')
            ->assertSee('WIB oleh')
            ->assertSee('Setujui')
            ->assertSee('Tolak')
            ->assertSee('Belum Ada Riwayat Ditolak');
    }

    public function test_karyawan_akun_screen_has_camera_gallery_and_pin_modal()
    {
        $karyawan = User::where('role', 'karyawan')->first();
        $res = $this->actingAs($karyawan)->get('/dashboard');

        $res->assertStatus(200)
            ->assertSee('Pilih Foto Profil Karyawan')
            ->assertSee('Ambil Foto Kamera')
            ->assertSee('Pilih dari Galeri')
            ->assertSee('Ubah PIN Presensi & Keamanan', false)
            ->assertSee('Simpan PIN Baru');
    }

    public function test_karyawan_and_direktur_view_reflects_superadmin_operational_tab_configuration()
    {
        // 1. Simpan konfigurasi jam operasional khusus untuk hari ini
        $todayDow = \Carbon\Carbon::now('Asia/Jakarta')->dayOfWeekIso;
        $dayName = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][$todayDow - 1];

        $settings = \App\Models\SystemSetting::getAllSettings();
        $dailySchedules = $settings['operational_hours']['daily_schedules'] ?? [];
        $found = false;
        foreach ($dailySchedules as &$ds) {
            if (($ds['day_of_week'] ?? 0) === $todayDow) {
                $ds['masuk'] = '07:45';
                $ds['istirahat_keluar'] = '11:30';
                $ds['istirahat_masuk'] = '13:00';
                $ds['pulang'] = '16:45';
                $ds['toleransi_menit'] = 20;
                $ds['is_work_day'] = true;
                $found = true;
                break;
            }
        }
        unset($ds);
        if (!$found) {
            $dailySchedules[] = [
                'day_of_week'     => $todayDow,
                'day_name'        => $dayName,
                'is_work_day'     => true,
                'masuk'           => '07:45',
                'istirahat_keluar'=> '11:30',
                'istirahat_masuk' => '13:00',
                'pulang'          => '16:45',
                'toleransi_menit' => 20,
            ];
        }
        $settings['operational_hours']['daily_schedules'] = $dailySchedules;
        \App\Models\SystemSetting::updateSetting('operational_hours', $settings['operational_hours']);

        // Pastikan kantor utama memiliki radius 200m
        $hq = \App\Models\Office::find('hq');
        if ($hq) {
            $hq->radius = 200;
            $hq->save();
        }

        // 2. Karyawan mengakses dashboard: jam kerja & radius terkonfigurasi muncul di tampilan
        $karyawan = User::where('role', 'karyawan')->first();
        $karyawan->office_id = 'hq';
        $karyawan->save();

        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('Radius 200m')
            ->assertSee('07:45')
            ->assertSee('Toleransi 20 mnt')
            ->assertSee('11:30')
            ->assertSee('16:45');

        // 3. Direktur mengakses dashboard: pastikan section konfigurasi operasional & radius kantor TIDAK ditampilkan pada akun Direktur
        $direktur = User::where('role', 'direktur')->first();
        $resDirektur = $this->actingAs($direktur)->get('/dashboard');
        $resDirektur->assertStatus(200)
            ->assertDontSee('Konfigurasi Operasional &amp; Radius Kantor', false)
            ->assertDontSee('Sinkronisasi acuan presensi dari Tab Operasional Superadmin', false);
    }

    public function test_maps_render_satellite_mode_and_switcher_controls()
    {
        // 1. Karyawan dashboard memuat container peta verifikasi lokasi dan kontrol mode satelit
        $karyawan = User::where('role', 'karyawan')->first();
        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('id="attendanceMap"', false)
            ->assertSee('satellite_alt')
            ->assertSee('Satelit')
            ->assertSee('setMapMode')
            ->assertSee('lyrs=y')
            ->assertSee('AIzaSyDpm1SPneUjheEMnk3TDOP38js6TQLmXps');

        // 2. Halaman Approvals memuat modal detail presensi dengan peta satelit dan switcher
        $direktur = User::where('role', 'direktur')->first();
        $resApprovals = $this->actingAs($direktur)->get('/approvals');
        $resApprovals->assertStatus(200)
            ->assertSee('id="attendanceDetailMap"', false)
            ->assertSee('setDetailMapMode')
            ->assertSee('Satelit')
            ->assertSee('&t=k', false);
    }

    public function test_alarm_and_device_notification_banner_lockscreen_and_notification_center()
    {
        // 1. File Service Worker dan Manifest PWA dapat diakses
        $resManifest = $this->get('/manifest.json');
        $resManifest->assertStatus(200)
            ->assertSee('Tirtafield - Presensi Mobile')
            ->assertSee('standalone');

        $resSw = $this->get('/sw.js');
        $resSw->assertStatus(200)
            ->assertSee('SHOW_NOTIFICATION')
            ->assertSee('requireInteraction')
            ->assertSee('notificationclick');

        // 2. Karyawan dashboard memuat aset Alarm Engine, Banner Notifikasi, Modal Dering, dan Modal Pengaturan
        $karyawan = User::where('role', 'karyawan')->first();
        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('manifest.json')
            ->assertSee('js/tirtafield-alarm.js')
            ->assertSee('Banner Pemberitahuan &amp; Alarm', false)
            ->assertSee('alarmBannerVisible')
            ->assertSee('isAlarmModalOpen')
            ->assertSee('alarmSettingsModal')
            ->assertSee('Layar Terkunci', false)
            ->assertSee('Pusat Pemberitahuan', false)
            ->assertSee('Uji Alarm &amp; Kirim Notifikasi Sekarang', false);
    }

    public function test_mobile_pwa_notification_settings_and_preferences_across_all_roles()
    {
        // 1. Script Alarm Engine mendukung status Standalone PWA, Unlock Audio, dan Web Worker fallback
        $this->assertFileExists(public_path('js/tirtafield-alarm.js'));
        $alarmScript = file_get_contents(public_path('js/tirtafield-alarm.js'));
        $this->assertStringContainsString('isStandalone', $alarmScript);
        $this->assertStringContainsString('unlockAudio', $alarmScript);
        $this->assertStringContainsString('showDeviceNotification', $alarmScript);
        $this->assertStringContainsString('playAlarmSound', $alarmScript);
        $this->assertStringContainsString('requireInteraction', $alarmScript);

        // 2. Karyawan: Memiliki preferensi melodi suara, getaran perangkat, layar terkunci & fungsi simpan
        $karyawan = User::where('role', 'karyawan')->first();
        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('alarmSoundEnabled')
            ->assertSee('alarmVibrationEnabled')
            ->assertSee('alarmDeviceNotificationEnabled')
            ->assertSee('saveAlarmPreferences()')
            ->assertSee('Preferensi Suara &amp; Perangkat Mobile', false);

        // 3. Direktur: Memiliki toggle notifikasi presensi, approval, tombol uji coba notifikasi mobile, dan simpan
        $direktur = User::where('role', 'direktur')->first();
        $resDirektur = $this->actingAs($direktur)->get('/dashboard');
        $resDirektur->assertStatus(200)
            ->assertSee('adminPresenceAlerts')
            ->assertSee('adminApprovalAlerts')
            ->assertSee('testAdminNotification()')
            ->assertSee('Uji Coba Notifikasi Mobile (Banner &amp; Layar Terkunci)', false)
            ->assertSee('saveAdminProfile()');

        // 4. Superadmin: Memiliki toggle notifikasi sistem & backup, tombol uji coba notifikasi mobile, dan simpan
        $superadmin = User::where('nik', 'P242101')->first();
        $resSuperadmin = $this->actingAs($superadmin)->get('/dashboard');
        $resSuperadmin->assertStatus(200)
            ->assertSee('superadminSystemAlerts')
            ->assertSee('superadminBackupAlerts')
            ->assertSee('testSuperadminNotification()')
            ->assertSee('Uji Coba Notifikasi Sistem (Banner &amp; Layar Terkunci)', false)
            ->assertSee('saveSuperadminProfile()');
    }

    public function test_admin_and_superadmin_header_banner_two_lines_and_text_only_preview()
    {
        // 1. Verifikasi Admin (Direktur): Header banner 2 baris dan preview presensi hanya teks
        $direktur = User::where('nik', 'D001')->first();
        $resDirektur = $this->actingAs($direktur)->get('/dashboard');
        $resDirektur->assertStatus(200)
            ->assertSee('Perusahaan Umum Daerah<br>Air Minum Tirta Kepri', false)
            ->assertDontSee('Perusahaan Umum Daerah Air Minum Tirta Kepri', false)
            ->assertDontSee('Demo Role:')
            ->assertDontSee('/switch-role/karyawan')
            ->assertSee('Hanya Teks Saat Preview', false)
            ->assertSee('Bukti Swafoto Presensi', false)
            ->assertSee('detailData.fotoUrl', false);

        // 2. Verifikasi Superadmin (Staff IT): Header banner 2 baris dan preview presensi hanya teks
        $superadmin = User::where('nik', 'P242101')->first();
        $resSuperadmin = $this->actingAs($superadmin)->get('/dashboard');
        $resSuperadmin->assertStatus(200)
            ->assertSee('Perusahaan Umum Daerah<br>Air Minum Tirta Kepri', false)
            ->assertDontSee('Perusahaan Umum Daerah Air Minum Tirta Kepri', false)
            ->assertDontSee('Demo Role:')
            ->assertDontSee('/switch-role/karyawan')
            ->assertSee('Hanya Teks Saat Preview', false)
            ->assertSee('Bukti Swafoto Presensi', false)
            ->assertSee('detailData.fotoUrl', false);
    }

    public function test_karyawan_attendance_session_locked_when_not_time_yet()
    {
        $karyawan = User::where('role', 'karyawan')->first();

        // 1. Script Alpine memuat validasi canStartAttendance dan penjagaan di startAttendance
        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('canStartAttendance(slotType)')
            ->assertSee('const check = this.canStartAttendance(slotType);', false)
            ->assertSee('if (!check.allowed) {', false)
            ->assertSee('this.showLockedToast(check.message);', false)
            ->assertSee('myAttendanceSlots:');

        // 2. Simulasi Waktu Pagi Sebelum Jam Buka (Pukul 05:00 WIB di hari Selasa)
        // Jadwal Selasa: Masuk 08:00, jam buka 07:00
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-10 05:00:00', 'Asia/Jakarta'));
        $resEarly = $this->actingAs($karyawan)->get('/dashboard');
        $resEarly->assertStatus(200)
            ->assertSee('Sesi Pagi · Belum Waktunya')
            ->assertSee('Dibuka mulai pukul 07:00 WIB', false)
            ->assertSee('Sesi Presensi Masuk belum dapat dibuka!')
            ->assertDontSee('Lakukan Presensi Masuk');

        // 3. Simulasi Waktu Kerja Siang Sebelum Jam Istirahat (Pukul 10:00 WIB)
        // Belum jam istirahat keluar (12:00) dan belum jam pulang (17:00)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta'));
        $resMid = $this->actingAs($karyawan)->get('/dashboard');
        $resMid->assertStatus(200)
            ->assertSee('Sesi Siang · Belum Dimulai')
            ->assertSee('Sesi Sore · Belum Dimulai');

        // 4. Simulasi Hari Libur (Hari Minggu)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-15 10:00:00', 'Asia/Jakarta'));
        $resHoliday = $this->actingAs($karyawan)->get('/dashboard');
        $resHoliday->assertStatus(200)
            ->assertSee('Hari Libur Operasional')
            ->assertSee('Hari ini adalah hari libur operasional. Sesi presensi tidak dapat dibuka.', false)
            ->assertDontSee('Lakukan Presensi Masuk')
            ->assertDontSee('Lakukan Presensi Istirahat')
            ->assertDontSee('Lakukan Presensi Pulang');

        // Reset waktu uji coba
        \Carbon\Carbon::setTestNow();
    }

    public function test_approvals_after_day_change_disappear_from_admin_superadmin_view_but_persist_in_database()
    {
        $karyawan = User::where('role', 'karyawan')->first();
        $direktur = User::where('role', 'direktur')->first();
        $superadmin = User::where('role', 'staffIT')->first();

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $yesterday = Carbon::now('Asia/Jakarta')->subDay()->toDateString();

        // 1. Buat record approval kemarin: approved & rejected
        $oldApproved = Presensi::create([
            'nik'             => $karyawan->nik,
            'pgw_id'          => $karyawan->id,
            'pgw_nama'        => $karyawan->name,
            'tanggal'         => $yesterday,
            'slot_type'       => 'pulang',
            'waktu'           => $yesterday . ' 17:30:00',
            'approval_status' => 'approved',
            'reviewed_by'     => 'Direktur Test',
            'reviewed_at'     => Carbon::parse($yesterday . ' 18:00:00', 'Asia/Jakarta'),
            'catatan'         => 'Persetujuan kemarin yang sudah disetujui_OLD_APP',
            'status'          => 'Tepat Waktu',
            'tipe_pengajuan'  => 'luarRadius',
        ]);

        $oldRejected = Presensi::create([
            'nik'             => $karyawan->nik,
            'pgw_id'          => $karyawan->id,
            'pgw_nama'        => $karyawan->name,
            'tanggal'         => $yesterday,
            'slot_type'       => 'pulang',
            'waktu'           => $yesterday . ' 17:45:00',
            'approval_status' => 'rejected',
            'reviewed_by'     => 'Direktur Test',
            'reviewed_at'     => Carbon::parse($yesterday . ' 18:15:00', 'Asia/Jakarta'),
            'catatan'         => 'Persetujuan kemarin yang sudah ditolak_OLD_REJ',
            'status'          => 'Tepat Waktu',
            'tipe_pengajuan'  => 'luarRadius',
        ]);

        // 2. Buat record approval hari ini: approved hari ini
        $todayApproved = Presensi::create([
            'nik'             => $karyawan->nik,
            'pgw_id'          => $karyawan->id,
            'pgw_nama'        => $karyawan->name,
            'tanggal'         => $today,
            'slot_type'       => 'masuk',
            'waktu'           => $today . ' 08:10:00',
            'approval_status' => 'approved',
            'reviewed_by'     => 'Direktur Test',
            'reviewed_at'     => Carbon::now('Asia/Jakarta'),
            'catatan'         => 'Persetujuan hari ini_TODAY_APP',
            'status'          => 'Tepat Waktu',
            'tipe_pengajuan'  => 'luarRadius',
        ]);

        // 3. Buat record pending kemarin (tetap harus muncul karena belum disetujui/ditolak)
        $pendingOvernight = Presensi::create([
            'nik'             => $karyawan->nik,
            'pgw_id'          => $karyawan->id,
            'pgw_nama'        => $karyawan->name,
            'tanggal'         => $yesterday,
            'slot_type'       => 'pulang',
            'waktu'           => $yesterday . ' 23:45:00',
            'approval_status' => 'pending',
            'catatan'         => 'Lembur pipa bocor_PENDING_STILL_SHOW',
            'status'          => 'Tepat Waktu',
            'tipe_pengajuan'  => 'lemburDiniHari',
        ]);

        // 4. Verifikasi di Tampilan Direktur (Admin)
        $resDirektur = $this->actingAs($direktur)->get('/dashboard');
        $resDirektur->assertStatus(200)
            ->assertSee('Lembur pipa bocor_PENDING_STILL_SHOW')
            ->assertSee('Persetujuan hari ini_TODAY_APP')
            ->assertDontSee('Persetujuan kemarin yang sudah disetujui_OLD_APP')
            ->assertDontSee('Persetujuan kemarin yang sudah ditolak_OLD_REJ');

        // 5. Verifikasi di Tampilan Superadmin (Staff IT)
        $resSuperadmin = $this->actingAs($superadmin)->get('/dashboard');
        $resSuperadmin->assertStatus(200)
            ->assertSee('Lembur pipa bocor_PENDING_STILL_SHOW')
            ->assertSee('Persetujuan hari ini_TODAY_APP')
            ->assertDontSee('Persetujuan kemarin yang sudah disetujui_OLD_APP')
            ->assertDontSee('Persetujuan kemarin yang sudah ditolak_OLD_REJ');

        // 6. Data lama tetap tersimpan di database
        $this->assertDatabaseHas('presensi', ['id' => $oldApproved->id, 'approval_status' => 'approved']);
        $this->assertDatabaseHas('presensi', ['id' => $oldRejected->id, 'approval_status' => 'rejected']);
    }

    public function test_overnight_overtime_employee_can_clock_out_in_dini_hari_and_shows_in_admin_approvals()
    {
        $karyawan = User::where('role', 'karyawan')->first();
        $direktur = User::where('role', 'direktur')->first();

        // 1. Simulasikan karyawan absen masuk kemarin (e.g. 2026-11-09)
        $yesterday = '2026-11-09';
        $today = '2026-11-10';

        // Hapus record tanggal terkait untuk EMP001
        Presensi::where('nik', $karyawan->nik)->whereIn('tanggal', [$yesterday, $today])->delete();

        Presensi::create([
            'nik'             => $karyawan->nik,
            'pgw_id'          => $karyawan->id,
            'pgw_nama'        => $karyawan->name,
            'tanggal'         => $yesterday,
            'slot_type'       => 'masuk',
            'waktu'           => $yesterday . ' 08:00:00',
            'status'          => 'Tepat Waktu',
            'approval_status' => 'approved',
            'tipe_pengajuan'  => 'dalamRadius',
        ]);

        // 2. Set waktu ke dini hari keesokan harinya: pukul 02:30 WIB
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($today . ' 02:30:00', 'Asia/Jakarta'));

        // Dashboard karyawan menampilkan slot pulang aktif khusus lembur dini hari
        $resKaryawan = $this->actingAs($karyawan)->get('/dashboard');
        $resKaryawan->assertStatus(200)
            ->assertSee('LEMBUR DINI HARI')
            ->assertSee('Lakukan Presensi Pulang Lembur');

        // 3. Karyawan melakukan absensi pulang dini hari via API
        $recordRes = $this->actingAs($karyawan)->postJson('/api/attendance/record', [
            'user_id'   => (string)$karyawan->id,
            'nik'       => $karyawan->nik,
            'user_name' => $karyawan->name,
            'slot_type' => 'pulang',
            'location'  => 'Kantor Pusat PERUMDA Air Minum Tirta Kepri',
            'latitude'  => 0.92031,
            'longitude' => 104.46069,
            'jarak_hq'  => '10 m',
            'notes'     => 'Perbaikan kebocoran pipa transmisi hingga dini hari',
        ]);

        $recordRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verifikasi record di database memiliki approval_status pending dan tag lembur
        $pulangRec = Presensi::where('nik', $karyawan->nik)->where('slot_type', 'pulang')->latest('id')->first();
        $this->assertNotNull($pulangRec);
        $this->assertEquals('pending', strtolower($pulangRec->approval_status));
        $this->assertStringContainsString('LEMBUR', strtoupper($pulangRec->catatan));

        // 4. Verifikasi persetujuan lembur dini hari muncul di tampilan Direktur (Admin)
        $resDirektur = $this->actingAs($direktur)->get('/dashboard');
        $resDirektur->assertStatus(200)
            ->assertSee('Perbaikan kebocoran pipa transmisi hingga dini hari');

        \Carbon\Carbon::setTestNow();
    }
}
