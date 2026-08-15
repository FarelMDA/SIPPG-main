<?php

namespace Tests\Feature\MasterData;

use App\Livewire\MasterData\KelolaJenisMusyawaroh;
use App\Models\Desa;
use App\Models\JenisMusyawaroh;
use App\Models\Musyawaroh;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaJenisMusyawarohTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_selain_admin_daerah_sysadmin_tidak_bisa_akses(): void
    {
        $user = User::factory()->create();
        $user->assignRole('pjp-desa');

        Livewire::actingAs($user, 'web')
            ->test(KelolaJenisMusyawaroh::class)
            ->assertForbidden();
    }

    public function test_admin_daerah_bisa_tambah_jenis_musyawaroh(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaJenisMusyawaroh::class)
            ->call('openCreate')
            ->set('nama', 'Musyawaroh Tambahan')
            ->set('tingkat', 'KELOMPOK')
            ->set('urutan', 5)
            ->call('simpan');

        $this->assertDatabaseHas('jenis_musyawaroh', ['nama' => 'Musyawaroh Tambahan', 'tingkat' => 'KELOMPOK']);
    }

    public function test_admin_daerah_bisa_edit_jenis_musyawaroh(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $baris = JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('nama', 'Musyawaroh Pengurus KBM')->firstOrFail();

        Livewire::actingAs($admin, 'web')
            ->test(KelolaJenisMusyawaroh::class)
            ->call('openEdit', $baris->id)
            ->set('nama', 'Musyawaroh Pengurus KBM (Ubah)')
            ->call('simpan');

        $this->assertDatabaseHas('jenis_musyawaroh', ['id' => $baris->id, 'nama' => 'Musyawaroh Pengurus KBM (Ubah)']);
    }

    public function test_tidak_bisa_hapus_jenis_musyawaroh_yang_masih_dipakai(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $baris = JenisMusyawaroh::where('tingkat', 'DESA')->where('nama', 'Musyawarah PPD-PPK')->firstOrFail();

        Musyawaroh::create([
            'tingkat' => 'DESA',
            'penyelenggara_type' => 'desa',
            'penyelenggara_id' => Desa::factory()->create()->id,
            'jenis_musyawaroh_id' => $baris->id,
            'tanggal' => now()->toDateString(),
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaJenisMusyawaroh::class)
            ->call('hapus', $baris->id);

        $this->assertDatabaseHas('jenis_musyawaroh', ['id' => $baris->id]);
    }
}
