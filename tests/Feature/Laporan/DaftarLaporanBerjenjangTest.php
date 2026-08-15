<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\DaftarLaporanBerjenjang;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DaftarLaporanBerjenjangTest extends TestCase
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

    public function test_pjp_desa_menelusuri_laporan_kelompok_di_desanya(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);
        LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => '2026-08', 'versi' => 1,
            'status' => 'DISETUJUI', 'dibuat_oleh' => User::factory()->create()->id,
        ]);

        Livewire::actingAs($this->pjdUntuk($desa), 'web')
            ->test(DaftarLaporanBerjenjang::class)
            ->assertSet('tingkat', 'KELOMPOK')
            ->set('entitasId', $kelompok->id)
            ->assertSet('entitasId', $kelompok->id);
    }

    public function test_pjp_desa_tidak_bisa_menelusuri_kelompok_di_luar_desanya(): void
    {
        $desaSendiri = Desa::factory()->create();
        $desaLain = Desa::factory()->create();
        $kelompokLain = Kelompok::factory()->create(['desa_id' => $desaLain->id]);

        Livewire::actingAs($this->pjdUntuk($desaSendiri), 'web')
            ->test(DaftarLaporanBerjenjang::class)
            ->set('entitasId', $kelompokLain->id)
            ->assertSet('entitasId', null);
    }

    public function test_pjp_desa_tidak_bisa_beralih_ke_tingkat_desa(): void
    {
        $desa = Desa::factory()->create();

        Livewire::actingAs($this->pjdUntuk($desa), 'web')
            ->test(DaftarLaporanBerjenjang::class)
            ->set('tingkat', 'DESA')
            ->assertSet('tingkat', 'KELOMPOK');
    }

    public function test_admin_daerah_menelusuri_laporan_desa_dan_kelompok_manapun(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);

        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(DaftarLaporanBerjenjang::class)
            ->assertSet('tingkat', 'DESA')
            ->set('entitasId', $desa->id)
            ->assertSet('entitasId', $desa->id)
            ->set('tingkat', 'KELOMPOK')
            ->assertSet('entitasId', null)
            ->set('entitasId', $kelompok->id)
            ->assertSet('entitasId', $kelompok->id);
    }
}
