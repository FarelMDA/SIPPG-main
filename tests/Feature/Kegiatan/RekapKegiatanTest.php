<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\RekapKegiatan;
use App\Models\Kegiatan;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RekapKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_kelompok_tidak_bisa_lihat_rekap_kegiatan_kelompok_lain(): void
    {
        $kelompokSendiri = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();

        $kegiatanLain = Kegiatan::create([
            'nama' => 'Kegiatan Kelompok Lain',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompokLain->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERLAKSANA',
            'dibuat_oleh' => User::factory()->create()->id,
        ]);

        $pjk = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(RekapKegiatan::class, ['kegiatan' => $kegiatanLain])
            ->assertForbidden();
    }

    public function test_pjp_kelompok_bisa_lihat_rekap_kegiatan_daerah(): void
    {
        $kelompok = Kelompok::factory()->create();

        $kegiatanDaerah = Kegiatan::create([
            'nama' => 'Kegiatan Daerah',
            'tingkat' => 'DAERAH',
            'penyelenggara_type' => 'daerah',
            'penyelenggara_id' => 1,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERLAKSANA',
            'dibuat_oleh' => User::factory()->create()->id,
        ]);

        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(RekapKegiatan::class, ['kegiatan' => $kegiatanDaerah])
            ->assertOk();
    }
}
