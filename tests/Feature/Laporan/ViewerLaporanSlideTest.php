<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\ViewerLaporanSlide;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\SensusSnapshot;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewerLaporanSlideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_draft_mengikuti_perubahan_data_sumber_secara_live(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'DRAFT', 'dibuat_oleh' => $pjk->id,
        ]);

        $component = Livewire::actingAs($pjk, 'web')->test(ViewerLaporanSlide::class, ['laporan' => $laporan]);
        $sensusAwal = collect($component->get('data')['sensus']['per_kategori']);
        $this->assertTrue($sensusAwal->isEmpty());

        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id, 'periode' => '2026-08', 'jenjang' => 'ACR',
            'status_domisili' => 'SETEMPAT', 'jenis_kelamin' => 'LAKI', 'jumlah' => 3,
        ]);

        $component = Livewire::actingAs($pjk, 'web')->test(ViewerLaporanSlide::class, ['laporan' => $laporan]);
        $sensusBaru = collect($component->get('data')['sensus']['per_kategori']);
        $this->assertSame(3, $sensusBaru->firstWhere('jenjang', 'ACR')['laki']);
    }

    public function test_finalisasi_membekukan_snapshot_dan_tidak_lagi_mengikuti_data_sumber(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'DRAFT', 'dibuat_oleh' => $pjk->id,
        ]);

        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id, 'periode' => '2026-08', 'jenjang' => 'ACR',
            'status_domisili' => 'SETEMPAT', 'jenis_kelamin' => 'LAKI', 'jumlah' => 3,
        ]);

        Livewire::actingAs($pjk, 'web')->test(ViewerLaporanSlide::class, ['laporan' => $laporan])->call('finalisasi');

        $laporan->refresh();
        $this->assertSame('FINAL', $laporan->status);
        $this->assertNotNull($laporan->difinalisasi_pada);
        $this->assertSame(3, collect($laporan->snapshot_data['sensus']['per_kategori'])->firstWhere('jenjang', 'ACR')['laki']);

        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id, 'periode' => '2026-08', 'jenjang' => 'ACR',
            'status_domisili' => 'PENDATANG', 'jenis_kelamin' => 'LAKI', 'jumlah' => 99,
        ]);

        $laporan->refresh();
        $this->assertSame(3, collect($laporan->snapshot_data['sensus']['per_kategori'])->firstWhere('jenjang', 'ACR')['laki']);
    }

    public function test_finalisasi_gagal_jika_bukan_draft(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'FINAL', 'snapshot_data' => ['cover' => []], 'dibuat_oleh' => $pjk->id,
        ]);

        Livewire::actingAs($pjk, 'web')
            ->test(ViewerLaporanSlide::class, ['laporan' => $laporan])
            ->call('finalisasi')
            ->assertForbidden();
    }

    public function test_bukan_pemilik_tidak_bisa_finalisasi(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pembuat = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjkLain = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjkLain->assignRole('pjp-kelompok');

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'DRAFT', 'dibuat_oleh' => $pembuat->id,
        ]);

        $component = Livewire::actingAs($pjkLain, 'web')->test(ViewerLaporanSlide::class, ['laporan' => $laporan]);
        $this->assertFalse($component->get('bisaFinalisasi'));

        $component->call('finalisasi')->assertForbidden();
    }

    public function test_kelompok_lain_tidak_bisa_melihat_laporan(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();
        $pjkLain = User::factory()->create(['kelompok_id' => $kelompokLain->id]);
        $pjkLain->assignRole('pjp-kelompok');

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'DRAFT', 'dibuat_oleh' => User::factory()->create()->id,
        ]);

        Livewire::actingAs($pjkLain, 'web')
            ->test(ViewerLaporanSlide::class, ['laporan' => $laporan])
            ->assertForbidden();
    }
}
