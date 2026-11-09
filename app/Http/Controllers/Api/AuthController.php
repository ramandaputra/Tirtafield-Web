<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AuthController extends Controller
{
    /**
     * Login Pengguna (Mendukung JSON POST, Form-Data, maupun legacy query params)
     */
    public function login(Request $request): JsonResponse
    {
        $nik = trim($request->input('nik') ?? $request->input('nip') ?? '');
        $pin = trim($request->input('pin') ?? $request->input('password') ?? '');

        if (empty($nik)) {
            return response()->json([
                'success' => false,
                'message' => 'NIP / NIK tidak boleh kosong.',
                'data'    => null,
            ], 400);
        }

        // Cari user di tabel users
        $user = User::with('office')
            ->where(function ($q) use ($nik) {
                $q->where('nik', $nik)
                  ->orWhere('email', $nik)
                  ->orWhere('id', $nik);
            })
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Pengguna dengan NIP/NIK '$nik' tidak ditemukan di database.",
                'data'    => null,
            ], 404);
        }

        // Verifikasi PIN / Password jika diisi
        if (!empty($pin)) {
            if (!$user->verifySecret($pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN yang Anda masukkan salah. (Default PIN: 123456)',
                    'data'    => null,
                ], 401);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => $user->toApiResponse(),
        ], 200);
    }

    /**
     * Update Profil (Mendukung foto, PIN, telepon, serta perubahan bidang oleh Superadmin)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $userId = trim($request->input('id') ?? '');
        $nik = trim($request->input('nik') ?? $request->input('nip') ?? '');
        $actorRole = trim($request->input('actor_role') ?? '');

        // Validasi hak akses Superadmin
        if (auth()->check()) {
            $isSuperadmin = auth()->user()->isStaffIT();
            if (empty($userId) && empty($nik)) {
                $userId = (string)auth()->id();
            }
        } else {
            $isSuperadmin = !empty($request->input('is_superadmin')) || $actorRole === 'staffIT';
        }

        if (empty($userId) && empty($nik)) {
            return response()->json([
                'success' => false,
                'message' => 'ID Karyawan atau NIK diperlukan.',
                'data'    => null,
            ], 400);
        }

        $user = null;
        if (!empty($userId)) {
            $user = User::find($userId);
        }
        if (!$user && !empty($nik)) {
            $user = User::where('nik', $nik)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan di database.',
                'data'    => null,
            ], 404);
        }

        // 1. Foto Profil (Mendukung file upload, base64 data URI kamera/galeri, maupun URL)
        if ($request->hasFile('avatar') || $request->hasFile('photo') || $request->hasFile('image')) {
            $file = $request->file('avatar') ?? $request->file('photo') ?? $request->file('image');
            if ($file && $file->isValid()) {
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $fileName = 'avatar_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $ext;
                $uploadDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'avatars');
                if (!File::isDirectory($uploadDir)) {
                    File::makeDirectory($uploadDir, 0777, true, true);
                }
                File::put($uploadDir . DIRECTORY_SEPARATOR . $fileName, file_get_contents($file->getRealPath()));
                $user->avatar_url = '/uploads/avatars/' . $fileName;
            }
        } elseif ($request->filled('avatar_base64') || $request->filled('avatar_url') || $request->filled('photo')) {
            $rawAvatar = $request->input('avatar_base64') ?? $request->input('avatar_url') ?? $request->input('photo');
            if (is_string($rawAvatar) && (str_starts_with($rawAvatar, 'data:image/') || str_contains($rawAvatar, ';base64,'))) {
                $commaPos = strpos($rawAvatar, ',');
                $base64Data = ($commaPos !== false) ? substr($rawAvatar, $commaPos + 1) : $rawAvatar;
                $decoded = base64_decode($base64Data);

                if ($decoded !== false && strlen($decoded) > 0) {
                    $ext = str_contains($rawAvatar, 'image/png') ? 'png' : 'jpg';
                    $fileName = 'avatar_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $uploadDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'avatars');
                    if (!File::isDirectory($uploadDir)) {
                        File::makeDirectory($uploadDir, 0777, true, true);
                    }
                    File::put($uploadDir . DIRECTORY_SEPARATOR . $fileName, $decoded);
                    $user->avatar_url = '/uploads/avatars/' . $fileName;
                }
            } elseif (!empty($rawAvatar)) {
                $user->avatar_url = trim($rawAvatar);
            }
        }

        // 2. PIN
        if ($request->filled('pin')) {
            if ($request->filled('old_pin')) {
                $currentPin = $user->pin ?: '123456';
                if (trim($request->input('old_pin')) !== (string)$currentPin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'PIN lama tidak sesuai. Silakan periksa kembali.',
                        'data'    => null,
                    ], 400);
                }
            }
            $newPin = trim($request->input('pin'));
            $user->pin = $newPin;
            $user->password = $newPin;
        }

        // 3. Telepon
        if ($request->has('phone')) {
            $user->phone = trim($request->input('phone'));
        }

        // 4. Nama Lengkap, NIK, Bidang & Jabatan (Hanya Superadmin yang dapat mengubah)
        if ($isSuperadmin) {
            if ($request->filled('name')) {
                $user->name = trim($request->input('name'));
            }

            // Update NIK jika ada new_nik atau nik yang berbeda dari saat ini
            if ($request->filled('new_nik')) {
                $newNikVal = trim($request->input('new_nik'));
                if (User::where('nik', $newNikVal)->where('id', '!=', $user->id)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => "NIK '{$newNikVal}' sudah terdaftar pada pengguna lain.",
                        'data'    => null,
                    ], 400);
                }
                $user->nik = $newNikVal;
            } elseif ($request->filled('nik') && trim($request->input('nik')) !== (string)$user->nik && !empty($userId) && $userId != $nik) {
                $newNikVal = trim($request->input('nik'));
                if (User::where('nik', $newNikVal)->where('id', '!=', $user->id)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => "NIK '{$newNikVal}' sudah terdaftar pada pengguna lain.",
                        'data'    => null,
                    ], 400);
                }
                $user->nik = $newNikVal;
            }

            if ($request->has('department')) {
                $user->department = trim($request->input('department'));
            } elseif ($request->has('bidang')) {
                $user->department = trim($request->input('bidang'));
            }

            if ($request->has('position')) {
                $user->position = trim($request->input('position'));
            }
            if ($request->filled('office_id')) {
                $user->office_id = trim($request->input('office_id'));
                $office = Office::find($user->office_id);
                if ($office) {
                    $user->office_name = $office->nama_kantor;
                }
            } elseif ($request->filled('office_name')) {
                $user->office_name = trim($request->input('office_name'));
            }
        } else {
            $attemptNameChange = $request->filled('name') && trim($request->input('name')) !== (string)$user->name;
            $attemptNikChange  = ($request->filled('new_nik') && trim($request->input('new_nik')) !== (string)$user->nik)
                || ($request->filled('nik') && trim($request->input('nik')) !== (string)$user->nik && !empty($userId) && $userId != $nik);
            $attemptDeptChange = ($request->has('department') && trim($request->input('department')) !== (string)$user->department)
                || ($request->has('bidang') && trim($request->input('bidang')) !== (string)$user->department);

            if ($attemptNameChange || $attemptNikChange || $attemptDeptChange) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya Superadmin yang dapat merubah Nama Lengkap, NIK, dan Bidang.',
                    'data'    => null,
                ], 403);
            }
        }

        $user->save();
        $user->load('office');

        // Sinkronisasi foto profil ke tabel employee jika ada
        if (!empty($user->avatar_url)) {
            try {
                DB::table('employee')
                    ->where('pgw_nup', $user->nik)
                    ->orWhere('userid_machine', $user->id)
                    ->update(['avatar_url' => $user->avatar_url]);
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => [
                'id'          => (string)$user->id,
                'nip'         => (string)$user->nik,
                'name'        => (string)$user->name,
                'department'  => (string)$user->department,
                'position'    => (string)$user->position,
                'office_id'   => (string)$user->office_id,
                'office_name' => $user->office?->nama_kantor ?? $user->office_name,
                'avatar_url'  => (string)($user->avatar_url ?? ''),
            ],
        ], 200);
    }
}

