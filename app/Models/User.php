<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nik',
        'name',
        'email',
        'pin',
        'password',
        'role',
        'department',
        'position',
        'phone',
        'office_id',
        'office_name',
        'avatar_url',
        'status_pegawai',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id', 'id');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'nik', 'nik');
    }

    public function isStaffIT(): bool
    {
        return $this->role === 'staffIT';
    }

    public function isDirektur(): bool
    {
        return $this->role === 'direktur';
    }

    public function isKaryawan(): bool
    {
        return $this->role === 'karyawan';
    }

    /**
     * Verifikasi PIN / Password (mendukung plain text demo, bcrypt, & md5 legacy)
     */
    public function verifySecret(?string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        $secret = trim($secret);

        // 1. Cek kecocokan PIN langsung
        if (!empty($this->pin) && $this->pin === $secret) {
            return true;
        }

        // 2. Cek password hash bcrypt
        if (Hash::check($secret, $this->password)) {
            return true;
        }

        // 3. Cek password plain text legacy
        if ($this->password === $secret) {
            return true;
        }

        // 4. Cek password md5 legacy
        if (md5($secret) === $this->password) {
            return true;
        }

        // 5. Fallback demo PIN
        if ($secret === '123456') {
            return true;
        }

        return false;
    }

    /**
     * Serialisasi respons terstandarisasi untuk Mobile Flutter & Web
     */
    public function toApiResponse(): array
    {
        $resolvedOfficeName = $this->office?->nama_kantor 
            ?? $this->office_name 
            ?? 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang';

        $cleanPin = !empty($this->pin) && strlen($this->pin) >= 4 ? $this->pin : '123456';

        return [
            'id'          => (string)$this->id,
            'nip'         => (string)$this->nik,
            'nik'         => (string)$this->nik,
            'name'        => (string)$this->name,
            'email'       => !empty($this->email) 
                ? $this->email 
                : strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z0-9 ]/', '', $this->name))) . '@tirtafield.co.id',
            'pin'         => $cleanPin,
            'role'        => (string)$this->role,
            'department'  => (string)$this->department,
            'position'    => (string)$this->position,
            'status'      => (string)($this->status_pegawai ?: 'Karyawan Tetap'),
            'phone'       => (string)($this->phone ?: '081234567890'),
            'office_id'   => (string)($this->office_id ?: 'hq'),
            'office_name' => $resolvedOfficeName,
            'avatar_url'  => $this->avatar_url ?: null,
        ];
    }
}
