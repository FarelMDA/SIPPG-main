<?php

namespace Tests\Feature\Kurikulum;

use App\Livewire\Kurikulum\RekapKbmLintasKelompok;
use App\Models\Desa;
use App\Models\Generus;
use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\KurikulumKalender;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rollup otomatis Kelompok→Desa untuk Kegiatan hasil-Kurikulum (jenjang +
 * kurikulum_kalender_id yang sama) — docs/Visi-Konvergensi-Kurikulum-Kegiatan-
 * Presensi.md §5.
 */
class RekapKbmLintasKelompokTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekap_menggabungkan_kelas_dari_beberapa_kelompok_di_desa_yang_sama(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $desa = Desa::factory()->create();
        $kelompokA = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $kelompokB = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $kelasA = Kelas::factory()->create(['kelompok_id' => $kelompokA->id]);
        $kelasB = Kelas::factory()->create(['kelompok_id' => $kelompokB->id, 'jenjang_id' => $kelasA->jenjang_id]);

        $kurikulum = KurikulumKalender::create([
            'jenjang' => $kelasA->jenjang->kode,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'jenis' => 'MATERI',
            'item_materi' => ['Materi 1'],
            'dibuat_oleh' => $admin->id,
        ]);

        $buatKegiatan = function (Kelas $kelas, Kelompok $kelompok) use ($kurikulum, $admin) {
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
                'realisasi_status' => 'SESUAI_JADWAL',
            ]);
            $kegiatan->targetKelas()->attach($kelas->id);

            $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
            KegiatanPeserta::create([
                'kegiatan_id' => $kegiatan->id,
                'generus_id' => $generus->id,
                'kelompok_id' => $kelompok->id,
                'kelas_id' => $kelas->id,
                'status_presensi' => 'HADIR',
            ]);

            return $kegiatan;
        };

        $buatKegiatan($kelasA, $kelompokA);
        $buatKegiatan($kelasB, $kelompokB);

        Livewire::actingAs($admin, 'web')
            ->test(RekapKbmLintasKelompok::class)
            ->set('filterJenjang', $kelasA->jenjang->kode)
            ->assertSee($kelompokA->nama)
            ->assertSee($kelompokB->nama)
            ->assertSee($kelasA->nama)
            ->assertSee($kelasB->nama);
    }

    /**
     * SRS-Fase-1 §4.2 — hanya Admin Daerah dan PJP Desa yang boleh lintas-Kelompok;
     * PJP Kelompok dibatasi ke Kelompoknya sendiri meski dua Kelompok berada di Desa
     * yang sama (sebelumnya bocor — lihat riwayat perbaikan).
     */
    public function test_pjp_kelompok_hanya_melihat_kelompoknya_sendiri_walau_satu_desa(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $desa = Desa::factory()->create();
        $kelompokA = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $kelompokB = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $kelasA = Kelas::factory()->create(['kelompok_id' => $kelompokA->id]);
        $kelasB = Kelas::factory()->create(['kelompok_id' => $kelompokB->id, 'jenjang_id' => $kelasA->jenjang_id]);

        $kurikulum = KurikulumKalender::create([
            'jenjang' => $kelasA->jenjang->kode,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'jenis' => 'MATERI',
            'item_materi' => ['Materi 1'],
            'dibuat_oleh' => $admin->id,
        ]);

        $buatKegiatan = function (Kelas $kelas, Kelompok $kelompok) use ($kurikulum, $admin) {
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
                'realisasi_status' => 'SESUAI_JADWAL',
            ]);
            $kegiatan->targetKelas()->attach($kelas->id);

            $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
            KegiatanPeserta::create([
                'kegiatan_id' => $kegiatan->id,
                'generus_id' => $generus->id,
                'kelompok_id' => $kelompok->id,
                'kelas_id' => $kelas->id,
                'status_presensi' => 'HADIR',
            ]);
        };

        $buatKegiatan($kelasA, $kelompokA);
        $buatKegiatan($kelasB, $kelompokB);

        $pjk = User::factory()->create(['kelompok_id' => $kelompokA->id]);
        $pjk->assignRole('pjp-kelompok');

        // kelasA & kelasB berbagi jenjang (dan karenanya nama) yang sama secara sengaja
        // (dikelompokkan bareng, sama seperti test admin di atas) — jadi pembeda scope
        // yang benar untuk diuji adalah nama Kelompok-nya, bukan nama Kelas.
        Livewire::actingAs($pjk, 'web')
            ->test(RekapKbmLintasKelompok::class)
            ->set('filterJenjang', $kelasA->jenjang->kode)
            ->assertSee($kelompokA->nama)
            ->assertDontSee($kelompokB->nama);
    }
}
