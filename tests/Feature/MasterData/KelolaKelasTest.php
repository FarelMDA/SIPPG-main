<?php

namespace Tests\Feature\MasterData;

use App\Livewire\MasterData\KelolaKelas;
use App\Models\Desa;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\RombonganBelajar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaKelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /** Satu Kelas (RombonganBelajar) bisa menggabungkan >1 Jenjang sekaligus. */
    public function test_admin_daerah_bisa_gabungkan_beberapa_jenjang_jadi_satu_kelas(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $dasar2 = Jenjang::where('kode', 'DASAR_2')->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelas::class)
            ->call('openCreate')
            ->set('kelompok_id', $kelompok->id)
            ->set('nama', 'Kelas ACR A')
            ->set('jenjangIds', [$dasar1->id, $dasar2->id])
            ->call('simpan')
            ->assertHasNoErrors();

        $rombel = RombonganBelajar::where('nama', 'Kelas ACR A')->firstOrFail();
        $this->assertSame($kelompok->id, $rombel->kelompok_id);
        $this->assertSame([$dasar1->id, $dasar2->id], $rombel->jenjangs->pluck('id')->sort()->values()->all());
    }

    public function test_edit_kelas_mengganti_gabungan_jenjang(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $dasar2 = Jenjang::where('kode', 'DASAR_2')->firstOrFail();
        $dasar3 = Jenjang::where('kode', 'DASAR_3')->firstOrFail();

        $rombel = RombonganBelajar::factory()->create(['kelompok_id' => $kelompok->id, 'nama' => 'Kelas Lama']);
        $rombel->jenjangs()->attach($dasar1->id);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelas::class)
            ->call('openEdit', $rombel->id)
            ->set('nama', 'Kelas Baru')
            ->set('jenjangIds', [$dasar2->id, $dasar3->id])
            ->call('simpan')
            ->assertHasNoErrors();

        $rombel->refresh();
        $this->assertSame('Kelas Baru', $rombel->nama);
        $this->assertSame([$dasar2->id, $dasar3->id], $rombel->jenjangs->pluck('id')->sort()->values()->all());
    }

    public function test_hapus_kelas_diblok_jika_masih_ada_generus(): void
    {
        $kelompok = Kelompok::factory()->create();
        $jenjang = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id, 'jenjang_id' => $jenjang->id]);

        $rombel = RombonganBelajar::factory()->create(['kelompok_id' => $kelompok->id]);
        $rombel->jenjangs()->attach($jenjang->id);

        Generus::factory()->create(['kelas_id' => $kelas->id, 'jenjang_id' => $jenjang->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelas::class)
            ->call('hapus', $rombel->id);

        $this->assertDatabaseHas('rombongan_belajar', ['id' => $rombel->id, 'deleted_at' => null]);
    }

    public function test_hapus_kelas_kosong_berhasil(): void
    {
        $kelompok = Kelompok::factory()->create();
        $jenjang = Jenjang::where('kode', 'DASAR_1')->firstOrFail();

        $rombel = RombonganBelajar::factory()->create(['kelompok_id' => $kelompok->id]);
        $rombel->jenjangs()->attach($jenjang->id);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelas::class)
            ->call('hapus', $rombel->id);

        $this->assertSoftDeleted('rombongan_belajar', ['id' => $rombel->id]);
    }

    /** pjp-desa (struktur-organisasi.view) tetap bisa lihat Kelas di Desa-nya, bukan Desa lain. */
    public function test_pjp_desa_tidak_melihat_kelas_desa_lain(): void
    {
        $desaSendiri = Desa::factory()->create();
        $kelompokSendiri = Kelompok::factory()->create(['desa_id' => $desaSendiri->id]);
        $rombelSendiri = RombonganBelajar::factory()->create(['kelompok_id' => $kelompokSendiri->id]);

        $desaLain = Desa::factory()->create();
        $kelompokLain = Kelompok::factory()->create(['desa_id' => $desaLain->id]);
        $rombelLain = RombonganBelajar::factory()->create(['kelompok_id' => $kelompokLain->id]);

        $pjd = User::factory()->create(['kelompok_id' => null, 'desa_id' => $desaSendiri->id]);
        $pjd->assignRole('pjp-desa');

        $daftar = Livewire::actingAs($pjd, 'web')
            ->test(KelolaKelas::class)
            ->instance()->rombonganBelajarList;

        $ids = $daftar->pluck('id');
        $this->assertTrue($ids->contains($rombelSendiri->id));
        $this->assertFalse($ids->contains($rombelLain->id));
    }

    public function test_pjp_kelompok_bisa_kelola_kelas_di_kelompoknya_sendiri(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();

        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaKelas::class)
            ->call('openCreate')
            ->set('nama', 'Kelas Baru')
            ->set('jenjangIds', [$dasar1->id])
            ->call('simpan')
            ->assertHasNoErrors();

        $rombel = RombonganBelajar::where('nama', 'Kelas Baru')->firstOrFail();
        $this->assertSame($kelompok->id, $rombel->kelompok_id);
    }

    /** kelompok_id adalah wire:model — pastikan tidak bisa dipaksa ke kelompok lain lewat request. */
    public function test_pjp_kelompok_tidak_bisa_memindahkan_kelas_ke_kelompok_lain(): void
    {
        $kelompokSendiri = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();

        $pjk = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaKelas::class)
            ->call('openCreate')
            ->set('kelompok_id', $kelompokLain->id)
            ->set('nama', 'Kelas Selundup')
            ->set('jenjangIds', [$dasar1->id])
            ->call('simpan')
            ->assertHasNoErrors();

        $rombel = RombonganBelajar::where('nama', 'Kelas Selundup')->firstOrFail();
        $this->assertSame($kelompokSendiri->id, $rombel->kelompok_id);
    }

    /** openEdit tidak boleh memuat Kelas milik kelompok lain meski ID ditebak/dipaksa. */
    public function test_pjp_kelompok_tidak_bisa_edit_kelas_kelompok_lain(): void
    {
        $kelompokLain = Kelompok::factory()->create();
        $rombelLain = RombonganBelajar::factory()->create(['kelompok_id' => $kelompokLain->id]);

        $pjk = User::factory()->create(['kelompok_id' => Kelompok::factory()->create()->id]);
        $pjk->assignRole('pjp-kelompok');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaKelas::class)
            ->call('openEdit', $rombelLain->id);
    }

    /**
     * Reproduksi kasus: Kelompok baru auto-provision 1 Rombel per Jenjang. Setelah salah satu
     * Jenjang digabung ke Rombel lain, Rombel lama (asal Jenjang itu) harus otomatis lepas
     * jenjangnya — supaya tidak nyangkut & tidak diblokir guard Generus saat mau dihapus.
     */
    public function test_gabung_jenjang_ke_kelas_lain_melepas_jenjang_dari_kelas_asal(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $dasar2 = Jenjang::where('kode', 'DASAR_2')->firstOrFail();

        $rombelDasar1 = RombonganBelajar::factory()->create(['kelompok_id' => $kelompok->id, 'nama' => 'Dasar 1']);
        $rombelDasar1->jenjangs()->attach($dasar1->id);

        $rombelDasar2 = RombonganBelajar::factory()->create(['kelompok_id' => $kelompok->id, 'nama' => 'Dasar 2']);
        $rombelDasar2->jenjangs()->attach($dasar2->id);

        $kelasDasar2 = Kelas::factory()->create(['kelompok_id' => $kelompok->id, 'jenjang_id' => $dasar2->id]);
        Generus::factory()->create(['kelas_id' => $kelasDasar2->id, 'jenjang_id' => $dasar2->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        // Gabungkan Dasar 2 ke Rombel "Dasar 1".
        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelas::class)
            ->call('openEdit', $rombelDasar1->id)
            ->set('jenjangIds', [$dasar1->id, $dasar2->id])
            ->call('simpan')
            ->assertHasNoErrors();

        $rombelDasar2->refresh();
        $this->assertSame([], $rombelDasar2->jenjangs->pluck('id')->all());

        // Rombel "Dasar 2" lama sekarang kosong jenjangnya — guard Generus tidak lagi memblokir.
        Livewire::actingAs($admin, 'web')
            ->test(KelolaKelas::class)
            ->call('hapus', $rombelDasar2->id);

        $this->assertSoftDeleted('rombongan_belajar', ['id' => $rombelDasar2->id]);
    }

    /** filterKelompok datang dari query string (#[Url]) — nilai di luar scope harus dibuang, bukan menghasilkan daftar kosong yang menyesatkan. */
    public function test_filter_kelompok_di_luar_scope_pjp_kelompok_direset(): void
    {
        $kelompokSendiri = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();
        RombonganBelajar::factory()->create(['kelompok_id' => $kelompokSendiri->id, 'nama' => 'Rombel Sendiri']);

        $pjk = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $pjk->assignRole('pjp-kelompok');

        $component = Livewire::actingAs($pjk, 'web')
            ->test(KelolaKelas::class, ['filterKelompok' => (string) $kelompokLain->id]);

        $this->assertSame('', $component->get('filterKelompok'));
        $this->assertSame(1, $component->instance()->rombonganBelajarList->count());
    }
}
