<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\KelolaJadwalKegiatan;
use App\Models\Kegiatan;
use App\Models\KegiatanJadwal;
use App\Models\Kelompok;
use App\Models\User;
use App\Services\Kegiatan\GeneratorKegiatanDariJadwal;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-28 — Daftar Jadwal Kegiatan Berulang (list-only). Tambah/Ubah/Pratinjau ada di
 * FormJadwalKegiatanTest — komponen ini hanya menampilkan daftar & aksi nonaktifkan.
 */
class KelolaJadwalKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_nonaktifkan_tidak_menghapus_kejadian_yang_sudah_ada(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $jadwal = KegiatanJadwal::factory()->create([
            'penyelenggara_id' => $kelompok->id,
            'dibuat_oleh' => $pjk->id,
            'hari_dalam_minggu' => ['SABTU'],
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);
        app(GeneratorKegiatanDariJadwal::class)->generate($jadwal);

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaJadwalKegiatan::class)
            ->call('nonaktifkan', $jadwal->id);

        $this->assertSame('NONAKTIF', $jadwal->fresh()->status);
        $this->assertSame(5, Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->count());
    }
}
