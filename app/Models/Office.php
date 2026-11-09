<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $table = 'offices';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'nama_kantor',
        'alamat',
        'latitude',
        'longitude',
        'radius',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getRadiusMetersAttribute(): int
    {
        return (int)($this->radius ?? 100);
    }

    public function getRadiusMeterAttribute(): int
    {
        return (int)($this->radius ?? 100);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'office_id', 'id');
    }

    public function toApiResponse(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->nama_kantor,
            'nama_kantor'   => $this->nama_kantor,
            'address'       => $this->alamat,
            'alamat'        => $this->alamat,
            'latitude'      => (float)$this->latitude,
            'longitude'     => (float)$this->longitude,
            'radius'        => (int)$this->radius,
            'radius_meters' => (int)$this->radius,
            'is_primary'    => (bool)$this->is_primary,
        ];
    }
}

