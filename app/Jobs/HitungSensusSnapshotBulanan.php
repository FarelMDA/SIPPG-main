<?php

namespace App\Jobs;

use App\Models\Generus;
use App\Models\Kelompok;
use App\Models\SensusSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * UC-09 — Sensus otomatis, dijalankan scheduler tanggal 1 tiap bulan (SRS §7).
 * Menyimpan hasil hitung ke sensus_snapshots agar bisa dibandingkan antar bulan
 * tanpa perlu hitung ulang data historis.
 */
class HitungSensusSnapshotBulanan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $periode = null) {}

    public function handle(): void
    {
        $periode = $this->periode ?? now()->format('Y-m');

        foreach (Kelompok::pluck('id') as $kelompokId) {
            $rows = Generus::query()
                ->withoutGlobalScopes()
                ->join('kelas', 'kelas.id', '=', 'generus.kelas_id')
                ->join('jenjang', 'jenjang.id', '=', 'generus.jenjang_id')
                ->where('kelas.kelompok_id', $kelompokId)
                ->where('generus.status_aktif', true)
                ->selectRaw('jenjang.kode as jenjang, generus.status_domisili, generus.jenis_kelamin, count(*) as jumlah')
                ->groupBy('jenjang.kode', 'generus.status_domisili', 'generus.jenis_kelamin')
                ->get();

            foreach ($rows as $row) {
                SensusSnapshot::updateOrCreate(
                    [
                        'kelompok_id' => $kelompokId,
                        'periode' => $periode,
                        'jenjang' => $row->jenjang,
                        'status_domisili' => $row->status_domisili,
                        'jenis_kelamin' => $row->jenis_kelamin,
                    ],
                    ['jumlah' => $row->jumlah]
                );
            }
        }
    }
}
