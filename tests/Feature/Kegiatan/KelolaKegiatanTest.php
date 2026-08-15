<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\KelolaKegiatan;
use App\Models\Kegiatan;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Tambah/Ubah Kegiatan ada di FormKegiatanTest — komponen ini kini list-only. */
class KelolaKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_kelompok_tidak_melihat_kegiatan_kelompok_lain_di_daftar(): void
    {
        $kelompokSendiri = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();
        $pembuat = User::factory()->create();

        Kegiatan::create([
            'nama' => 'Kegiatan Kelompok Sendiri',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompokSendiri->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $pembuat->id,
        ]);

        Kegiatan::create([
            'nama' => 'Kegiatan Kelompok Lain',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompokLain->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $pembuat->id,
        ]);

        $pjk = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $pjk->assignRole('pjp-kelompok');

        $component = Livewire::actingAs($pjk, 'web')
            ->test(KelolaKegiatan::class)
            ->set('filterTingkat', '');

        $daftar = $component->instance()->daftar;

        $this->assertTrue($daftar->pluck('nama')->contains('Kegiatan Kelompok Sendiri'));
        $this->assertFalse($daftar->pluck('nama')->contains('Kegiatan Kelompok Lain'));
    }
}
