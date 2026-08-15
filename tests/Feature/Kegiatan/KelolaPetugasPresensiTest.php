<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\KelolaPetugasPresensi;
use App\Models\Desa;
use App\Models\Kegiatan;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaPetugasPresensiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_kelompok_tidak_bisa_kelola_petugas_kegiatan_desa_lain(): void
    {
        $desaSendiri = Desa::factory()->create();
        $kelompokSendiri = Kelompok::factory()->create(['desa_id' => $desaSendiri->id]);
        $desaLain = Desa::factory()->create();

        $kegiatanDesaLain = Kegiatan::create([
            'nama' => 'Kegiatan Desa Lain',
            'tingkat' => 'DESA',
            'penyelenggara_type' => 'desa',
            'penyelenggara_id' => $desaLain->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => User::factory()->create()->id,
        ]);

        $pjk = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaPetugasPresensi::class, ['kegiatan' => $kegiatanDesaLain])
            ->assertForbidden();
    }

    public function test_pjp_kelompok_bisa_kelola_petugas_kegiatan_desa_sendiri(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);

        $kegiatanDesaSendiri = Kegiatan::create([
            'nama' => 'Kegiatan Desa Sendiri',
            'tingkat' => 'DESA',
            'penyelenggara_type' => 'desa',
            'penyelenggara_id' => $desa->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => User::factory()->create()->id,
        ]);

        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaPetugasPresensi::class, ['kegiatan' => $kegiatanDesaSendiri])
            ->assertOk();
    }

    /**
     * UC-22 — UCIC tidak menyebut Admin Daerah sebagai aktor utama, tapi
     * RolePermissionSeeder tetap memberi admin-daerah semua permission (override).
     * Sebelum perbaikan, kelompok_id null admin-daerah membuat penunjukan petugas
     * tersimpan dengan kelompok_id=null (orphan). Sekarang admin-daerah wajib
     * memilih salah satu Kelompok dalam cakupan Kegiatan lewat kelompokCakupanId.
     */
    public function test_admin_daerah_wajib_pilih_kelompok_dan_penunjukan_tidak_orphan(): void
    {
        $desa = Desa::factory()->create();
        $kelompok = Kelompok::factory()->create(['desa_id' => $desa->id]);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Desa',
            'tingkat' => 'DESA',
            'penyelenggara_type' => 'desa',
            'penyelenggara_id' => $desa->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => User::factory()->create()->id,
        ]);

        $sekretaris = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $sekretaris->assignRole('sekretaris-kbm');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPetugasPresensi::class, ['kegiatan' => $kegiatan])
            ->assertOk()
            ->assertSet('kelompokCakupanId', $kelompok->id)
            ->set('tipe_petugas', 'AKUN_INTERNAL')
            ->set('user_id', $sekretaris->id)
            ->call('tunjuk');

        $this->assertDatabaseHas('kegiatan_petugas_presensi', [
            'kegiatan_id' => $kegiatan->id,
            'kelompok_id' => $kelompok->id,
            'user_id' => $sekretaris->id,
        ]);
    }
}
