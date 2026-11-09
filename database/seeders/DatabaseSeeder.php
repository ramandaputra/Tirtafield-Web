<?php

namespace Database\Seeders;

use App\Models\Bidang;
use App\Models\Office;
use App\Models\SeksiBidang;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Offices
        $initialOffices = [
            [
                'id' => 'hq',
                'nama_kantor' => 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang',
                'alamat' => 'Jl. M.T Haryono, Km.3, Tanjungpinang, Kepulauan Riau',
                'latitude' => 0.92031000,
                'longitude' => 104.46069000,
                'radius' => 200,
                'is_primary' => true,
                'is_active' => true,
            ],
            [
                'id' => 'bintan_center',
                'nama_kantor' => 'Unit Pelayanan Bintan Centre',
                'alamat' => 'Komp. Bintan Centre Blok A No. 5-6, Tanjung Pinang',
                'latitude' => 0.92550000,
                'longitude' => 104.47120000,
                'radius' => 60,
                'is_primary' => false,
                'is_active' => true,
            ],
            [
                'id' => 'ipa_sei_pulai',
                'nama_kantor' => 'Instalasi Pengolahan Air (IPA) Sei Pulai',
                'alamat' => 'Kawasan Waduk Sei Pulai, Bintan',
                'latitude' => 0.90810000,
                'longitude' => 104.43250000,
                'radius' => 100,
                'is_primary' => false,
                'is_active' => true,
            ],
            [
                'id' => 'ipa_sei_gesek',
                'nama_kantor' => 'IPA SEI GESEK',
                'alamat' => 'Kawasan Waduk Gesek, Bintan',
                'latitude' => 0.97378400,
                'longitude' => 104.55858000,
                'radius' => 100,
                'is_primary' => false,
                'is_active' => true,
            ],
        ];

        foreach ($initialOffices as $office) {
            Office::updateOrCreate(['id' => $office['id']], $office);
        }

        // 2. Seed Users
        User::updateOrCreate(
            ['nik' => 'P242101'],
            [
                'name' => 'Pusimpel',
                'email' => 'pusimpel@tirtakepri.co.id',
                'pin' => '123456',
                'password' => '123456',
                'role' => 'staffIT',
                'department' => 'PUSIMPEL IT & System',
                'position' => 'Superadmin Pusimpel',
                'phone' => '081234567890',
                'office_id' => 'hq',
                'office_name' => 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang',
                'status_pegawai' => 'Karyawan Tetap',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['nik' => 'D001'],
            [
                'name' => 'IR. H. ABDUL KHOLIK F, M.M.',
                'email' => 'abdul.kholik@tirtakepri.co.id',
                'pin' => '123456',
                'password' => '123456',
                'role' => 'direktur',
                'department' => 'Dewan Direksi',
                'position' => 'Direktur',
                'phone' => '081234567890',
                'office_id' => 'hq',
                'office_name' => 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang',
                'status_pegawai' => 'Direksi',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['nik' => 'EMP001'],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@tirtakepri.co.id',
                'pin' => '123456',
                'password' => '123456',
                'role' => 'karyawan',
                'department' => 'Bidang Hubungan Langganan',
                'position' => 'Seksi Pembacaan Meter',
                'phone' => '081298765432',
                'office_id' => 'hq',
                'office_name' => 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang',
                'status_pegawai' => 'Karyawan Tetap',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['nik' => 'K202609'],
            [
                'name' => 'RAJA HAFIZA RAMANDA PUTRA, S.T',
                'email' => 'ramandaputra@tirtakepri.co.id',
                'pin' => '123456',
                'password' => '123456',
                'role' => 'karyawan',
                'department' => 'Bidang Hubungan Langganan',
                'position' => 'Seksi Pembacaan Meter',
                'phone' => '081298765432',
                'office_id' => 'hq',
                'office_name' => 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang',
                'status_pegawai' => 'Karyawan Tetap',
                'is_active' => true,
            ]
        );

        // 3. Seed Bidang & Seksi
        $bidangData = [
            [
                'id' => 1,
                'nama' => 'Dewan Direksi',
                'kode' => 'DIREKSI',
                'urutan' => 1,
                'seksi' => [
                    ['nama' => 'Direktur', 'kode' => 'DIREKTUR', 'urutan' => 1],
                ],
            ],
            [
                'id' => 2,
                'nama' => 'Bidang Hubungan Langganan',
                'kode' => 'HUB_LANGGANAN',
                'urutan' => 2,
                'seksi' => [
                    ['nama' => 'Seksi Pembacaan Meter', 'kode' => 'SEKSI_PEMBACAAN_METER', 'urutan' => 1],
                    ['nama' => 'Seksi Perawatan Meter', 'kode' => 'SEKSI_PERAWATAN_METER', 'urutan' => 2],
                ],
            ],
            [
                'id' => 3,
                'nama' => 'Bidang Teknik',
                'kode' => 'TEKNIK',
                'urutan' => 3,
                'seksi' => [
                    ['nama' => 'Seksi Perencanaan', 'kode' => 'SEKSI_PERENCANAAN', 'urutan' => 1],
                    ['nama' => 'Seksi Distribusi', 'kode' => 'SEKSI_DISTRIBUSI', 'urutan' => 2],
                    ['nama' => 'Seksi Survei & Verifikasi Teknis', 'kode' => 'SEKSI_SURVEI_VERIFIKASI_TEKNIS', 'urutan' => 3],
                ],
            ],
            [
                'id' => 4,
                'nama' => 'Satuan Pengendalian Kehilangan Air',
                'kode' => 'SPKA',
                'urutan' => 4,
                'seksi' => [
                    ['nama' => 'Bidang Kehilangan Air', 'kode' => 'BIDANG_KEHILANGAN_AIR', 'urutan' => 1],
                    ['nama' => 'Bidang Analisa Jaringan & GIS', 'kode' => 'BIDANG_ANALISA_JARINGAN_GIS', 'urutan' => 2],
                ],
            ],
            [
                'id' => 5,
                'nama' => 'PUSIMPEL IT & System',
                'kode' => 'PUSIMPEL_IT',
                'urutan' => 5,
                'seksi' => [
                    ['nama' => 'Superadmin Pusimpel', 'kode' => 'SUPERADMIN_PUSIMPEL', 'urutan' => 1],
                    ['nama' => 'Staff IT & Jaringan', 'kode' => 'STAFF_IT_JARINGAN', 'urutan' => 2],
                ],
            ],
        ];

        foreach ($bidangData as $b) {
            $bidang = Bidang::updateOrCreate(
                ['id' => $b['id']],
                ['nama' => $b['nama'], 'kode' => $b['kode'], 'urutan' => $b['urutan']]
            );

            // Simpan juga ke tabel alias 'bidang'
            DB::table('bidang')->updateOrInsert(
                ['id' => $b['id']],
                ['nama' => $b['nama'], 'kode' => $b['kode'], 'urutan' => $b['urutan']]
            );

            foreach ($b['seksi'] as $s) {
                SeksiBidang::updateOrCreate(
                    ['bidang_id' => $bidang->id, 'nama' => $s['nama']],
                    ['kode' => $s['kode'], 'urutan' => $s['urutan']]
                );

                DB::table('seksi_bidang')->updateOrInsert(
                    ['bidang_id' => $bidang->id, 'nama' => $s['nama']],
                    ['kode' => $s['kode'], 'urutan' => $s['urutan']]
                );
            }
        }

        // 4. Seed Settings
        $jsonPath = database_path('data/settings.json');
        if (File::exists($jsonPath)) {
            $settings = json_decode(File::get($jsonPath), true) ?: [];
            foreach ($settings as $key => $val) {
                SystemSetting::updateOrCreate(['key' => $key], ['value' => $val]);
            }
        }

        // 5. Seed Initial Presensi & Approval Demo Data
        \App\Models\Presensi::updateOrCreate(
            ['nik' => 'K202609', 'tanggal' => Carbon::now('Asia/Jakarta')->toDateString(), 'slot_type' => 'masuk'],
            [
                'pgw_id'          => '3',
                'pgw_nama'        => 'RAJA HAFIZA RAMANDA PUTRA, S.T',
                'waktu'           => Carbon::now('Asia/Jakarta')->setTime(7, 45, 0)->format('Y-m-d H:i:s'),
                'lokasi'          => 'Kantor PERUMDA Tirta Kepri Tanjung Pinang',
                'latitude'        => 0.92031,
                'longitude'       => 104.46069,
                'alamat'          => 'Tj. Pinang Timur, Kota Tanjung Pinang, Kepulauan Riau',
                'jarak_hq'        => '15 m',
                'foto_url'        => '/images/sample_selfie.jpg',
                'biometric_score' => 99.4,
                'catatan'         => 'Presensi kehadiran operasional lapangan PERUMDA Tirta Kepri',
                'status'          => 'Tepat Waktu',
                'approval_status' => 'approved',
                'reviewed_by'     => 'IR. H. ABDUL KHOLIK F, M.M.',
                'reviewed_at'     => Carbon::now('Asia/Jakarta')->setTime(8, 27, 0),
                'tipe_pengajuan'  => 'dalamRadius',
            ]
        );

        \App\Models\Presensi::updateOrCreate(
            ['nik' => 'EMP001', 'tanggal' => Carbon::now('Asia/Jakarta')->toDateString(), 'slot_type' => 'pulang'],
            [
                'pgw_id'          => '1',
                'pgw_nama'        => 'Budi Santoso',
                'waktu'           => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'lokasi'          => 'Jl. D.I. Panjaitan Km 9, Tanjungpinang',
                'latitude'        => 0.92450,
                'longitude'       => 104.46520,
                'alamat'          => 'Perbaikan Pipa Distribusi Utama Wilayah Batu 9',
                'jarak_hq'        => '750 m',
                'foto_url'        => '/images/sample_selfie.jpg',
                'biometric_score' => 98.8,
                'catatan'         => '[TERLAMBAT (15 Menit): Perbaikan pipa bocor darurat di jalur transmisi]',
                'status'          => 'Terlambat (15 Menit)',
                'approval_status' => 'pending',
                'tipe_pengajuan'  => 'luarRadius',
            ]
        );

        \App\Models\Presensi::updateOrCreate(
            ['nik' => 'K202609', 'tanggal' => Carbon::now('Asia/Jakarta')->toDateString(), 'slot_type' => 'istirahatMasuk'],
            [
                'pgw_id'          => '3',
                'pgw_nama'        => 'RAJA HAFIZA RAMANDA PUTRA, S.T',
                'waktu'           => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'lokasi'          => 'Jl. MT Haryono Km 3, Tanjungpinang',
                'latitude'        => 0.92031,
                'longitude'       => 104.46069,
                'alamat'          => 'Kantor Cabang Wilayah Pelayanan Lapangan',
                'jarak_hq'        => '450 m',
                'foto_url'        => '/images/sample_selfie.jpg',
                'biometric_score' => 99.4,
                'catatan'         => 'Presensi istirahat masuk penugasan lapangan wilayah pelayanan.',
                'status'          => 'Tepat Waktu',
                'approval_status' => 'pending',
                'tipe_pengajuan'  => 'luarRadius',
            ]
        );

        \App\Models\Presensi::updateOrCreate(
            ['nik' => 'EMP001', 'tanggal' => Carbon::now('Asia/Jakarta')->toDateString(), 'slot_type' => 'istirahatKeluar'],
            [
                'pgw_id'          => '1',
                'pgw_nama'        => 'Budi Santoso',
                'waktu'           => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'lokasi'          => 'IPA Sungai Jang Tanjungpinang',
                'latitude'        => 0.92031,
                'longitude'       => 104.46069,
                'alamat'          => 'Instalasi Pengolahan Air Sungai Jang',
                'jarak_hq'        => '1.2 km',
                'foto_url'        => '/images/sample_selfie.jpg',
                'biometric_score' => 99.0,
                'catatan'         => 'Presensi istirahat keluar di lokasi instalasi pengolahan air.',
                'status'          => 'Tepat Waktu',
                'approval_status' => 'pending',
                'tipe_pengajuan'  => 'luarRadius',
            ]
        );
    }
}
