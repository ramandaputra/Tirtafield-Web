<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeksiBidang extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ref_seksi_bidang';

    protected $fillable = [
        'bidang_id',
        'nama',
        'kode',
        'urutan',
    ];

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'bidang_id', 'id');
    }
}

