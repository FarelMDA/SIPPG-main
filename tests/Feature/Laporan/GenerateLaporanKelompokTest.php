<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\GenerateLaporanKelompok;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GenerateLaporanKelompokTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_generate_membuat_draft_baru_tanpa_snapshot(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(GenerateLaporanKelompok::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $this->assertDatabaseHas('laporan_bulanan', [
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08',
            'versi' => 1, 'status' => 'DRAFT', 'snapshot_data' => null,
        ]);
    }

    public function test_generate_ulang_pada_draft_yang_sama_tidak_membuat_baris_baru(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')->test(GenerateLaporanKelompok::class)->set('periode', '2026-08')->call('generate');
        Livewire::actingAs($pjk, 'web')->test(GenerateLaporanKelompok::class)->set('periode', '2026-08')->call('generate');

        $this->assertSame(1, LaporanBulanan::where('kelompok_id', $kelompok->id)->where('periode', '2026-08')->count());
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $kelompok = Kelompok::factory()->create();
        $guru = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $guru->assignRole('guru');

        Livewire::actingAs($guru, 'web')
            ->test(GenerateLaporanKelompok::class)
            ->assertForbidden();
    }
}
