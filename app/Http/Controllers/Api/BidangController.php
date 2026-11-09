<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BidangController extends Controller
{
    /**
     * Legacy Router untuk endpoint /api/bidang.php
     */
    public function legacyRouter(Request $request): JsonResponse
    {
        return $this->list();
    }

    /**
     * Dapatkan daftar bidang beserta seksi bidang
     */
    public function list(): JsonResponse
    {
        $bidangList = Bidang::with('seksi')->orderBy('urutan', 'asc')->get();

        $result = $bidangList->map(function ($b) {
            return [
                'id'    => (int)$b->id,
                'nama'  => $b->nama,
                'kode'  => $b->kode,
                'seksi' => $b->seksi->map(function ($s) {
                    return [
                        'id'   => (int)$s->id,
                        'nama' => $s->nama,
                        'kode' => $s->kode,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar bidang dan seksi bidang berhasil dimuat.',
            'data'    => $result,
        ], 200);
    }
}

