<?php

namespace Tests\Feature\MasterData;

use App\Livewire\MasterData\KelolaPendidik;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-06 — Kelola Data Pendidik. SRS §6.3, UCIC UC-06.
 */
class KelolaPendidikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bidang_tendik_bisa_membuat_pendidik_di_kelompok_manapun(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);

        $bidangTendik = User::factory()->create();
        $bidangTendik->assignRole('bidang-tendik');

        Livewire::actingAs($bidangTendik, 'web')
            ->test(KelolaPendidik::class)
            ->set('nama', 'Ustadz Fulan')
            ->set('jenis', 'MT')
            ->set('desa_id', $desa->id)
            ->set('kelompok_id', $kelompok->id)
            ->call('simpan');

        $this->assertDatabaseHas('pendidik', ['nama' => 'Ustadz Fulan', 'kelompok_id' => $kelompok->id]);
    }

    public function test_pjp_kelompok_membuat_pendidik_otomatis_di_kelompoknya_sendiri(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaPendidik::class)
            ->set('nama', 'Ustadzah Fulanah')
            ->set('jenis', 'MS')
            ->call('simpan');

        $this->assertDatabaseHas('pendidik', ['nama' => 'Ustadzah Fulanah', 'kelompok_id' => $kelompok->id]);
    }
}
