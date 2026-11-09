<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Legacy Router untuk endpoint /api/employees.php
     */
    public function legacyRouter(Request $request): JsonResponse
    {
        $action = $request->input('action') ?? $request->query('action') ?? 'list';

        return match ($action) {
            'list'        => $this->list($request),
            'add'         => $this->store($request),
            'update'      => $this->update($request),
            'delete'      => $this->destroy($request),
            'offices'     => $this->offices($request),
            'bidang_list' => $this->bidangList($request),
            default       => response()->json([
                'success' => false,
                'message' => 'Action tidak valid: ' . $action,
                'data'    => null,
            ], 400),
        };
    }

    /**
     * Daftar Pegawai (dengan filter pencarian, divisi, dan role)
     */
    public function list(Request $request): JsonResponse
    {
        $q = trim($request->input('q') ?? $request->query('q') ?? '');
        $roleFilter = trim($request->input('role') ?? $request->query('role') ?? '');
        $deptFilter = trim($request->input('department') ?? $request->query('department') ?? '');

        $query = User::with('office')->where('is_active', true);

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%")
                    ->orWhere('department', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");
            });
        }

        if (!empty($deptFilter)) {
            $query->where('department', $deptFilter);
        }

        if (!empty($roleFilter)) {
            $query->where('role', $roleFilter);
        }

        $employees = $query->orderBy('id', 'asc')->get()->map(function ($u) {
            return $u->toApiResponse();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar data pegawai berhasil dimuat.',
            'data'    => $employees,
        ], 200);
    }

    /**
     * Tambah Pegawai Baru
     */
    public function store(Request $request): JsonResponse
    {
        $actorRole = trim($request->input('actor_role') ?? '');
        $isSuperadmin = auth()->check() ? auth()->user()->isStaffIT() : (!empty($request->input('is_superadmin')) || $actorRole === 'staffIT');

        if (!$isSuperadmin) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Superadmin yang dapat menambah data pegawai.',
                'data'    => null,
            ], 403);
        }

        $nip        = trim($request->input('nip') ?? $request->input('nik') ?? '');
        $name       = trim($request->input('name') ?? '');
        $department = trim($request->input('department') ?? 'Bidang Hubungan Langganan');
        $position   = trim($request->input('position') ?? 'Seksi Pembacaan Meter');
        $phone      = trim($request->input('phone') ?? '081234567890');
        $pin        = trim($request->input('pin') ?? '123456');
        $role       = trim($request->input('role') ?? 'karyawan');
        $officeId   = trim($request->input('office_id') ?? $request->input('officeId') ?? 'hq');
        $officeName = trim($request->input('office_name') ?? $request->input('officeName') ?? '');

        if (empty($nip) || empty($name)) {
            return response()->json([
                'success' => false,
                'message' => 'NIP/NIK dan Nama Pegawai wajib diisi.',
                'data'    => null,
            ], 400);
        }

        // Cek duplikasi NIP
        if (User::where('nik', $nip)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Pegawai dengan NIP '$nip' sudah terdaftar.",
                'data'    => null,
            ], 400);
        }

        // Resolve nama kantor
        if (empty($officeName)) {
            $office = Office::find($officeId);
            $officeName = $office?->nama_kantor ?? 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang';
        }

        $email = strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z0-9 ]/', '', $name))) . '@tirtafield.co.id';

        $user = User::create([
            'nik'            => $nip,
            'name'           => $name,
            'email'          => $email,
            'department'     => $department,
            'position'       => $position,
            'phone'          => $phone,
            'pin'            => $pin,
            'password'       => $pin,
            'role'           => $role,
            'office_id'      => $officeId,
            'office_name'    => $officeName,
            'status_pegawai' => 'Karyawan Tetap',
            'is_active'      => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai baru berhasil ditambahkan.',
            'data'    => [
                'id'          => (string)$user->id,
                'nip'         => $nip,
                'name'        => $name,
                'email'       => $email,
                'department'  => $department,
                'position'    => $position,
                'role'        => $role,
                'pin'         => $pin,
                'office_id'   => $officeId,
                'office_name' => $officeName,
            ],
        ], 200);
    }

    /**
     * Update Data Pegawai
     */
    public function update(Request $request, ?string $id = null): JsonResponse
    {
        $actorRole = trim($request->input('actor_role') ?? '');
        $isSuperadmin = auth()->check() ? auth()->user()->isStaffIT() : (!empty($request->input('is_superadmin')) || $actorRole === 'staffIT');

        if (!$isSuperadmin) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Superadmin yang dapat merubah data pegawai.',
                'data'    => null,
            ], 403);
        }

        $id = $id ?? trim($request->input('id') ?? '');
        $nip = trim($request->input('nip') ?? $request->input('nik') ?? '');

        if (empty($id) && empty($nip)) {
            return response()->json([
                'success' => false,
                'message' => 'ID Pegawai diperlukan.',
                'data'    => null,
            ], 400);
        }

        $user = User::where('id', $id)->orWhere('nik', $nip ?: $id)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $newNip = trim($request->input('nip') ?? $request->input('nik') ?? '');
        if (!empty($newNip) && $newNip !== (string)$user->nik) {
            if (User::where('nik', $newNip)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => "Pegawai dengan NIP/NIK '$newNip' sudah terdaftar.",
                    'data'    => null,
                ], 400);
            }
            $user->nik = $newNip;
        }

        if ($request->filled('name')) {
            $user->name = trim($request->input('name'));
        }
        if ($request->filled('department')) {
            $user->department = trim($request->input('department'));
        }
        if ($request->filled('position') || $request->filled('seksi_bidang')) {
            $user->position = trim($request->input('position') ?? $request->input('seksi_bidang'));
        }
        if ($request->filled('phone')) {
            $user->phone = trim($request->input('phone'));
        }
        if ($request->filled('pin')) {
            $newPin = trim($request->input('pin'));
            $user->pin = $newPin;
            $user->password = $newPin;
        }
        if ($request->filled('role')) {
            $user->role = trim($request->input('role'));
        }
        if ($request->filled('office_id') || $request->filled('officeId')) {
            $user->office_id = trim($request->input('office_id') ?? $request->input('officeId'));
            $office = Office::find($user->office_id);
            if ($office) {
                $user->office_name = $office->nama_kantor;
            }
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diperbarui.',
            'data'    => ['id' => (string)$user->id],
        ], 200);
    }

    /**
     * Hapus / Nonaktifkan Pegawai
     */
    public function destroy(Request $request, ?string $id = null): JsonResponse
    {
        $actorRole = trim($request->input('actor_role') ?? '');
        $isSuperadmin = auth()->check() ? auth()->user()->isStaffIT() : (!empty($request->input('is_superadmin')) || $actorRole === 'staffIT');

        if (!$isSuperadmin) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Superadmin yang dapat menonaktifkan data pegawai.',
                'data'    => null,
            ], 403);
        }

        $id = $id ?? trim($request->input('id') ?? $request->query('id') ?? '');

        if (empty($id)) {
            return response()->json([
                'success' => false,
                'message' => 'ID Pegawai diperlukan.',
                'data'    => null,
            ], 400);
        }

        $user = User::find($id);
        if ($user) {
            $user->is_active = false;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil dinonaktifkan.',
            'data'    => ['id' => (string)$id],
        ], 200);
    }

    /**
     * Daftar Kantor untuk dropdown
     */
    public function offices(): JsonResponse
    {
        $offices = Office::where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('nama_kantor', 'asc')
            ->get()
            ->map(fn($o) => $o->toApiResponse());

        return response()->json([
            'success' => true,
            'message' => 'Daftar lokasi kantor penempatan.',
            'data'    => $offices,
        ], 200);
    }

    /**
     * Daftar Bidang & Seksi Bidang untuk dropdown form
     */
    public function bidangList(): JsonResponse
    {
        $bidangList = Bidang::with('seksi')->orderBy('urutan', 'asc')->get();

        $result = $bidangList->map(function ($b) {
            return [
                'id'    => (int)$b->id,
                'nama'  => $b->nama,
                'kode'  => $b->kode,
                'seksi' => $b->seksi->pluck('nama')->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar bidang dan seksi bidang.',
            'data'    => $result,
        ], 200);
    }
}

