<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * Legacy Router untuk endpoint /api/approval.php
     */
    public function legacyRouter(Request $request): JsonResponse
    {
        $action = $request->input('action') ?? $request->query('action') ?? 'list';

        return match ($action) {
            'list'   => $this->list($request),
            'submit' => $this->submit($request),
            'review' => $this->review($request),
            default  => response()->json([
                'success' => false,
                'message' => 'Action tidak valid: ' . $action,
                'data'    => null,
            ], 400),
        };
    }

    /**
     * Daftar Permohonan & Approval Presensi
     */
    public function list(Request $request): JsonResponse
    {
        $userId = trim($request->input('user_id') ?? $request->query('user_id') ?? '');
        $role   = trim($request->input('role') ?? $request->query('role') ?? 'karyawan');

        $query = Presensi::whereNotNull('approval_status')
            ->where('approval_status', '!=', '');

        // Jika role karyawan, batasi hanya permohonan miliknya sendiri
        if ($role === 'karyawan' && !empty($userId)) {
            $query->where(function ($q) use ($userId) {
                $q->where('pgw_id', $userId)
                  ->orWhere('nik', $userId);
            });
        } else {
            // Tampilan admin/superadmin: setelah berganti hari, disetujui/ditolak tidak muncul lagi (hanya hari ini)
            $today = Carbon::now('Asia/Jakarta')->toDateString();
            $query->where(function ($q) use ($today) {
                $q->whereRaw('LOWER(approval_status) = ?', ['pending'])
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereIn('approval_status', ['approved', 'rejected'])
                          ->where(function ($dateQ) use ($today) {
                              $dateQ->whereDate('reviewed_at', $today)
                                    ->orWhere(function ($nullQ) use ($today) {
                                        $nullQ->whereNull('reviewed_at')
                                              ->whereDate('tanggal', $today);
                                    });
                          });
                  });
            });
        }

        $records = $query->orderBy('waktu', 'desc')->orderBy('id', 'desc')->get();

        // Ambil data users dan offices untuk enrich respons
        $userNiks = $records->pluck('nik')->filter()->unique();
        $users = User::with('office')->whereIn('nik', $userNiks)->get()->keyBy('nik');

        $result = [];
        foreach ($records as $p) {
            $u = $users->get($p->nik);

            $formattedTime = date('H:i', strtotime((string)$p->waktu)) . ' WIB';
            $officeName = $u?->office?->nama_kantor ?? $u?->office_name ?? 'Kantor Pusat PERUMDA Tirta Kepri Tanjung Pinang';

            $result[] = [
                'id'                   => (string)$p->id,
                'user_id'              => (string)$p->pgw_id,
                'pgw_id'               => (string)$p->pgw_id,
                'user_nip'             => (string)$p->nik,
                'nik'                  => (string)$p->nik,
                'user_name'            => (string)($p->pgw_nama ?: ($u?->name ?? 'Pegawai')),
                'pgw_nama'             => (string)($p->pgw_nama ?: ($u?->name ?? 'Pegawai')),
                'user_department'      => $u?->department ?? 'Operasional Lapangan',
                'type'                 => (string)$p->slot_type,
                'slot_type'            => (string)$p->slot_type,
                'tipe_pengajuan'       => (string)$p->tipe_pengajuan,
                'title'                => $p->slot_label . ' (' . ($p->tipe_pengajuan === 'luarRadius' ? 'Luar Radius' : 'Lapangan') . ')',
                'reason'               => (string)$p->catatan,
                'catatan'              => (string)$p->catatan,
                'status'               => strtolower((string)$p->approval_status),
                'approval_status'      => (string)$p->approval_status,
                'attendance_status'    => (string)$p->status,
                'tanggal'              => $p->tanggal ? $p->tanggal->format('Y-m-d') : Carbon::now('Asia/Jakarta')->toDateString(),
                'start_date'           => (string)$p->waktu,
                'end_date'             => (string)$p->waktu,
                'submitted_at'         => (string)($p->created_at ? $p->created_at->format('Y-m-d H:i:s') : $p->waktu),
                'slot_type'            => (string)$p->slot_type,
                'waktu'                => (string)$p->waktu,
                'attendance_time'      => $formattedTime,
                'lokasi'               => (string)$p->lokasi,
                'location_name'        => (string)$p->lokasi,
                'latitude'             => $p->latitude ? (float)$p->latitude : 0.92031,
                'longitude'            => $p->longitude ? (float)$p->longitude : 104.46069,
                'alamat'               => (string)$p->alamat,
                'jarak_hq'             => (string)$p->jarak_hq,
                'distance_hq'          => (string)$p->jarak_hq,
                'foto_url'             => (string)$p->foto_url,
                'is_late'              => $p->is_late,
                'reviewed_by'          => $p->reviewed_by,
                'reviewed_at'          => $p->reviewed_at ? $p->reviewed_at->format('Y-m-d H:i:s') : null,
                'review_notes'         => $p->review_notes,
                'resolved_user_name'   => $u?->name ?? $p->pgw_nama,
                'resolved_department'  => $u?->department ?? 'Bidang Lapangan',
                'resolved_position'    => $u?->position ?? 'Petugas Lapangan',
                'resolved_role'        => $u?->role ?? 'karyawan',
                'resolved_office_name' => $officeName,
                'office_placement'     => $officeName,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar approval presensi berhasil dimuat.',
            'data'    => $result,
        ], 200);
    }

    /**
     * Submit Pengajuan Permohonan (Cuti, Izin, Sakit, Lembur, Koreksi Absen, Presensi Luar Radius)
     */
    public function submit(Request $request): JsonResponse
    {
        $userId        = $request->input('user_id') ?? $request->input('pgw_id') ?? auth()->id() ?? '';
        $nik           = $request->input('nik') ?? $request->input('user_nip') ?? $request->input('user_nup') ?? auth()->user()?->nik ?? '';
        $name          = $request->input('user_name') ?? $request->input('pgw_nama') ?? $request->input('name') ?? auth()->user()?->name ?? '';
        $rawSlot       = $request->input('slot_type') ?? $request->input('type') ?? 'masuk';
        $slotType      = match (strtolower(trim((string)$rawSlot))) {
            'istirahatkeluar', 'istirahat_keluar' => 'istirahatKeluar',
            'istirahatmasuk', 'istirahat_masuk'   => 'istirahatMasuk',
            'pulang'                              => 'pulang',
            default                               => 'masuk',
        };
        $tipePengajuan = $request->input('tipe_pengajuan') ?? 'luarRadius';
        
        $startDate     = $request->input('start_date') ?? $request->input('tanggal');
        $tanggal       = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::now('Asia/Jakarta')->toDateString();
        
        $waktu         = $request->input('waktu') ?? Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
        if (strlen($waktu) <= 8) {
            $waktu = $tanggal . ' ' . $waktu;
        }

        $lokasi        = $request->input('lokasi') ?? $request->input('location_name') ?? $request->input('location') ?? 'Pengajuan Permohonan';
        $catatan       = $request->input('catatan') ?? $request->input('reason') ?? $request->input('alasan') ?? '';
        $jarakHq       = $request->input('jarak_hq') ?? $request->input('distance_hq') ?? '0 m';
        $fotoUrl       = $request->input('foto_url') ?? $request->input('attachment_url') ?? $request->input('foto') ?? '';

        // Simpan Base64 photo jika dikirim data URL
        if (!empty($fotoUrl) && (str_starts_with($fotoUrl, 'data:image/') || str_contains($fotoUrl, ';base64,'))) {
            $commaPos = strpos($fotoUrl, ',');
            $base64Data = ($commaPos !== false) ? substr($fotoUrl, $commaPos + 1) : $fotoUrl;
            $decoded = base64_decode($base64Data);

            if ($decoded !== false && strlen($decoded) > 0) {
                $ext = str_contains($fotoUrl, 'image/png') ? 'png' : 'jpg';
                $fileName = 'approval_' . time() . '_' . uniqid() . '.' . $ext;
                $uploadDir = public_path('uploads');
                if (!\Illuminate\Support\Facades\File::exists($uploadDir)) {
                    \Illuminate\Support\Facades\File::makeDirectory($uploadDir, 0755, true);
                }
                \Illuminate\Support\Facades\File::put($uploadDir . '/' . $fileName, $decoded);
                $fotoUrl = '/uploads/' . $fileName;
            }
        }

        $user = User::where('id', $userId)->orWhere('nik', $nik ?: $userId)->first();
        if ($user) {
            $userId = (string)$user->id;
            $nik    = $user->nik;
            $name   = $name ?: $user->name;
        }

        $presensi = Presensi::create([
            'pgw_id'          => $userId ?: '1',
            'nik'             => $nik ?: 'EMP001',
            'pgw_nama'        => $name ?: 'Karyawan Lapangan',
            'tanggal'         => $tanggal,
            'slot_type'       => $slotType,
            'waktu'           => $waktu,
            'lokasi'          => $lokasi,
            'latitude'        => $request->filled('latitude') ? (float)$request->input('latitude') : 0.92031,
            'longitude'       => $request->filled('longitude') ? (float)$request->input('longitude') : 104.46069,
            'alamat'          => $request->input('alamat') ?? $lokasi,
            'jarak_hq'        => $jarakHq,
            'foto_url'        => $fotoUrl,
            'biometric_score' => 99.4,
            'catatan'         => $catatan,
            'status'          => 'Tepat Waktu',
            'approval_status' => 'pending',
            'tipe_pengajuan'  => $tipePengajuan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan presensi berhasil disimpan ke sistem.',
            'data'    => [
                'id'        => (string)$presensi->id,
                'status'    => 'pending',
                'slot_type' => $slotType,
                'waktu'     => $waktu,
            ],
        ], 200);
    }

    /**
     * Review Pengajuan oleh Direktur / Admin (Approve / Reject)
     */
    public function review(Request $request): JsonResponse
    {
        $id       = (int)($request->input('approval_id') ?? $request->input('id') ?? 0);
        $status   = trim($request->input('status') ?? 'approved'); // approved / rejected
        $reviewer = trim($request->input('reviewer_name') ?? 'IR. H. ABDUL KHOLIK F, M.M.');
        $notes    = trim($request->input('notes') ?? $request->input('review_notes') ?? '');

        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID pengajuan presensi tidak valid.',
                'data'    => null,
            ], 400);
        }

        $presensi = Presensi::find($id);
        if (!$presensi) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan presensi tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $presensi->approval_status = $status;
        $presensi->reviewed_by     = $reviewer;
        $presensi->reviewed_at     = Carbon::now('Asia/Jakarta');
        $presensi->review_notes    = $notes;
        $presensi->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan presensi berhasil diperbarui.',
            'data'    => [
                'id'     => (string)$presensi->id,
                'status' => $status,
            ],
        ], 200);
    }
}

