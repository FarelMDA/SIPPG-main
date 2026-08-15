<?php

namespace Tests\Feature\Kurikulum;

use App\Livewire\Kurikulum\KelolaKurikulum;
use App\Models\Jenjang;
use App\Models\KurikulumKalender;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kelola Kurikulum — breakdown materi per jenjang berbasis rentang tanggal literal,
 * menggantikan ImporKalender/hari_ke lama. docs/Visi-Konvergensi-Kurikulum-Kegiatan-
 * Presensi.md §5.
 */
class KelolaKurikulumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_daerah_bisa_menambah_materi_kurikulum(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $jenjang = Jenjang::first();

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKurikulum::class)
            ->set('filterJenjang', $jenjang->kode)
            ->call('pilihTanggal', '2026-08-03')
            ->set('tanggalSelesai', '2026-08-05')
            ->set('jenis', 'MATERI')
            ->set('itemMateriText', "Tilawati 1 halaman 1\nHafalan An-Nas")
            ->call('simpan');

        $this->assertDatabaseHas('kurikulum_kalender', [
            'jenjang' => $jenjang->kode,
            'jenis' => 'MATERI',
        ]);

        $baris = KurikulumKalender::where('jenjang', $jenjang->kode)->first();
        $this->assertSame(['Tilawati 1 halaman 1', 'Hafalan An-Nas'], $baris->item_materi);
    }

    public function test_selain_kurikulum_manage_tidak_bisa_menambah(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');
        $jenjang = Jenjang::first();

        Livewire::actingAs($guru, 'web')
            ->test(KelolaKurikulum::class)
            ->set('jenjang', $jenjang->kode)
            ->set('tanggalMulai', '2026-08-03')
            ->set('tanggalSelesai', '2026-08-05')
            ->set('itemMateriText', 'Materi')
            ->call('simpan')
            ->assertForbidden();
    }

    public function test_rentang_tumpang_tindih_ditolak(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $jenjang = Jenjang::first();

        KurikulumKalender::create([
            'jenjang' => $jenjang->kode,
            'tanggal_mulai' => '2026-08-03',
            'tanggal_selesai' => '2026-08-05',
            'jenis' => 'MATERI',
            'item_materi' => ['Materi 1'],
            'dibuat_oleh' => $admin->id,
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKurikulum::class)
            ->set('jenjang', $jenjang->kode)
            ->set('tanggalMulai', '2026-08-04')
            ->set('tanggalSelesai', '2026-08-06')
            ->set('itemMateriText', 'Materi 2')
            ->call('simpan')
            ->assertHasErrors('tanggalMulai');

        $this->assertSame(1, KurikulumKalender::where('jenjang', $jenjang->kode)->count());
    }

    public function test_klik_tanggal_yang_sudah_ada_entri_membuka_panel_untuk_diubah(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $jenjang = Jenjang::first();

        $baris = KurikulumKalender::create([
            'jenjang' => $jenjang->kode,
            'tanggal_mulai' => '2026-08-03',
            'tanggal_selesai' => '2026-08-05',
            'jenis' => 'MATERI',
            'item_materi' => ['Tilawati 1 halaman 1'],
            'dibuat_oleh' => $admin->id,
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKurikulum::class)
            ->set('filterJenjang', $jenjang->kode)
            ->call('pilihTanggal', '2026-08-04') // hari tengah dari rentang, bukan tanggal_mulai persis
            ->assertSet('editingId', $baris->id)
            ->assertSet('tanggalMulai', '2026-08-03')
            ->assertSet('tanggalSelesai', '2026-08-05')
            ->assertSet('itemMateriText', 'Tilawati 1 halaman 1');
    }

    public function test_klik_tanggal_kosong_menyiapkan_form_baru_untuk_tanggal_itu(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $jenjang = Jenjang::first();

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKurikulum::class)
            ->set('filterJenjang', $jenjang->kode)
            ->call('pilihTanggal', '2026-08-10')
            ->assertSet('editingId', null)
            ->assertSet('tanggalMulai', '2026-08-10')
            ->assertSet('tanggalSelesai', '2026-08-10')
            ->assertSet('itemMateriText', '');
    }
}
