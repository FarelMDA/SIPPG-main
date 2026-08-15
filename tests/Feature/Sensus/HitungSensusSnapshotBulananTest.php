<?php

namespace Tests\Feature\Sensus;

use App\Jobs\HitungSensusSnapshotBulanan;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HitungSensusSnapshotBulananTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sensus per-Jenjang harus ikut generus.jenjang_id (individual), bukan lagi
     * kelas.jenjang_id — karena satu Kelas sekarang bisa dipakai bersama oleh Generus
     * dari Jenjang berbeda (RombonganBelajar gabungan), kelas_id saja tidak lagi
     * cukup untuk menentukan Jenjang seorang Generus.
     */
    public function test_snapshot_dikelompokkan_per_jenjang_individual_generus(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $dasar2 = Jenjang::where('kode', 'DASAR_2')->firstOrFail();

        // Dua Generus berbagi kelas_id yang SAMA, tapi Jenjang individualnya beda.
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id, 'jenjang_id' => $dasar1->id]);

        Generus::factory()->create(['kelas_id' => $kelas->id, 'jenjang_id' => $dasar1->id, 'status_domisili' => 'SETEMPAT', 'jenis_kelamin' => 'LAKI']);
        Generus::factory()->create(['kelas_id' => $kelas->id, 'jenjang_id' => $dasar2->id, 'status_domisili' => 'SETEMPAT', 'jenis_kelamin' => 'LAKI']);

        (new HitungSensusSnapshotBulanan('2026-08'))->handle();

        $this->assertDatabaseHas('sensus_snapshots', [
            'kelompok_id' => $kelompok->id, 'periode' => '2026-08', 'jenjang' => 'DASAR_1', 'jumlah' => 1,
        ]);
        $this->assertDatabaseHas('sensus_snapshots', [
            'kelompok_id' => $kelompok->id, 'periode' => '2026-08', 'jenjang' => 'DASAR_2', 'jumlah' => 1,
        ]);
    }
}
