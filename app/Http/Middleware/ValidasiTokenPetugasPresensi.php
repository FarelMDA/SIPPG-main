<?php

namespace App\Http\Middleware;

use App\Models\KegiatanPetugasPresensi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SRS §2.1, §18.2 — jalur akses ketiga TANPA guard, khusus Petugas Presensi
 * Kegiatan berupa Generus (tanpa akun `users`). Bukan guard formal — middleware
 * ini memvalidasi UUID `kegiatan_petugas_presensi.token` langsung dari URL,
 * dibatasi ke satu Kegiatan & satu Kelompok saja, kedaluwarsa otomatis.
 */
class ValidasiTokenPetugasPresensi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        $petugas = KegiatanPetugasPresensi::where('token', $token)->first();

        if (! $petugas || ! $petugas->tokenMasihBerlaku()) {
            return response()->view('errors.token-tidak-berlaku', [], 200);
        }

        $request->attributes->set('petugasPresensiToken', $petugas);

        return $next($request);
    }
}
