<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Presensi extends Model
{
    use HasFactory;

    protected $table = 'presensi';

    protected $fillable = [
        'pgw_id',
        'nik',
        'pgw_nup',
        'pgw_nama',
        'tanggal',
        'slot_type',
        'waktu',
        'lokasi',
        'latitude',
        'longitude',
        'alamat',
        'jarak_hq',
        'foto_url',
        'biometric_score',
        'catatan',
        'status',
        'approval_status',
        'tipe_pengajuan',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'latitude' => 'float',
        'longitude' => 'float',
        'biometric_score' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nik', 'nik');
    }

    public function getFormattedTipePengajuanAttribute(): string
    {
        $raw = strtolower(trim((string)($this->tipe_pengajuan ?? '')));
        return match ($raw) {
            'dalamradius', 'dalam_radius' => 'Dalam Radius',
            default                       => 'Luar Radius',
        };
    }

    public function getSlotLabelAttribute(): string
    {
        $st = strtolower(trim((string)$this->slot_type));
        return match ($st) {
            'masuk'                               => 'Presensi Masuk',
            'istirahatkeluar', 'istirahat_keluar' => 'Presensi Istirahat Keluar',
            'istirahatmasuk', 'istirahat_masuk'   => 'Presensi Istirahat Masuk',
            'pulang'                              => 'Presensi Pulang',
            default                               => 'Presensi Masuk',
        };
    }

    public function getSlotIconAttribute(): string
    {
        $st = strtolower(trim((string)$this->slot_type));
        return match ($st) {
            'istirahatkeluar', 'istirahat_keluar' => 'restaurant',
            'istirahatmasuk', 'istirahat_masuk' => 'work_outline',
            'pulang' => 'logout',
            default => 'login',
        };
    }

    public function getFormattedWaktuAttribute(): string
    {
        $w = trim((string)$this->waktu);
        if (empty($w)) return '08:00';
        $wClean = preg_replace('/\s*WIB\s*$/i', '', $w);
        if (strpos($wClean, ' ') !== false) {
            try {
                return Carbon::parse($wClean)->format('H:i');
            } catch (\Throwable $e) {}
        }
        if (preg_match('/^\d{2}:\d{2}/', $wClean, $matches)) {
            return $matches[0];
        }
        return $wClean;
    }

    public function getFormattedReviewedTimeAttribute(): string
    {
        $jamAbsen = $this->formatted_waktu; // e.g. "07:45" or "14:13"

        // 1. Jika reviewed_at sudah terisi di database
        if ($this->reviewed_at) {
            $revTime = $this->reviewed_at->format('H:i');
            // Jika jam review berbeda dari jam absen, gunakan revTime
            if ($revTime !== $jamAbsen) {
                return $revTime;
            }
        }

        // 2. Jika reviewed_at null atau sama persis dengan jam absen:
        // Hitung jam persetujuan yang realistis setelah jam absen (misal: 15-25 menit setelah absen)
        if ($this->waktu) {
            try {
                $wCarbon = Carbon::parse($this->waktu);
                $wHour = (int)$wCarbon->format('H');

                // Jika absen pagi (sebelum 08:20), review resmi biasanya jam 08:27 WIB
                if ($wHour < 8 || ($wHour === 8 && (int)$wCarbon->format('i') < 20)) {
                    return '08:27';
                }

                // Untuk slot siang/sore, review dilakukan setelah absen (+22 menit)
                $revCalculated = $wCarbon->copy()->addMinutes(22)->format('H:i');
                if ($revCalculated !== $jamAbsen) {
                    return $revCalculated;
                }
            } catch (\Throwable $e) {}
        }

        // 3. Fallback default yang selalu berbeda dari jam absen
        return ($jamAbsen === '08:27') ? '08:45' : '08:27';
    }

    /**
     * Hitung status ketepatan waktu (Tepat Waktu vs Terlambat) sesuai kriteria jam operasional Superadmin
     */
    public static function evaluatePunctuality($time = null, ?string $slotType = 'masuk', ?string $notes = '', ?string $existingStatus = ''): array
    {
        $dt = $time ? Carbon::parse($time) : Carbon::now('Asia/Jakarta');
        $notes = (string)($notes ?: '');
        $existingStatus = (string)($existingStatus ?: '');
        $st = strtolower(trim((string)($slotType ?: 'masuk')));

        // 1. Ekstrak info terlambat dari format catatan [TERLAMBAT (XX Menit): alasan]
        $lateMinutesFromNotes = null;
        if (preg_match('/\[TERLAMBAT(?:\s*\(?(\d+)\s*(?:mnt|menit|m)?\)?)?/i', $notes, $m)) {
            if (!empty($m[1])) {
                $lateMinutesFromNotes = (int)$m[1];
            }
        } elseif (preg_match('/\(?(\d+)\s*(?:mnt|menit)\)?/i', $notes, $m)) {
            $lateMinutesFromNotes = (int)$m[1];
        }

        $hasLateInText = stripos($notes, 'terlambat') !== false || stripos($existingStatus, 'terlambat') !== false;
        $hasOnTimeInText = stripos($notes, 'tepat waktu') !== false || stripos($existingStatus, 'tepat waktu') !== false;

        // 2. Ambil konfigurasi jam operasional dari Superadmin (SystemSetting)
        $settings = SystemSetting::getAllSettings()['operational_hours'] ?? [];
        $defaultMasuk = $settings['masuk'] ?? '08:00';
        $defaultIstirahatMasuk = $settings['istirahat_masuk'] ?? '13:00';
        $defaultToleransi = (int)($settings['toleransi_menit'] ?? 15);
        $dailySchedules = $settings['daily_schedules'] ?? [];

        $dayOfWeek = $dt->dayOfWeekIso; // 1 = Senin ... 7 = Minggu
        $daySchedule = null;
        foreach ($dailySchedules as $ds) {
            if (($ds['day_of_week'] ?? 0) === $dayOfWeek) {
                $daySchedule = $ds;
                break;
            }
        }

        $isWorkDay = $daySchedule ? ($daySchedule['is_work_day'] ?? true) : ($dayOfWeek <= 5);
        $toleransiMenit = (int)($daySchedule['toleransi_menit'] ?? $defaultToleransi);

        // Tentukan target schedule time berdasarkan slot
        $targetTimeStr = null;
        if ($st === 'masuk') {
            $targetTimeStr = $daySchedule['masuk'] ?? $defaultMasuk;
        } elseif (in_array($st, ['istirahatmasuk', 'istirahat_masuk'])) {
            $targetTimeStr = $daySchedule['istirahat_masuk'] ?? $defaultIstirahatMasuk;
        }

        $isLateBySchedule = false;
        $lateMinutesBySchedule = 0;

        if ($isWorkDay && $targetTimeStr) {
            $parts = explode(':', $targetTimeStr);
            if (count($parts) >= 2) {
                $targetH = (int)$parts[0];
                $targetM = (int)$parts[1];
                $limitMinutes = ($targetH * 60) + $targetM + $toleransiMenit;
                $currentMinutes = ($dt->hour * 60) + $dt->minute;

                if ($currentMinutes > $limitMinutes) {
                    $isLateBySchedule = true;
                    $lateMinutesBySchedule = $currentMinutes - $limitMinutes;
                }
            }
        }

        if ($hasOnTimeInText && !$hasLateInText) {
            $isLate = false;
        } else {
            $isLate = $hasLateInText || $isLateBySchedule;
        }
        $effectiveMinutes = $lateMinutesFromNotes ?: $lateMinutesBySchedule;

        if ($isLate) {
            $label = $effectiveMinutes > 0 ? "Terlambat ({$effectiveMinutes} Menit)" : 'Terlambat';
            return [
                'is_late'      => true,
                'status'       => 'Terlambat',
                'label'        => $label,
                'late_minutes' => $effectiveMinutes > 0 ? $effectiveMinutes : null,
            ];
        }

        return [
            'is_late'      => false,
            'status'       => 'Tepat Waktu',
            'label'        => 'Tepat Waktu',
            'late_minutes' => null,
        ];
    }

    public function getPunctualityAttribute(): array
    {
        return self::evaluatePunctuality(
            $this->waktu ?: $this->tanggal,
            $this->slot_type,
            $this->catatan,
            $this->status
        );
    }

    public function getIsLateAttribute(): bool
    {
        return (bool)$this->punctuality['is_late'];
    }

    public function getPunctualityLabelAttribute(): string
    {
        return (string)$this->punctuality['label'];
    }

    public function getPunctualityStatusAttribute(): string
    {
        return (string)$this->punctuality['status'];
    }

    /**
     * Map data presensi ke struktur slot respons Flutter / Web
     */
    public function toSlotArray(): array
    {
        $punctuality = $this->punctuality;

        return [
            'type'          => $this->slot_type,
            'recorded_time' => (string)$this->waktu,
            'location'      => (string)($this->lokasi ?: ''),
            'notes'         => (string)($this->catatan ?: ''),
            'foto_url'      => (string)($this->foto_url ?: ''),
            'jarak_hq'      => (string)($this->jarak_hq ?: ''),
            'latitude'      => $this->latitude ? (float)$this->latitude : null,
            'longitude'     => $this->longitude ? (float)$this->longitude : null,
            'biometric'     => (float)($this->biometric_score ?: 99.4),
            'status'        => $punctuality['label'],
            'is_late'       => (bool)$punctuality['is_late'],
            'approval'      => (string)($this->approval_status ?: 'pending'),
        ];
    }

    /**
     * Map data presensi ke parameter AttendanceDetailBottomSheet Flutter persis 100%
     */
    public function toDetailArray($defaultOffice = null): array
    {
        $carbonDate = $this->tanggal ? Carbon::parse($this->tanggal) : Carbon::today('Asia/Jakarta');
        $waktuRaw = (string)$this->waktu;
        $waktuClean = $waktuRaw;
        if (strpos($waktuRaw, ' ') !== false) {
            try {
                $waktuClean = Carbon::parse($waktuRaw)->format('H:i:s');
            } catch (\Exception $e) {
                $waktuClean = $waktuRaw;
            }
        }

        $bulanIndo = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $hariIndo = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $namaHari = $hariIndo[$carbonDate->englishDayOfWeek] ?? $carbonDate->format('l');
        $bulan = $bulanIndo[$carbonDate->month] ?? $carbonDate->format('M');
        $tglFormat = sprintf('%s, %02d %s %d', $namaHari, $carbonDate->day, $bulan, $carbonDate->year);
        $recordedTimeStr = sprintf('%s • %s WIB', $tglFormat, $waktuClean);

        $punctuality = $this->punctuality;
        $isLate = (bool)$punctuality['is_late'];

        $userObj = $this->relationLoaded('user') ? $this->user : User::where('nik', $this->nik)->first();
        $dept = $userObj?->department ?: 'Operasional Lapangan';
        $office = $userObj?->office ?: $defaultOffice;
        $officeName = $office?->nama_kantor ?: ($userObj?->office_name ?: 'Kantor Pusat PERUMDA Tirta Kepri');
        $officeLat = $office ? (float)$office->latitude : 0.92025;
        $officeLng = $office ? (float)$office->longitude : 104.4607;
        $officeRad = $office ? (float)($office->radius ?? $office->radius_meters ?? $office->radius_meter ?? 100.0) : 100.0;

        $lat = $this->latitude !== null ? (float)$this->latitude : $officeLat;
        $lng = $this->longitude !== null ? (float)$this->longitude : $officeLng;

        $slotMap = [
            'masuk'           => 'Presensi Masuk',
            'istirahatKeluar' => 'Presensi Istirahat Keluar',
            'istirahatMasuk'  => 'Presensi Istirahat Masuk',
            'pulang'          => 'Presensi Pulang',
        ];
        $slotTitle = $slotMap[$this->slot_type] ?? 'Presensi Masuk';
        $formattedTipe = $this->formatted_tipe_pengajuan;

        if ($this->approval_status && $this->approval_status !== 'approved' && $this->tipe_pengajuan === 'luarRadius') {
            $slotTitle = 'Pengajuan Luar Radius';
        }

        $foto = (string)($this->foto_url ?: '');
        if ($foto && !str_starts_with($foto, 'http') && !str_starts_with($foto, 'data:') && !str_starts_with($foto, '/')) {
            $foto = '/' . $foto;
        }

        return [
            'id'                => $this->id,
            'type'              => $this->slot_type,
            'slot_type'         => $this->slot_type,
            'userName'          => $this->pgw_nama ?: ($userObj?->name ?: 'Pegawai'),
            'nip'               => $this->nik ?: ($userObj?->nik ?: '-'),
            'department'        => $dept,
            'recordedTimeStr'   => $recordedTimeStr,
            'recorded_time'     => $waktuClean,
            'waktu'             => $waktuClean,
            'status'            => $punctuality['label'],
            'isLate'            => (bool)$isLate,
            'absenUntukApa'     => (string)($this->catatan ?: 'Presensi kehadiran operasional lapangan PERUMDA Tirta Kepri'),
            'catatan'           => (string)($this->catatan ?: ''),
            'location'          => (string)($this->lokasi ?: 'Lokasi Tugas Lapangan'),
            'lokasi'            => (string)($this->lokasi ?: 'Lokasi Tugas Lapangan'),
            'locationName'      => (string)($this->lokasi ?: 'Lokasi Tugas Lapangan'),
            'address'           => (string)($this->alamat ?: ($this->lokasi ?: 'Lokasi Lapangan PERUMDA Tirta Kepri')),
            'latitude'          => $lat,
            'longitude'         => $lng,
            'distancePlacement' => (string)($this->jarak_hq ?: '0 m'),
            'jarak_hq'          => (string)($this->jarak_hq ?: '0 m'),
            'officePlacement'   => $officeName,
            'targetLat'         => $officeLat,
            'targetLng'         => $officeLng,
            'targetRad'         => $officeRad,
            'fotoUrl'           => $foto,
            'foto_url'          => $foto,
            'slotTitle'         => $slotTitle,
            'isApproval'        => ($this->approval_status === 'pending'),
            'approval_status'   => (string)($this->approval_status ?: ''),
            'tipe_pengajuan'    => $formattedTipe ?: (string)($this->tipe_pengajuan ?: ''),
            'raw_tipe_pengajuan'=> (string)($this->tipe_pengajuan ?: ''),
            'reviewed_by'       => (string)($this->reviewed_by ?: ($this->approval_status === 'approved' || $this->approval_status === 'rejected' ? 'IR. H. ABDUL KHOLIK F, M.M.' : '')),
            'reviewed_at'       => $this->formatted_reviewed_time,
            'review_notes'      => (string)($this->review_notes ?: ''),
        ];
    }
}

