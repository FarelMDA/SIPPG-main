<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\InputPresensiKegiatan;
use App\Models\Generus;
use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\KurikulumKalender;
use App\Models\Pendidik;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Carve-out otorisasi Guru + realisasi materi untuk Kegiatan KBM (kurikulum_kalender_id
 * tidak null) — docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5. Berbeda dari
 * Kegiatan Tambahan biasa yang sengaja melarang Guru (lihat InputPresensiKegiatanTest).
 */
class InputPresensiKegiatanKbmTest extends TestCase
{
    use RefreshDatabase;

    private function kegiatanKbmDenganGuru(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $pendidik = Pendidik::create(['kelompok_id' => $kelompok->id, 'nama' => 'Ustadzah A', 'jenis' => 'MT']);
        $kelas->pendidik()->attach($pendidik->id);

        $guru = User::factory()->create(['kelompok_id' => $kelompok->id, 'pendidik_id' => $pendidik->id]);
        $guru->assignRole('guru');

        $admin = User::factory()->create();
        $kurikulum = KurikulumKalender::create([
            'jenjang' => $kelas->jenjang->kode,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'jenis' => 'MATERI',
            'item_materi' => ['Tilawati 1 halaman 1'],
            'dibuat_oleh' => $admin->id,
        ]);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Kelas '.$kelas->nama,
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => JenisKegiatan::where('nama', 'KBM Reguler')->value('id'),
            'tanggal' => now()->toDateString(),
            'target_tipe' => 'JENJANG_KELAS',
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $admin->id,
            'kurikulum_kalender_id' => $kurikulum->id,
            'materi' => $kurikulum->item_materi,
        ]);
        $kegiatan->targetKelas()->attach($kelas->id);

        Generus::factory()->create(['kelas_id' => $kelas->id]);

        return [$guru, $kegiatan, $kelompok, $kelas];
    }

    public function test_guru_pengajar_berwenang_input_presensi_kbm_kelasnya(): void
    {
        [$guru, $kegiatan, $kelompok] = $this->kegiatanKbmDenganGuru();

        Livewire::actingAs($guru, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->assertSet('berwenang', true);
    }

    public function test_realisasi_tidak_terlaksana_wajib_catatan(): void
    {
        [$guru, $kegiatan, $kelompok] = $this->kegiatanKbmDenganGuru();

        Livewire::actingAs($guru, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->set('realisasiStatus', 'TIDAK_TERLAKSANA')
            ->call('simpan')
            ->assertHasErrors('realisasiCatatan');

        $this->assertNull($kegiatan->fresh()->realisasi_status);
    }

    public function test_simpan_realisasi_dan_presensi_berhasil(): void
    {
        [$guru, $kegiatan, $kelompok, $kelas] = $this->kegiatanKbmDenganGuru();
        $generus = Generus::where('kelas_id', $kelas->id)->first();

        Livewire::actingAs($guru, 'web')
            ->test(InputPresensiKegiatan::class, ['kegiatan' => $kegiatan, 'kelompok' => $kelompok])
            ->set('realisasiStatus', 'SESUAI_JADWAL')
            ->set("daftar.{$generus->id}", 'HADIR')
            ->call('simpan');

        $this->assertSame('SESUAI_JADWAL', $kegiatan->fresh()->realisasi_status);
        $this->assertDatabaseHas('kegiatan_peserta', [
            'kegiatan_id' => $kegiatan->id,
            'generus_id' => $generus->id,
            'status_presensi' => 'HADIR',
            'kelas_id' => $kelas->id,
        ]);
    }
}
