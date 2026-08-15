<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\ApprovalLaporan;
use App\Livewire\Laporan\GenerateLaporanKelompok;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisiVersiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_generate_setelah_ditolak_membuat_versi_baru_dan_arsip_versi_lama(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');
        $pjd = User::factory()->create(['desa_id' => $desa->id]);
        $pjd->assignRole('pjp-desa');

        $laporanV1 = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'FINAL', 'snapshot_data' => ['cover' => ['kelompok' => $kelompok->nama]],
            'dibuat_oleh' => $pjk->id, 'difinalisasi_oleh' => $pjk->id, 'difinalisasi_pada' => now(),
        ]);

        Livewire::actingAs($pjd, 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporanV1->id])
            ->set('catatan', 'Data sensus belum sesuai')
            ->call('tolak');

        $laporanV1->refresh();
        $this->assertSame('REVISI_DIMINTA', $laporanV1->status);
        $this->assertSame('Data sensus belum sesuai', $laporanV1->catatan_revisi);

        Livewire::actingAs($pjk, 'web')
            ->test(GenerateLaporanKelompok::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $this->assertSame(2, LaporanBulanan::where('kelompok_id', $kelompok->id)->where('periode', '2026-08')->count());

        $laporanV2 = LaporanBulanan::where('kelompok_id', $kelompok->id)->where('periode', '2026-08')->where('versi', 2)->first();
        $this->assertNotNull($laporanV2);
        $this->assertSame('DRAFT', $laporanV2->status);

        $laporanV1->refresh();
        $this->assertSame('REVISI_DIMINTA', $laporanV1->status);
        $this->assertSame('Data sensus belum sesuai', $laporanV1->catatan_revisi);
    }
}
