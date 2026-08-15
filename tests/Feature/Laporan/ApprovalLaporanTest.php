<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\ApprovalLaporan;
use App\Models\Daerah;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function pjdUntuk(Desa $desa): User
    {
        $pjd = User::factory()->create(['desa_id' => $desa->id]);
        $pjd->assignRole('pjp-desa');

        return $pjd;
    }

    public function test_pjp_desa_menyetujui_laporan_kelompok_final(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => ['cover' => []], 'difinalisasi_pada' => now(),
        ]);

        Livewire::actingAs($this->pjdUntuk($desa), 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporan->id])
            ->call('setujui');

        $laporan->refresh();
        $this->assertSame('DISETUJUI', $laporan->status);
        $this->assertNotNull($laporan->disetujui_pada);
    }

    public function test_admin_daerah_menyetujui_laporan_desa(): void
    {
        $desa = Desa::factory()->create();
        $laporan = LaporanBulanan::create([
            'desa_id' => $desa->id, 'tingkat' => 'DESA', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => ['cover' => []], 'difinalisasi_pada' => now(),
        ]);

        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporan->id])
            ->call('setujui');

        $laporan->refresh();
        $this->assertSame('DISETUJUI', $laporan->status);
    }

    public function test_tolak_wajib_isi_catatan(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => ['cover' => []], 'difinalisasi_pada' => now(),
        ]);

        Livewire::actingAs($this->pjdUntuk($desa), 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporan->id])
            ->set('catatan', '')
            ->call('tolak')
            ->assertHasErrors(['catatan' => 'required']);

        $laporan->refresh();
        $this->assertSame('FINAL', $laporan->status);
    }

    public function test_setujui_laporan_yang_belum_final_ditolak(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'DRAFT', 'dibuat_oleh' => User::factory()->create()->id,
        ]);

        Livewire::actingAs($this->pjdUntuk($desa), 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporan->id])
            ->call('setujui')
            ->assertForbidden();
    }

    public function test_pjp_desa_lain_tidak_bisa_review_laporan_kelompok_luar_desanya(): void
    {
        $desaSendiri = Desa::factory()->create();
        $desaLain = Desa::factory()->create();
        $kelompokLain = Kelompok::factory()->create(['desa_id' => $desaLain->id]);

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompokLain->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => ['cover' => []], 'difinalisasi_pada' => now(),
        ]);

        Livewire::actingAs($this->pjdUntuk($desaSendiri), 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporan->id])
            ->call('setujui')
            ->assertForbidden();
    }

    public function test_laporan_daerah_tidak_bisa_direview_siapa_pun(): void
    {
        $daerah = Daerah::factory()->create();
        $laporan = LaporanBulanan::create([
            'daerah_id' => $daerah->id, 'tingkat' => 'DAERAH', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => ['cover' => []], 'difinalisasi_pada' => now(),
        ]);

        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(ApprovalLaporan::class, ['laporanId' => $laporan->id])
            ->call('setujui')
            ->assertForbidden();

        $laporan->refresh();
        $this->assertSame('FINAL', $laporan->status);
    }
}
