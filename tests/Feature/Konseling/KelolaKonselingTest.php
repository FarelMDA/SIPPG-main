<?php

namespace Tests\Feature\Konseling;

use App\Livewire\Konseling\KelolaKonseling;
use App\Models\Generus;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaKonselingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bk_kbm_bisa_mencatat_konseling(): void
    {
        $kelas = Kelas::factory()->create();
        $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
        $bk = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $bk->assignRole('bk-kbm');

        Livewire::actingAs($bk, 'web')
            ->test(KelolaKonseling::class)
            ->set('generus_id', $generus->id)
            ->set('tanggal', now()->toDateString())
            ->set('catatan', 'Perlu pendampingan tambahan.')
            ->call('simpan');

        $this->assertDatabaseHas('konseling_catatan', [
            'kelompok_id' => $kelas->kelompok_id,
            'generus_id' => $generus->id,
            'dicatat_oleh' => $bk->id,
        ]);
    }

    public function test_pjp_kelompok_hanya_bisa_melihat_bukan_mencatat(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaKonseling::class)
            ->assertOk()
            ->call('openCreate')
            ->assertForbidden();
    }

    public function test_guru_tidak_bisa_akses_halaman_konseling(): void
    {
        $kelompok = Kelompok::factory()->create();
        $guru = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $guru->assignRole('guru');

        Livewire::actingAs($guru, 'web')->test(KelolaKonseling::class)->assertForbidden();
    }
}
