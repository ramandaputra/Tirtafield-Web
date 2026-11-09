<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Dapatkan settings gabungan dari file JSON & database
     */
    public static function getAllSettings(): array
    {
        $filePath = storage_path('app/settings.json');
        $defaultSettings = [];

        if (File::exists($filePath)) {
            $defaultSettings = json_decode(File::get($filePath), true) ?: [];
        }

        try {
            // Cek jika tabel ada & ada override di database
            $dbSettings = self::all()->pluck('value', 'key')->toArray();
            return array_merge($defaultSettings, $dbSettings);
        } catch (\Throwable $e) {
            // Fallback aman ke file JSON jika koneksi atau tabel database belum siap
            return $defaultSettings;
        }
    }

    /**
     * Simpan bagian setting ke DB dan file settings.json
     */
    public static function updateSetting(string $key, array $value): void
    {
        try {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        } catch (\Throwable $e) {
            // Abaikan kegagalan database dan tetap simpan ke file JSON
        }

        $filePath = storage_path('app/settings.json');
        $current = [];
        if (File::exists($filePath)) {
            $current = json_decode(File::get($filePath), true) ?: [];
        }
        $current[$key] = $value;
        File::put($filePath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

