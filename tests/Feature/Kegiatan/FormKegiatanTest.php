<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\FormKegiatan;
use App\Models\Kegiatan;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-21 — Tambah/Ubah Kegiatan (halaman biasa, bukan modal).
 */
class FormKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_kelompok_hanya_bisa_buat_kegiatan_tingkat_kelompok(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(FormKegiatan::class)
            ->set('nama', 'Kegiatan Tambahan')
            ->set('jenisKegiatanId', \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'))
            ->set('tanggal', now()->toDateString())
            ->call('simpan')
            ->assertRedirect(route('kegiatan.index'));

        $this->assertDatabaseHas('kegiatan', [
            'nama' => 'Kegiatan Tambahan',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
        ]);
    }

    public function test_pjp_kelompok_bisa_ubah_kegiatan_kelompoknya_sendiri(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $kegiatan = Kegiatan::create([
            'nama' => 'Nama Lama', 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok', 'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'), 'tanggal' => now()->toDateString(), 'status' => 'TERJADWAL', 'target_tipe' => 'SEMUA', 'dibuat_oleh' => $pjk->id,
        ]);

        Livewire::actingAs($pjk, 'web')
            ->test(FormKegiatan::class, ['kegiatan' => $kegiatan])
            ->set('nama', 'Nama Baru')
            ->call('simpan')
            ->assertRedirect(route('kegiatan.index'));

        $this->assertSame('Nama Baru', $kegiatan->fresh()->nama);
    }

    public function test_pjp_kelompok_tidak_bisa_ubah_kegiatan_kelompok_lain(): void
    {
        $kelompokLain = Kelompok::factory()->create();
        $pembuat = User::factory()->create();

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Kelompok Lain', 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok', 'penyelenggara_id' => $kelompokLain->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'), 'tanggal' => now()->toDateString(), 'status' => 'TERJADWAL', 'dibuat_oleh' => $pembuat->id,
        ]);

        $kelompokSendiri = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(FormKegiatan::class, ['kegiatan' => $kegiatan])
            ->assertForbidden();
    }
}
