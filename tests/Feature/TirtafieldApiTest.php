<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TirtafieldApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_auth_login_with_valid_credentials()
    {
        // 1. Modern RESTful route
        $response = $this->postJson('/api/auth/login', [
            'nik' => 'P242101',
            'pin' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nip', 'P242101')
            ->assertJsonPath('data.name', 'Pusimpel')
            ->assertJsonPath('data.role', 'staffIT');

        // 2. Legacy route compatibility (/api/login.php)
        $legacyResponse = $this->postJson('/api/login.php', [
            'nip' => 'D001',
            'password' => '123456',
        ]);

        $legacyResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nip', 'D001')
            ->assertJsonPath('data.role', 'direktur');
    }

    public function test_attendance_today_and_record()
    {
        // 1. Get today slots (empty initially)
        $response = $this->getJson('/api/attendance/today?user_id=EMP001');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 'EMP001');

        // 2. Record attendance for slot masuk
        $recordRes = $this->postJson('/api/attendance/record', [
            'user_id'   => 'EMP001',
            'nik'       => 'EMP001',
            'user_name' => 'Budi Santoso',
            'slot_type' => 'masuk',
            'location'  => 'Kantor Pusat Tanjung Pinang',
            'latitude'  => 0.92031,
            'longitude' => 104.46069,
            'jarak_hq'  => '15 m',
            'notes'     => 'Absen masuk pagi tepat waktu',
        ]);

        $recordRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'masuk')
            ->assertJsonPath('data.status', 'Tepat Waktu');

        // 3. Verify slot is now filled
        $checkRes = $this->getJson('/api/attendance.php?action=today&user_id=EMP001');
        $checkRes->assertStatus(200)
            ->assertJsonPath('data.slots.masuk.type', 'masuk')
            ->assertJsonPath('data.slots.masuk.location', 'Kantor Pusat Tanjung Pinang');
    }

    public function test_approval_submit_and_review()
    {
        // 1. Submit outside radius attendance request via legacy route
        $submitRes = $this->postJson('/api/approval.php?action=submit', [
            'user_id'        => 'EMP001',
            'nik'            => 'EMP001',
            'user_name'      => 'Budi Santoso',
            'slot_type'      => 'pulang',
            'tipe_pengajuan' => 'luarRadius',
            'catatan'        => 'Presensi pulang di luar radius penugasan',
        ]);

        $submitRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $approvalId = $submitRes->json('data.id');

        // 2. Review and approve
        $reviewRes = $this->postJson('/api/approvals/review', [
            'approval_id'   => $approvalId,
            'status'        => 'approved',
            'reviewer_name' => 'IR. H. ABDUL KHOLIK F, M.M.',
            'review_notes'  => 'Disetujui untuk presensi pulang lapangan.',
        ]);

        $reviewRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_employees_and_offices_and_bidang()
    {
        // 1. Employees list
        $empRes = $this->getJson('/api/employees.php?action=list');
        $empRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 2. Offices list
        $officeRes = $this->getJson('/api/offices.php?action=list');
        $officeRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Bidang list
        $bidangRes = $this->getJson('/api/bidang.php');
        $bidangRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_only_superadmin_can_update_account_info_name_nik_and_bidang()
    {
        $karyawan = User::where('nik', 'EMP001')->first();
        $this->assertNotNull($karyawan);

        // 1. Non-superadmin tries to change name -> 403 Forbidden
        $resName = $this->postJson('/api/update-profile', [
            'id'   => (string)$karyawan->id,
            'nik'  => $karyawan->nik,
            'name' => 'Nama Karyawan Baru',
        ]);
        $resName->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Hanya Superadmin yang dapat merubah Nama Lengkap, NIK, dan Bidang.');

        // 2. Non-superadmin tries to change NIK -> 403 Forbidden
        $resNik = $this->postJson('/api/update-profile', [
            'id'      => (string)$karyawan->id,
            'new_nik' => 'NEWNIK999',
        ]);
        $resNik->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Hanya Superadmin yang dapat merubah Nama Lengkap, NIK, dan Bidang.');

        // 3. Non-superadmin tries to change Bidang -> 403 Forbidden
        $resDept = $this->postJson('/api/update-profile', [
            'id'         => (string)$karyawan->id,
            'department' => 'Bidang Keuangan & Akuntansi',
        ]);
        $resDept->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Hanya Superadmin yang dapat merubah Nama Lengkap, NIK, dan Bidang.');

        // 4. Non-superadmin CAN update allowed fields like phone or pin
        $resPhone = $this->postJson('/api/update-profile', [
            'id'    => (string)$karyawan->id,
            'phone' => '089999999999',
            'pin'   => '654321',
        ]);
        $resPhone->assertStatus(200)
            ->assertJsonPath('success', true);

        $karyawan->refresh();
        $this->assertEquals('089999999999', $karyawan->phone);
        $this->assertEquals('654321', $karyawan->pin);
        $this->assertEquals('Budi Santoso', $karyawan->name); // Name untouched
    }

    public function test_superadmin_can_successfully_update_name_nik_and_bidang()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $this->assertNotNull($superadmin);

        // Superadmin updates their own profile
        $res = $this->actingAs($superadmin)->postJson('/api/update-profile', [
            'id'            => (string)$superadmin->id,
            'name'          => 'Superadmin IT Utama',
            'new_nik'       => 'P242199',
            'department'    => 'Bidang Teknologi & Sistem Informasi',
            'is_superadmin' => true,
            'actor_role'    => 'staffIT',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $superadmin->refresh();
        $this->assertEquals('Superadmin IT Utama', $superadmin->name);
        $this->assertEquals('P242199', $superadmin->nik);
        $this->assertEquals('Bidang Teknologi & Sistem Informasi', $superadmin->department);
    }

    public function test_admin_direktur_profile_pin_and_avatar_update()
    {
        $direktur = User::where('nik', 'D001')->first();
        $this->assertNotNull($direktur);

        // 1. Update Phone / Profile (Simpan Pengaturan Akun Admin)
        $resProfile = $this->actingAs($direktur)->postJson('/api/update-profile', [
            'id'    => (string)$direktur->id,
            'phone' => '081298765432',
        ]);
        $resProfile->assertStatus(200)
            ->assertJsonPath('success', true);
        $direktur->refresh();
        $this->assertEquals('081298765432', $direktur->phone);

        // 2. Change PIN with wrong old_pin fails
        $resPinFail = $this->actingAs($direktur)->postJson('/api/update-profile', [
            'id'      => (string)$direktur->id,
            'old_pin' => '999999',
            'pin'     => '654321',
        ]);
        $resPinFail->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'PIN lama tidak sesuai. Silakan periksa kembali.');

        // 3. Change PIN with correct old_pin succeeds
        $resPinSuccess = $this->actingAs($direktur)->postJson('/api/update-profile', [
            'id'      => (string)$direktur->id,
            'old_pin' => '123456',
            'pin'     => '654321',
        ]);
        $resPinSuccess->assertStatus(200)
            ->assertJsonPath('success', true);
        $direktur->refresh();
        $this->assertEquals('654321', $direktur->pin);

        // 4. Update avatar photo
        $testAvatar = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d';
        $resAvatar = $this->actingAs($direktur)->postJson('/api/update-profile', [
            'id'         => (string)$direktur->id,
            'avatar_url' => $testAvatar,
        ]);
        $resAvatar->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.avatar_url', $testAvatar);
        $direktur->refresh();
        $this->assertEquals($testAvatar, $direktur->avatar_url);
    }

    public function test_superadmin_operational_and_offices_api()
    {
        $superadmin = User::where('nik', 'P242101')->first();
        $this->assertNotNull($superadmin);

        // 1. Save day operational schedule
        $resOp = $this->actingAs($superadmin)->postJson('/api/settings/operational', [
            'day_of_week'    => 1,
            'day_name'       => 'Senin',
            'is_work_day'    => true,
            'masuk'          => '07:45',
            'istirahat_keluar' => '12:00',
            'istirahat_masuk'  => '13:00',
            'pulang'         => '17:15',
            'toleransi_menit' => 20,
        ]);
        $resOp->assertStatus(200)
            ->assertJsonPath('success', true);

        // 2. Add new office
        $testOfficeId = 'test_office_' . time();
        $resAddOffice = $this->actingAs($superadmin)->postJson('/api/offices', [
            'id'        => $testOfficeId,
            'name'      => 'Unit Layanan Test Antigravity',
            'address'   => 'Jl. Test No. 123',
            'latitude'  => 0.9300,
            'longitude' => 104.4700,
            'radius'    => 150,
            'is_primary' => false,
        ]);
        $resAddOffice->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Set as primary office
        $resPrimary = $this->actingAs($superadmin)->postJson("/api/offices/{$testOfficeId}/primary");
        $resPrimary->assertStatus(200)
            ->assertJsonPath('success', true);

        // 4. Restore original primary (hq)
        $this->actingAs($superadmin)->postJson("/api/offices/hq/primary");

        // 5. Delete test office
        $resDel = $this->actingAs($superadmin)->deleteJson("/api/offices/{$testOfficeId}");
        $resDel->assertStatus(200)
            ->assertJsonPath('success', true);

        // 6. Resolve Maps URL (direct coordinates)
        $resMap = $this->actingAs($superadmin)->getJson('/api/resolve-maps?url=0.92031,%20104.46069');
        $resMap->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.latitude', 0.92031)
            ->assertJsonPath('data.longitude', 104.46069);
    }

    public function test_all_roles_can_update_pin_and_profile_photo()
    {
        // 1. Karyawan PIN update
        $karyawan = User::where('role', 'karyawan')->first();
        $this->assertNotNull($karyawan);
        $karyawan->pin = '123456';
        $karyawan->save();

        $resPinKaryawan = $this->actingAs($karyawan)->postJson('/api/update-profile', [
            'id'      => (string)$karyawan->id,
            'old_pin' => '123456',
            'pin'     => '987654',
        ]);
        $resPinKaryawan->assertStatus(200)
            ->assertJsonPath('success', true);
        $karyawan->refresh();
        $this->assertEquals('987654', $karyawan->pin);

        // 2. Karyawan Profile Photo update (Base64)
        $base64Sample = 'data:image/jpeg;base64,' . base64_encode('fake-jpg-content');
        $resPhotoKaryawan = $this->actingAs($karyawan)->postJson('/api/update-profile', [
            'id'         => (string)$karyawan->id,
            'avatar_url' => $base64Sample,
        ]);
        $resPhotoKaryawan->assertStatus(200)
            ->assertJsonPath('success', true);
        $karyawan->refresh();
        $this->assertStringContainsString('/uploads/avatars/', $karyawan->avatar_url);

        // 3. Superadmin PIN update
        $superadmin = User::where('role', 'staffIT')->first();
        $this->assertNotNull($superadmin);
        $superadmin->pin = '123456';
        $superadmin->save();

        $resPinSuper = $this->actingAs($superadmin)->postJson('/api/update-profile', [
            'id'      => (string)$superadmin->id,
            'old_pin' => '123456',
            'pin'     => '112233',
        ]);
        $resPinSuper->assertStatus(200);
        $superadmin->refresh();
        $this->assertEquals('112233', $superadmin->pin);

        // 4. File upload test using UploadedFile (without requiring GD extension)
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->create('test_avatar.jpg', 10, 'image/jpeg');
        $resFileUpload = $this->actingAs($superadmin)->post('/api/update-profile', [
            'id'     => (string)$superadmin->id,
            'avatar' => $fakeFile,
        ], ['Accept' => 'application/json']);
        $resFileUpload->assertStatus(200)
            ->assertJsonPath('success', true);
        $superadmin->refresh();
        $this->assertStringContainsString('/uploads/avatars/', $superadmin->avatar_url);
    }

    public function test_operational_schedule_and_office_radius_configuration_sync()
    {
        $superadmin = User::where('role', 'staffIT')->first();
        $this->actingAs($superadmin);

        // 1. Simpan jam operasional baru via API Superadmin
        $todayDow = \Carbon\Carbon::now('Asia/Jakarta')->dayOfWeekIso;
        $dayName = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][$todayDow - 1];

        $resOp = $this->postJson('/api/settings/operational', [
            'day_of_week'     => $todayDow,
            'day_name'        => $dayName,
            'is_work_day'     => true,
            'masuk'           => '07:30',
            'istirahat_keluar'=> '11:45',
            'istirahat_masuk' => '12:45',
            'pulang'          => '16:30',
            'toleransi_menit' => 10,
        ]);
        $resOp->assertStatus(200);

        // 2. Evaluasi ketepatan waktu langsung mencerminkan batas jam operasional baru (07:30 + 10m = 07:40)
        $ontime = \App\Models\Presensi::evaluatePunctuality('07:38:00', 'masuk');
        $this->assertFalse($ontime['is_late']);
        $this->assertEquals('Tepat Waktu', $ontime['status']);

        $late = \App\Models\Presensi::evaluatePunctuality('07:48:00', 'masuk');
        $this->assertTrue($late['is_late']);
        $this->assertEquals('Terlambat', $late['status']);

        // 3. Simpan dan perbarui kantor dengan radius khusus (misal: Unit Bintan Centre radius 60m)
        $resOffice = $this->postJson('/api/offices', [
            'id'          => 'bintan_center',
            'name'        => 'Unit Pelayanan Bintan Centre',
            'address'     => 'Komp. Bintan Centre Blok A',
            'latitude'    => 0.9255,
            'longitude'   => 104.4712,
            'radius'      => 60,
            'is_primary'  => false,
        ]);
        $resOffice->assertStatus(200)
            ->assertJsonPath('data.radius', 60);

        $office = \App\Models\Office::find('bintan_center');
        $this->assertNotNull($office);
        $this->assertEquals(60, $office->radius);
        $this->assertEquals(60, $office->radius_meters);
        $this->assertEquals(60, $office->radius_meter);

        // 4. Hubungkan karyawan ke kantor Bintan Centre
        $karyawan = User::where('role', 'karyawan')->first();
        $karyawan->office_id = 'bintan_center';
        $karyawan->office_name = 'Unit Pelayanan Bintan Centre';
        $karyawan->save();

        // 5. Presensi karyawan pada jarak 45 meter (di dalam radius 60m) -> dalamRadius
        $resAttDalam = $this->actingAs($karyawan)->postJson('/api/attendance/record', [
            'user_id'   => (string)$karyawan->id,
            'nik'       => $karyawan->nik,
            'slot_type' => 'masuk',
            'jarak_hq'  => '45 m',
        ]);
        $resAttDalam->assertStatus(200);

        $presensiDalam = \App\Models\Presensi::where('nik', $karyawan->nik)
            ->whereDate('tanggal', \Carbon\Carbon::now('Asia/Jakarta')->toDateString())
            ->where('slot_type', 'masuk')
            ->first();
        $this->assertNotNull($presensiDalam);
        $this->assertEquals('dalamRadius', $presensiDalam->tipe_pengajuan);

        // 6. toDetailArray mencerminkan radius kantor yang dikonfigurasi Superadmin (60m)
        $detail = $presensiDalam->toDetailArray();
        $this->assertEquals(60.0, (float)$detail['targetRad']);
        $this->assertEquals('Unit Pelayanan Bintan Centre', $detail['officePlacement']);

        // 7. Presensi karyawan pada jarak 90 meter (di luar radius 60m) -> luarRadius
        $resAttLuar = $this->actingAs($karyawan)->postJson('/api/attendance/record', [
            'user_id'   => (string)$karyawan->id,
            'nik'       => $karyawan->nik,
            'slot_type' => 'istirahatKeluar',
            'jarak_hq'  => '90 m',
        ]);
        $resAttLuar->assertStatus(200);

        $presensiLuar = \App\Models\Presensi::where('nik', $karyawan->nik)
            ->whereDate('tanggal', \Carbon\Carbon::now('Asia/Jakarta')->toDateString())
            ->where('slot_type', 'istirahatKeluar')
            ->first();
        $this->assertNotNull($presensiLuar);
        $this->assertEquals('luarRadius', $presensiLuar->tipe_pengajuan);
    }
}

