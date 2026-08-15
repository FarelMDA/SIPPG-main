<?php

namespace Tests\Feature\MasterData;

use App\Livewire\MasterData\KelolaReferensi;
use App\Models\JenisKelamin;
use App\Models\Jenjang;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaReferensiTest extends TestCase
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
            ->test(KelolaReferensi::class, ['model' => JenisKelamin::class, 'judul' => 'Jenis Kelamin'])
            ->assertForbidden();
    }

    public function test_admin_daerah_bisa_tambah_baris_referensi_tanpa_kategori_usia(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaReferensi::class, ['model' => JenisKelamin::class, 'judul' => 'Jenis Kelamin'])
            ->set('kode', 'LAINNYA')
            ->set('label', 'Lainnya')
            ->set('urutan', 3)
            ->call('simpan');

        $this->assertDatabaseHas('jenis_kelamin', ['kode' => 'LAINNYA', 'label' => 'Lainnya']);
    }

    public function test_admin_daerah_bisa_edit_baris_referensi(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $baris = JenisKelamin::where('kode', 'LAKI')->firstOrFail();

        Livewire::actingAs($admin, 'web')
            ->test(KelolaReferensi::class, ['model' => JenisKelamin::class, 'judul' => 'Jenis Kelamin'])
            ->call('openEdit', $baris->id)
            ->set('label', 'Pria')
            ->call('simpan');

        $this->assertDatabaseHas('jenis_kelamin', ['id' => $baris->id, 'label' => 'Pria']);
    }

    public function test_field_kategori_usia_wajib_untuk_jenjang(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaReferensi::class, ['model' => Jenjang::class, 'judul' => 'Jenjang', 'withKategoriUsia' => true])
            ->set('kode', 'TES_JENJANG')
            ->set('label', 'Tes Jenjang')
            ->set('urutan', 99)
            ->call('simpan')
            ->assertHasErrors(['kategori_usia' => 'required']);
    }
}
