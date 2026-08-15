<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| routes/api.php — HANYA untuk sinkronisasi offline presensi & realisasi KBM
| Reguler (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5). Seluruh
| UI lain memakai Livewire di routes/web.php, bukan endpoint ini.
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/sync/bootstrap', [SyncController::class, 'bootstrap'])->name('api.sync.bootstrap');
    Route::post('/sync/presensi', [SyncController::class, 'presensi'])->name('api.sync.presensi');
    Route::post('/sync/realisasi-kegiatan', [SyncController::class, 'realisasiKegiatan'])->name('api.sync.realisasi-kegiatan');
});
