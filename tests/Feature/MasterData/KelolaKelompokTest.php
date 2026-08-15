<?php

namespace Tests\Feature\MasterData;

use App\Livewire\MasterData\KelolaKelompok;
use App\Models\Desa;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelompok;
use App\Models\Pendidik;
use App\Models\RombonganBelajar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaKelompokTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /** Kelas per Jenjang dibuat otomatis saat Kelompok baru dibuat — admin tidak perlu input manual. */
    public function test_kelompok_baru_otomatis_dapat_kelas_per_jenjang(): void
    {
        $desa = Desa::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->set('desa_id', $desa->id)
            ->set('nama', 'Kelompok Baru')
            ->call('simpan');

        $kelompok = Kelompok::where('nama', 'Kelompok Baru')->firstOrFail();

        $this->assertSame(Jenjang::count(), $kelompok->kelas()->count());
        $this->assertTrue($kelompok->kelas()->where('status_aktif', true)->exists());

        // RombonganBelajar (Kelas sungguhan) juga auto-provisioned 1 per Jenjang, sebagai
        // starting point yang bisa digabung admin belakangan — masing-masing berisi tepat 1 Jenjang.
        $rombonganBelajar = $kelompok->rombonganBelajar()->with('jenjangs')->get();
        $this->assertSame(Jenjang::count(), $rombonganBelajar->count());
        $this->assertTrue($rombonganBelajar->every(fn ($r) => $r->jenjangs->count() === 1));
        $this->assertSame(
            Jenjang::orderBy('urutan')->pluck('label')->sort()->values()->all(),
            $rombonganBelajar->pluck('nama')->sort()->values()->all()
        );
    }

    /** Edit Kelompok yang sudah ada tidak menduplikasi/mengubah baris Kelas yang sudah ada. */
    public function test_edit_kelompok_tidak_membuat_ulang_kelas(): void
    {
        $desa = Desa::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->set('desa_id', $desa->id)
            ->set('nama', 'Kelompok Awal')
            ->call('simpan');

        $kelompok = Kelompok::where('nama', 'Kelompok Awal')->firstOrFail();
        $jumlahKelasAwal = $kelompok->kelas()->count();

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->call('openEdit', $kelompok->id)
            ->set('nama', 'Kelompok Diubah')
            ->call('simpan');

        $this->assertSame($jumlahKelasAwal, $kelompok->kelas()->count());
    }

    /**
     * Kelas SELALU ada (auto-provision per Jenjang) sejak Kelompok dibuat — jadi
     * kehadiran Kelas tidak lagi boleh memblokir hapus Kelompok kosong (regresi
     * yang diperbaiki: guard lama `kelas_count > 0` bikin SEMUA Kelompok permanen
     * tidak bisa dihapus). Kelas ikut soft-delete saat Kelompok dihapus.
     */
    public function test_hapus_kelompok_kosong_berhasil_dan_ikut_hapus_kelasnya(): void
    {
        $desa = Desa::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->set('desa_id', $desa->id)
            ->set('nama', 'Kelompok Kosong')
            ->call('simpan');

        $kelompok = Kelompok::where('nama', 'Kelompok Kosong')->firstOrFail();
        $this->assertGreaterThan(0, $kelompok->kelas()->count());
        $this->assertGreaterThan(0, $kelompok->rombonganBelajar()->count());

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->call('hapus', $kelompok->id);

        $this->assertSoftDeleted('kelompok', ['id' => $kelompok->id]);
        $this->assertSame(0, $kelompok->kelas()->count());
        $this->assertSame(0, $kelompok->rombonganBelajar()->count());
    }

    public function test_hapus_kelompok_diblok_jika_masih_ada_pendidik(): void
    {
        $desa = Desa::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->set('desa_id', $desa->id)
            ->set('nama', 'Kelompok Ada Pendidik')
            ->call('simpan');

        $kelompok = Kelompok::where('nama', 'Kelompok Ada Pendidik')->firstOrFail();
        Pendidik::create(['kelompok_id' => $kelompok->id, 'nama' => 'Ustadz Tes', 'jenis' => 'MT']);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->call('hapus', $kelompok->id);

        $this->assertDatabaseHas('kelompok', ['id' => $kelompok->id, 'deleted_at' => null]);
    }

    public function test_hapus_kelompok_diblok_jika_masih_ada_generus(): void
    {
        $desa = Desa::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->set('desa_id', $desa->id)
            ->set('nama', 'Kelompok Ada Generus')
            ->call('simpan');

        $kelompok = Kelompok::where('nama', 'Kelompok Ada Generus')->firstOrFail();
        $kelas = $kelompok->kelas()->first();
        Generus::factory()->create(['kelas_id' => $kelas->id]);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelompok::class)
            ->call('hapus', $kelompok->id);

        $this->assertDatabaseHas('kelompok', ['id' => $kelompok->id, 'deleted_at' => null]);
    }
}
