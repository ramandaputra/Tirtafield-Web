<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Legacy Router untuk endpoint /api/settings.php
     */
    public function legacyRouter(Request $request): JsonResponse
    {
        $action = $request->input('action') ?? $request->query('action') ?? 'get';

        return match ($action) {
            'get'              => $this->get(),
            'save_operational' => $this->saveOperational($request),
            'save_geofencing'  => $this->saveGeofencing($request),
            default            => response()->json([
                'success' => false,
                'message' => 'Action tidak valid: ' . $action,
                'data'    => null,
            ], 400),
        };
    }

    /**
     * Dapatkan konfigurasi sistem
     */
    public function get(): JsonResponse
    {
        $settings = SystemSetting::getAllSettings();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil dimuat.',
            'data'    => $settings,
        ], 200);
    }

    /**
     * Simpan jam operasional 4 slot presensi
     */
    public function saveOperational(Request $request): JsonResponse
    {
        $input = $request->all();
        $payload = $input['operational_hours'] ?? $input;

        $currentSettings = SystemSetting::getAllSettings();
        $currentOp = $currentSettings['operational_hours'] ?? [];

        // Jika input berupa single day schedule
        if (isset($payload['day_of_week']) && !isset($payload['daily_schedules'])) {
            $schedules = $currentOp['daily_schedules'] ?? [];
            $found = false;
            foreach ($schedules as &$d) {
                if (($d['day_of_week'] ?? 0) == $payload['day_of_week']) {
                    $d = array_merge($d, $payload);
                    $found = true;
                    break;
                }
            }
            unset($d);
            if (!$found) {
                $schedules[] = $payload;
            }
            $currentOp['daily_schedules'] = $schedules;
            $merged = $currentOp;
        } else {
            $merged = array_merge($currentOp, $payload);
        }

        SystemSetting::updateSetting('operational_hours', $merged);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal operasional presensi berhasil disimpan.',
            'data'    => $merged,
        ], 200);
    }

    /**
     * Simpan parameter geofencing kantor
     */
    public function saveGeofencing(Request $request): JsonResponse
    {
        $input = $request->all();
        $payload = $input['geofencing'] ?? $input;

        $currentSettings = SystemSetting::getAllSettings();
        $currentGeo = $currentSettings['geofencing'] ?? [];
        $merged = array_merge($currentGeo, $payload);

        SystemSetting::updateSetting('geofencing', $merged);

        // Sinkronkan juga daftar kantor ke tabel offices jika disertakan
        if (isset($merged['offices']) && is_array($merged['offices'])) {
            $activeIds = [];
            foreach ($merged['offices'] as $o) {
                if (!empty($o['id'])) {
                    $activeIds[] = $o['id'];
                    $isPrimary = (bool)($o['is_primary'] ?? false);
                    Office::updateOrCreate(
                        ['id' => $o['id']],
                        [
                            'nama_kantor' => $o['name'] ?? $o['nama_kantor'] ?? $o['id'],
                            'alamat'      => $o['address'] ?? $o['alamat'] ?? '',
                            'latitude'    => (float)($o['latitude'] ?? 0),
                            'longitude'   => (float)($o['longitude'] ?? 0),
                            'radius'      => (int)($o['radius'] ?? 100),
                            'is_primary'  => $isPrimary,
                            'is_active'   => true,
                        ]
                    );
                }
            }
            if (!empty($activeIds)) {
                Office::whereNotIn('id', $activeIds)->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan acuan lokasi kantor berhasil disimpan.',
            'data'    => $merged,
        ], 200);
    }

    /**
     * Resolve Google Maps URL ke Latitude & Longitude
     */
    public function resolveMaps(Request $request): JsonResponse
    {
        $url = trim($request->input('url') ?? $request->query('url') ?? '');

        if (empty($url)) {
            return response()->json([
                'success' => false,
                'message' => 'URL tidak boleh kosong',
                'data'    => null,
            ], 400);
        }

        // Cek jika format koordinat langsung "lat, lng"
        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/', $url, $m)) {
            return response()->json([
                'success' => true,
                'message' => 'Koordinat berhasil dibaca.',
                'data'    => [
                    'latitude'     => (float)$m[1],
                    'longitude'    => (float)$m[2],
                    'name'         => null,
                    'resolved_url' => $url,
                ],
            ], 200);
        }

        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $lat = null;
        $lng = null;
        $name = null;

        // Fetch URL untuk resolusi redirect
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
        curl_close($ch);

        // Ekstraksi regex koordinat
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $matches)) {
            $lat = (float)$matches[1];
            $lng = (float)$matches[2];
        } elseif (preg_match('/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $matches)) {
            $lat = (float)$matches[1];
            $lng = (float)$matches[2];
        } elseif (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $finalUrl, $matches)) {
            $lat = (float)$matches[1];
            $lng = (float)$matches[2];
        }

        // Google Geocoding API Fallback jika API key tersedia
        if ($lat === null && ($apiKey = config('services.google.maps_api_key'))) {
            try {
                $geoUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($url) . '&key=' . $apiKey;
                $geoRes = @file_get_contents($geoUrl);
                if ($geoRes) {
                    $geoData = json_decode($geoRes, true);
                    if (!empty($geoData['results'][0]['geometry']['location'])) {
                        $lat = (float)$geoData['results'][0]['geometry']['location']['lat'];
                        $lng = (float)$geoData['results'][0]['geometry']['location']['lng'];
                        $name = $geoData['results'][0]['formatted_address'] ?? null;
                    }
                }
            } catch (\Throwable $e) {}
        }

        if ($lat !== null && $lng !== null) {
            return response()->json([
                'success' => true,
                'message' => 'Koordinat berhasil diekstraksi dari URL Maps.',
                'data'    => [
                    'latitude'     => $lat,
                    'longitude'    => $lng,
                    'name'         => $name,
                    'resolved_url' => $finalUrl,
                ],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengekstrak koordinat dari URL peta.',
            'data'    => ['resolved_url' => $finalUrl],
        ], 422);
    }
}

