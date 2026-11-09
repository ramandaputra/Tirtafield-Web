<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ref_bidang';

    protected $fillable = [
        'nama',
        'kode',
        'urutan',
    ];

    public function seksi()
    {
        return $this->hasMany(SeksiBidang::class, 'bidang_id', 'id')->orderBy('urutan', 'asc');
    }
}

