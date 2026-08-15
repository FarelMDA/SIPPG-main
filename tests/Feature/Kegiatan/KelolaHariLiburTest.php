<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\KelolaHariLibur;
use App\Models\HariLibur;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-38 — Kelola Kalender Hari Libur. SRS-Fase-2 §2.6/§2.6.1.
 */
class KelolaHariLiburTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_daerah_bisa_menambah_hari_libur(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->set('nama', 'Libur Idul Fitri')
            ->set('tanggalMulai', '2026-04-20')
            ->set('tanggalSelesai', '2026-04-22')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hari_libur', ['nama' => 'Libur Idul Fitri', 'sumber' => 'MANUAL']);
    }

    public function test_selain_admin_daerah_tidak_bisa_akses(): void
    {
        $pjk = User::factory()->create();
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')->test(KelolaHariLibur::class)->assertForbidden();
    }

    public function test_tanggal_selesai_sebelum_mulai_ditolak(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->set('nama', 'Libur Salah')
            ->set('tanggalMulai', '2026-04-22')
            ->set('tanggalSelesai', '2026-04-20')
            ->call('simpan')
            ->assertHasErrors('tanggalSelesai');
    }

    public function test_hapus_libur_manual_hard_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $libur = HariLibur::factory()->create(['sumber' => 'MANUAL']);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->call('hapus', $libur->id);

        $this->assertDatabaseMissing('hari_libur', ['id' => $libur->id]);
    }

    public function test_hapus_libur_google_soft_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $libur = HariLibur::factory()->create(['sumber' => 'OTOMATIS_GOOGLE', 'google_event_id' => 'abc123']);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->call('hapus', $libur->id);

        $this->assertSoftDeleted('hari_libur', ['id' => $libur->id], deletedAtColumn: 'dihapus_pada');
    }

    public function test_edit_libur_google_menandai_disunting_manual(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $libur = HariLibur::factory()->create([
            'sumber' => 'OTOMATIS_GOOGLE',
            'google_event_id' => 'xyz789',
            'nama' => 'Nama Lama',
            'disunting_manual' => false,
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->call('openEdit', $libur->id)
            ->set('nama', 'Nama Baru')
            ->call('simpan');

        $libur->refresh();
        $this->assertSame('Nama Baru', $libur->nama);
        $this->assertTrue($libur->disunting_manual);
    }
}
