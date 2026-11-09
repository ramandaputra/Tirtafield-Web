<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AttendanceController extends Controller
{
    /**
     * Legacy Router untuk endpoint /api/attendance.php
     */
    public function legacyRouter(Request $request): JsonResponse
    {
        $action = $request->input('action') ?? $request->query('action') ?? 'today';

        return match ($action) {
            'today'   => $this->today($request),
            'record'  => $this->record($request),
            'history' => $this->history($request),
            'recap'   => $this->recap($request),
            default   => response()->json([
                'success' => false,
                'message' => 'Action tidak valid: ' . $action,
                'data'    => null,
            ], 400),
        };
    }

    /**
     * Dapatkan 4 slot presensi hari ini untuk user
     */
    public function today(Request $request): JsonResponse
    {
        $userId = trim($request->input('user_id') ?? $request->input('nik') ?? '');
        $userName = trim($request->input('user_name') ?? '');
        $todayDate = Carbon::now('Asia/Jakarta')->toDateString();

        if (empty($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'user_id diperlukan',
                'data'    => null,
            ], 400);
        }

        $records = Presensi::where(function ($q) use ($userId) {
                $q->where('pgw_id', $userId)
                  ->orWhere('nik', $userId);
            })
            ->whereDate('tanggal', $todayDate)
            ->orderBy('waktu', 'asc')
            ->get();

        $slots = [
            'masuk'           => null,
            'istirahatKeluar' => null,
            'istirahatMasuk'  => null,
            'pulang'          => null,
        ];

        foreach ($records as $r) {
            $slotType = $r->slot_type;
            if (array_key_exists($slotType, $slots)) {
                $slots[$slotType] = $r->toSlotArray();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data presensi hari ini berhasil dimuat.',
            'data'    => [
                'date'      => $todayDate,
                'user_id'   => $userId,
                'user_name' => $userName,
                'slots'     => $slots,
            ],
        ], 200);
    }

    /**
     * Catat Presensi (Slot Masuk, Istirahat, Pulang)
     */
    public function record(Request $request): JsonResponse
    {
        $userId    = $request->input('user_id') ?? $request->input('pgw_id') ?? $request->input('nik') ?? auth()->id() ?? '';
        $userName  = $request->input('user_name') ?? $request->input('pgw_nama') ?? auth()->user()?->name ?? '';
        $userNik   = $request->input('nik') ?? $request->input('user_nip') ?? $request->input('user_nup') ?? auth()->user()?->nik ?? '';
        $slotType  = $request->input('slot_type') ?? 'masuk';
        $location  = $request->input('location') ?? $request->input('lokasi') ?? 'Kantor PERUMDA Tirta Kepri Tanjung Pinang';
        $latitude  = $request->filled('latitude') ? (float)$request->input('latitude') : 0.92031;
        $longitude = $request->filled('longitude') ? (float)$request->input('longitude') : 104.46069;
        $alamat    = $request->input('alamat') ?? 'Tj. Pinang Timur, Kota Tanjung Pinang, Kepulauan Riau';
        $jarakHq   = $request->input('jarak_hq') ?? '10 m';
        $fotoUrl   = $request->input('foto') ?? $request->input('foto_url') ?? $request->input('photo_url') ?? '';
        $notes     = $request->input('notes') ?? $request->input('catatan') ?? '';

        if (empty($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'user_id tidak boleh kosong',
                'data'    => null,
            ], 400);
        }

        // Sinkronkan data pengguna dari database
        $user = User::where('id', $userId)
            ->orWhere('nik', $userNik ?: $userId)
            ->first();

        if ($user) {
            $userId   = (string)$user->id;
            $userName = $userName ?: $user->name;
            $userNik  = $user->nik;
        } else {
            if (empty($userNik)) {
                $userNik = 'EMP-' . $userId;
            }
        }

        // Simpan foto absensi (mendukung Base64 data URL)
        if (!empty($fotoUrl) && (str_starts_with($fotoUrl, 'data:image/') || str_contains($fotoUrl, ';base64,'))) {
            $commaPos = strpos($fotoUrl, ',');
            $base64Data = ($commaPos !== false) ? substr($fotoUrl, $commaPos + 1) : $fotoUrl;
            $decoded = base64_decode($base64Data);

            if ($decoded !== false && strlen($decoded) > 0) {
                $ext = str_contains($fotoUrl, 'image/png') ? 'png' : 'jpg';
                $fileName = 'absensi_' . time() . '_' . uniqid() . '.' . $ext;
                
                $uploadDir = public_path('uploads');
                if (!File::exists($uploadDir)) {
                    File::makeDirectory($uploadDir, 0755, true);
                }
                File::put($uploadDir . '/' . $fileName, $decoded);
                $fotoUrl = '/uploads/' . $fileName;
            }
        }

        $nowTime = Carbon::now('Asia/Jakarta');
        $todayDate = $nowTime->toDateString();
        $formattedTime = $nowTime->format('Y-m-d H:i:s');

        // Hitung status keterlambatan sesuai kriteria jam operasional yang ditentukan Superadmin
        $eval = Presensi::evaluatePunctuality(
            $nowTime,
            $slotType,
            $notes,
            $request->input('status')
        );
        $status = $eval['label'];

        // Ambil data kantor penempatan yang dikonfigurasi Superadmin untuk pengguna ini
        $userOffice = $user?->office;
        if (!$userOffice && !empty($user?->office_id)) {
            $userOffice = Office::find($user->office_id);
        }
        if (!$userOffice) {
            $userOffice = Office::where('is_primary', true)->first() ?: Office::where('is_active', true)->first();
        }

        $allowedRadius = (int)($userOffice?->radius ?? 100);

        // Hitung jarak akurat jika koordinat kantor penempatan tersedia
        if ($userOffice && $userOffice->latitude && $userOffice->longitude && $latitude && $longitude) {
            $officeLat = (float)$userOffice->latitude;
            $officeLng = (float)$userOffice->longitude;
            $R = 6371000;
            $dLat = deg2rad($officeLat - $latitude);
            $dLng = deg2rad($officeLng - $longitude);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($latitude)) * cos(deg2rad($officeLat)) *
                 sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $calculatedMeters = round($R * $c);

            if (empty($request->input('jarak_hq')) || $jarakHq === '10 m') {
                $jarakHq = ($calculatedMeters >= 1000)
                    ? (round($calculatedMeters / 1000, 1) . ' km')
                    : ($calculatedMeters . ' m');
            }
        }

        // Hitung radius sesuai batas radius kantor penempatan yang dikonfigurasi Superadmin
        $isWithinRadius = $request->has('is_within_radius') 
            ? (bool)$request->input('is_within_radius') 
            : null;

        if ($isWithinRadius === null) {
            $cleanDist = strtolower(trim((string)$jarakHq));
            if (str_contains($cleanDist, 'km')) {
                $isWithinRadius = false;
            } elseif (preg_match('/([0-9\.]+)\s*m/', $cleanDist, $m)) {
                $isWithinRadius = ((float)$m[1] <= (float)$allowedRadius);
            } elseif (isset($calculatedMeters)) {
                $isWithinRadius = ($calculatedMeters <= $allowedRadius);
            } else {
                $isWithinRadius = false;
            }
        }

        $tipePengajuan = $request->input('tipe_pengajuan') ?? ($isWithinRadius ? 'dalamRadius' : 'luarRadius');

        $isDiniHari = ($nowTime->hour < 7);
        $yesterdayDate = $nowTime->copy()->subDay()->toDateString();
        $targetDate = $todayDate;
        $isOvernightPulang = false;

        if ($slotType === 'pulang' && $isDiniHari) {
            // Cek apakah ada sesi presensi masuk kemarin yang belum dipulangkan
            $yesterdayMasuk = Presensi::where(function ($q) use ($userId, $userNik) {
                    $q->where('pgw_id', $userId)->orWhere('nik', $userNik);
                })
                ->whereDate('tanggal', $yesterdayDate)
                ->where('slot_type', 'masuk')
                ->first();

            $yesterdayPulang = Presensi::where(function ($q) use ($userId, $userNik) {
                    $q->where('pgw_id', $userId)->orWhere('nik', $userNik);
                })
                ->whereDate('tanggal', $yesterdayDate)
                ->where('slot_type', 'pulang')
                ->first();

            if ($yesterdayMasuk && !$yesterdayPulang) {
                // Tautkan langsung ke tanggal shift kemarin agar data siklus presensi kemarin lengkap
                $targetDate = $yesterdayDate;
                $isOvernightPulang = true;
            } elseif (!$request->has('tanggal')) {
                $isOvernightPulang = true;
            }
        }

        if ($request->filled('tanggal')) {
            $targetDate = $request->input('tanggal');
        }

        if ($isOvernightPulang) {
            if (!str_contains(strtoupper($notes), 'LEMBUR')) {
                $notes = trim('[LEMBUR DINI HARI] ' . $notes);
            }
            if ($tipePengajuan !== 'luarRadius') {
                $tipePengajuan = 'lemburDiniHari';
            }
        }

        // Simpan atau perbarui record presensi untuk slot ini
        $presensi = Presensi::updateOrCreate(
            [
                'nik'       => $userNik,
                'tanggal'   => $targetDate,
                'slot_type' => $slotType,
            ],
            [
                'pgw_id'          => $userId,
                'pgw_nama'        => $userName,
                'waktu'           => $formattedTime,
                'lokasi'          => $location,
                'latitude'        => $latitude,
                'longitude'       => $longitude,
                'alamat'          => $alamat,
                'jarak_hq'        => $jarakHq,
                'foto_url'        => $fotoUrl,
                'biometric_score' => (float)($request->input('biometric_score') ?? 99.4),
                'catatan'         => $notes,
                'status'          => $status,
                'approval_status' => $request->input('approval_status') ?? 'pending',
                'tipe_pengajuan'  => $tipePengajuan,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Presensi slot ' . $slotType . ' berhasil dicatat.',
            'data'    => $presensi->toSlotArray(),
        ], 200);
    }

    /**
     * Riwayat Presensi Pegawai
     */
    public function history(Request $request): JsonResponse
    {
        $userId = trim($request->input('user_id') ?? $request->input('nik') ?? '');
        $month = $request->input('month') ?? Carbon::now('Asia/Jakarta')->format('Y-m');

        $query = Presensi::query();

        if (!empty($userId)) {
            $query->where(function ($q) use ($userId) {
                $q->where('pgw_id', $userId)->orWhere('nik', $userId);
            });
        }

        if (!empty($month)) {
            $query->where('tanggal', 'like', $month . '%');
        }

        $records = $query->orderBy('tanggal', 'desc')->orderBy('waktu', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat presensi berhasil dimuat.',
            'data'    => $records,
        ], 200);
    }

    /**
     * Rekap Statistik Presensi Harian / Bulanan
     */
    public function recap(Request $request): JsonResponse
    {
        $date = $request->input('date') ?? Carbon::now('Asia/Jakarta')->toDateString();

        $totalHadir = Presensi::whereDate('tanggal', $date)->where('slot_type', 'masuk')->count();
        $totalTerlambat = Presensi::whereDate('tanggal', $date)
            ->where('slot_type', 'masuk')
            ->where('status', 'Terlambat')
            ->count();
        $totalLuarRadius = Presensi::whereDate('tanggal', $date)
            ->where(function ($q) {
                $q->where('tipe_pengajuan', 'luarRadius')
                  ->orWhere('tipe_pengajuan', 'Luar Radius');
            })
            ->count();
        $totalPendingApproval = Presensi::where('approval_status', 'pending')->count();

        return response()->json([
            'success' => true,
            'message' => 'Rekap presensi berhasil dimuat.',
            'data'    => [
                'tanggal'          => $date,
                'total_hadir'      => $totalHadir,
                'total_terlambat'  => $totalTerlambat,
                'total_luar_radius'=> $totalLuarRadius,
                'pending_approval' => $totalPendingApproval,
            ],
        ], 200);
    }
}

