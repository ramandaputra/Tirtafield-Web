<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    /**
     * Legacy Router untuk endpoint /api/offices.php
     */
    public function legacyRouter(Request $request): JsonResponse
    {
        $action = $request->input('action') ?? $request->query('action') ?? 'list';

        return match ($action) {
            'list'        => $this->list(),
            'add'         => $this->store($request),
            'delete'      => $this->destroy($request->input('id') ?? $request->query('id') ?? ''),
            'set_primary' => $this->setPrimary($request->input('id') ?? $request->query('id') ?? ''),
            default       => response()->json([
                'success' => false,
                'message' => 'Action tidak valid: ' . $action,
                'data'    => null,
            ], 400),
        };
    }

    /**
     * Dapatkan daftar kantor aktif
     */
    public function list(): JsonResponse
    {
        $offices = Office::where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('nama_kantor', 'asc')
            ->get()
            ->map(fn($o) => $o->toApiResponse());

        return response()->json([
            'success' => true,
            'message' => 'Daftar lokasi kantor berhasil dimuat.',
            'data'    => $offices,
        ], 200);
    }

    /**
     * Tambah atau perbarui data kantor
     */
    public function store(Request $request): JsonResponse
    {
        $id = trim($request->input('id') ?? 'office_' . time());
        $name = trim($request->input('name') ?? $request->input('nama_kantor') ?? '');
        $address = trim($request->input('address') ?? $request->input('alamat') ?? '');
        $lat = (float)($request->input('latitude') ?? 0);
        $lng = (float)($request->input('longitude') ?? 0);
        $radius = (int)($request->input('radius') ?? 100);
        $isPrimary = (bool)($request->input('is_primary') ?? false);

        if (empty($name)) {
            return response()->json([
                'success' => false,
                'message' => 'Nama kantor wajib diisi.',
                'data'    => null,
            ], 400);
        }

        if ($isPrimary) {
            Office::where('id', '!=', $id)->update(['is_primary' => false]);
        }

        $office = Office::updateOrCreate(
            ['id' => $id],
            [
                'nama_kantor' => $name,
                'alamat'      => $address,
                'latitude'    => $lat,
                'longitude'   => $lng,
                'radius'      => $radius,
                'is_primary'  => $isPrimary,
                'is_active'   => true,
            ]
        );

        // Sync system_settings geofencing offices
        $settings = \App\Models\SystemSetting::getAllSettings();
        if (isset($settings['geofencing']['offices'])) {
            $found = false;
            foreach ($settings['geofencing']['offices'] as &$o) {
                if (($o['id'] ?? '') === $id) {
                    $o['name'] = $name;
                    $o['address'] = $address;
                    $o['latitude'] = $lat;
                    $o['longitude'] = $lng;
                    $o['radius'] = $radius;
                    $o['is_primary'] = $isPrimary;
                    $found = true;
                } elseif ($isPrimary) {
                    $o['is_primary'] = false;
                }
            }
            unset($o);

            if (!$found) {
                if ($isPrimary) {
                    foreach ($settings['geofencing']['offices'] as &$o) {
                        $o['is_primary'] = false;
                    }
                    unset($o);
                }
                $settings['geofencing']['offices'][] = [
                    'id'         => $id,
                    'name'       => $name,
                    'address'    => $address,
                    'latitude'   => $lat,
                    'longitude'  => $lng,
                    'radius'     => $radius,
                    'is_primary' => $isPrimary,
                ];
            }

            \App\Models\SystemSetting::updateSetting('geofencing', $settings['geofencing']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lokasi kantor berhasil disimpan.',
            'data'    => [
                'id'         => $office->id,
                'name'       => $office->nama_kantor,
                'address'    => $office->alamat,
                'latitude'   => $office->latitude,
                'longitude'  => $office->longitude,
                'radius'     => $office->radius,
                'is_primary' => (bool)$office->is_primary,
            ],
        ], 200);
    }

    /**
     * Hapus titik kantor (selain kantor utama)
     */
    public function destroy(string $id): JsonResponse
    {
        $office = Office::find($id);
        if (!$office) {
            return response()->json([
                'success' => false,
                'message' => 'Titik kantor tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        if ($office->is_primary) {
            return response()->json([
                'success' => false,
                'message' => 'Kantor utama tidak dapat dihapus.',
                'data'    => null,
            ], 400);
        }

        $name = $office->nama_kantor;
        $office->delete();

        // Sync system_settings geofencing offices
        $settings = \App\Models\SystemSetting::getAllSettings();
        if (isset($settings['geofencing']['offices'])) {
            $settings['geofencing']['offices'] = array_values(
                array_filter($settings['geofencing']['offices'], fn($o) => ($o['id'] ?? '') !== $id)
            );
            if (($settings['geofencing']['active_office_id'] ?? '') === $id) {
                $settings['geofencing']['active_office_id'] = $settings['geofencing']['offices'][0]['id'] ?? 'hq';
            }
            \App\Models\SystemSetting::updateSetting('geofencing', $settings['geofencing']);
        }

        return response()->json([
            'success' => true,
            'message' => "Titik kantor cabang \"$name\" berhasil dihapus.",
            'data'    => ['id' => $id],
        ], 200);
    }

    /**
     * Jadikan sebagai kantor utama
     */
    public function setPrimary(string $id): JsonResponse
    {
        $office = Office::find($id);
        if (!$office) {
            return response()->json([
                'success' => false,
                'message' => 'Titik kantor tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        Office::where('id', '!=', $id)->update(['is_primary' => false]);
        $office->update(['is_primary' => true]);

        // Sync system_settings geofencing offices
        $settings = \App\Models\SystemSetting::getAllSettings();
        if (isset($settings['geofencing']['offices'])) {
            foreach ($settings['geofencing']['offices'] as &$o) {
                $o['is_primary'] = (($o['id'] ?? '') === $id);
            }
            unset($o);
            $settings['geofencing']['active_office_id'] = $id;
            \App\Models\SystemSetting::updateSetting('geofencing', $settings['geofencing']);
        }

        return response()->json([
            'success' => true,
            'message' => "\"{$office->nama_kantor}\" berhasil ditetapkan sebagai Kantor Utama.",
            'data'    => [
                'id'   => $office->id,
                'name' => $office->nama_kantor,
            ],
        ], 200);
    }
}

