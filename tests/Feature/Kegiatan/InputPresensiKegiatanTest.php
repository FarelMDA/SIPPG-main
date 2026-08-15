<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\InputPresensiKegiatan;
use App\Models\Daerah;
use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanPetugasPresensi;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class InputPresensiKegiatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_akses_token_kedaluwarsa_menampilkan_halaman_error(): void
    {
        $daerah = Daerah::factory()->create();
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        Generus::factory()->create(['kelas_id' => $kelas->id]);
        $generusPetugas = Generus::factory()->create(['kelas_id' => $kelas->id]);
        $user = User::factory()->create();

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Daerah',
            'tingkat' => 'DAERAH',
            'penyelenggara_type' => 'daerah',
            'penyelenggara_id' => $daerah->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->subDays(5)->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $user->id,
        ]);

        $petugas = KegiatanPetugasPresensi::create([
            'kegiatan_id' => $kegiatan->id,
            'kelompok_id' => $kelompok->id,
            'generus_id' => $generusPetugas->id,
            'token' => (string) Str::uuid(),
            'token_kedaluwarsa' => now()->subDays(4), // sudah lewat
            'ditugaskan_oleh' => $user->id,
        ]);

        $this->get('/kegiatan/presensi/'.$petugas->token)
            ->assertOk()
            ->assertSee('Tautan ini sudah tidak berlaku');
    }

    public function test_token_valid_bisa_membuka_form_presensi(): void
    {
        $daerah = Daerah::factory()->create();
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        Generus::factory()->create(['kelas_id' => $kelas->id]);
        $generusPetugas = Generus::factory()->create(['kelas_id' => $kelas->id]);
        $user = User::factory()->create();

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Daerah',
            'tingkat' => 'DAERAH',
            'penyelenggara_type' => 'daerah',
            'penyelenggara_id' => $daerah->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $user->id,
        ]);

        $petugas = KegiatanPetugasPresensi::create([
            'kegiatan_id' => $kegiatan->id,
            'kelompok_id' => $kelompok->id,
            'generus_id' => $generusPetugas->id,
            'token' => (string) Str::uuid(),
            'token_kedaluwarsa' => now()->addDay(),
            'ditugaskan_oleh' => $user->id,
        ]);

        $this->get('/kegiatan/presensi/'.$petugas->token)
            ->assertOk()
            ->assertSee($kegiatan->nama);
    }

    /**
     * UC-23 — Kegiatan tingkat KELOMPOK butuh permission `kegiatan.manage` biasa
     * (PJP Kelompok/Sekretaris KBM), bukan cuma keanggotaan kelompok_id — Guru di
     * kelompok yang sama tidak berwenang mencatat presensi Kegiatan.
     */
    public function test_guru_tidak_berwenang_input_presensi_kegiatan_tingkat_kelompok(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $kelompok = Kelompok::factory()->create();
        $guru = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $guru->assignRole('guru');

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Kelompok',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $guru->id,
        ]);

        Livewire::actingAs($guru, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->assertSet('berwenang', false);
    }

    public function test_pjp_kelompok_berwenang_input_presensi_kegiatan_tingkat_kelompok(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Kelompok',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $pjk->id,
        ]);

        Livewire::actingAs($pjk, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->assertSet('berwenang', true);
    }

    /** SRS-Fase-2 §2.4 — target_tipe=JENJANG_KELAS mempersempit grid presensi ke Kelas yang ditarget saja. */
    public function test_grid_presensi_mengikuti_target_jenjang_kelas(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $kelasTertarget = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $kelasLain = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $generusTertarget = Generus::factory()->create(['kelas_id' => $kelasTertarget->id]);
        $generusLain = Generus::factory()->create(['kelas_id' => $kelasLain->id]);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Bertarget', 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok', 'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'), 'tanggal' => now()->toDateString(), 'status' => 'TERJADWAL',
            'target_tipe' => 'JENJANG_KELAS', 'dibuat_oleh' => $pjk->id,
        ]);
        $kegiatan->targetKelas()->sync([$kelasTertarget->id]);

        $daftar = Livewire::actingAs($pjk, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->get('daftar');

        $this->assertArrayHasKey($generusTertarget->id, $daftar);
        $this->assertArrayNotHasKey($generusLain->id, $daftar);
    }

    /** SRS-Fase-2 §2.4 — target_tipe=INDIVIDU mempersempit grid presensi ke Generus spesifik saja. */
    public function test_grid_presensi_mengikuti_target_individu(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $generusTertarget = Generus::factory()->create(['kelas_id' => $kelas->id]);
        $generusLain = Generus::factory()->create(['kelas_id' => $kelas->id]);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Individu', 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok', 'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'), 'tanggal' => now()->toDateString(), 'status' => 'TERJADWAL',
            'target_tipe' => 'INDIVIDU', 'dibuat_oleh' => $pjk->id,
        ]);
        $kegiatan->targetIndividu()->sync([$generusTertarget->id]);

        $daftar = Livewire::actingAs($pjk, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->get('daftar');

        $this->assertArrayHasKey($generusTertarget->id, $daftar);
        $this->assertArrayNotHasKey($generusLain->id, $daftar);
    }
}
