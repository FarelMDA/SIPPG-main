<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\DashboardKelompok;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardKelompokTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_desa_tidak_punya_akses_dashboard_kelompok(): void
    {
        // PJP Desa belum punya Dashboard di Fase 1 (SRS §1.1) — dashboard/agregasi
        // Desa menyusul Fase 2 (UCIC-Fase-2 UC-34). Lihat juga LoginTest/GantiPasswordTest
        // untuk landing page pengganti (Kelola Struktur Organisasi).
        $pjd = User::factory()->create();
        $pjd->assignRole('pjp-desa');

        Livewire::actingAs($pjd, 'web')->test(DashboardKelompok::class)->assertForbidden();
    }

    public function test_pjp_kelompok_tetap_bisa_akses_dashboard_kelompok(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')->test(DashboardKelompok::class)->assertOk();
    }

    public function test_wanbin_desa_terisi_kelompok_pertama_di_desanya_bukan_kosong(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);

        $wanbin = User::factory()->create(['desa_id' => $desa->id]);
        $wanbin->assignRole('wanbin-desa');

        Livewire::actingAs($wanbin, 'web')
            ->test(DashboardKelompok::class)
            ->assertOk()
            ->assertSet('kelompok_id', $kelompok->id);
    }

    public function test_wanbin_desa_hanya_bisa_pindah_ke_kelompok_dalam_desanya(): void
    {
        $desaSendiri = Desa::factory()->create();
        $kelompokSendiri = Kelompok::factory()->create(['desa_id' => $desaSendiri->id]);

        $desaLain = Desa::factory()->create();
        $kelompokLain = Kelompok::factory()->create(['desa_id' => $desaLain->id]);

        $wanbin = User::factory()->create(['desa_id' => $desaSendiri->id]);
        $wanbin->assignRole('wanbin-desa');

        Livewire::actingAs($wanbin, 'web')
            ->test(DashboardKelompok::class)
            ->set('kelompokPilihan', $kelompokLain->id)
            ->assertSet('kelompok_id', $kelompokSendiri->id);
    }

    public function test_admin_daerah_bisa_pindah_kelompok_bebas(): void
    {
        Kelompok::factory()->create();
        $kelompokB = Kelompok::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(DashboardKelompok::class)
            ->set('kelompokPilihan', $kelompokB->id)
            ->assertSet('kelompok_id', $kelompokB->id);
    }
}
