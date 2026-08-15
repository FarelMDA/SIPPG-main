<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\FormJadwalKegiatan;
use App\Models\HariLibur;
use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\KurikulumKalender;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rutin dari Kurikulum (frekuensi_tipe=KURIKULUM) — KBM Reguler sebagai Kegiatan,
 * digenerate dari breakdown Kurikulum per Kelas. docs/Visi-Konvergensi-Kurikulum-
 * Kegiatan-Presensi.md §5.
 */
class FormJadwalKegiatanKurikulumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function pjkDenganKelasKbm(): array
    {
        $this->travelTo(Carbon::parse('2026-08-01'));

        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        return [$pjk, $kelompok, $kelas];
    }

    public function test_generate_kbm_dari_kurikulum_mengisi_materi_dan_melewati_libur(): void
    {
        [$pjk, , $kelas] = $this->pjkDenganKelasKbm();
        $user = User::factory()->create();

        // Senin 3, Selasa 4, Rabu 5 Agustus 2026 — Selasa jadi libur (harus dilewati).
        KurikulumKalender::create([
            'jenjang' => $kelas->jenjang->kode,
            'tanggal_mulai' => '2026-08-03',
            'tanggal_selesai' => '2026-08-05',
            'jenis' => 'MATERI',
            'item_materi' => ['Tilawati 1 halaman 1'],
            'dibuat_oleh' => $user->id,
        ]);

        HariLibur::factory()->create([
            'tanggal_mulai' => '2026-08-04',
            'tanggal_selesai' => '2026-08-04',
        ]);

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Kegiatan Kelas ACR A')
            ->set('jenisKegiatanId', JenisKegiatan::where('nama', 'KBM Reguler')->value('id'))
            ->set('frekuensiTipe', 'KURIKULUM')
            ->set('targetTipe', 'JENJANG_KELAS')
            ->set('kelasTerpilih', [$kelas->id])
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-08-31')
            ->call('hitungPratinjau')
            ->call('simpan')
            ->assertRedirect(route('kegiatan.jadwal.index'));

        $senin = Kegiatan::whereDate('tanggal', '2026-08-03')->first();
        $rabu = Kegiatan::whereDate('tanggal', '2026-08-05')->first();

        $this->assertNotNull($senin);
        $this->assertNotNull($rabu);
        $this->assertSame(['Tilawati 1 halaman 1'], $senin->materi);
        $this->assertSame('SESUAI_JADWAL', $senin->realisasi_status);
        $this->assertFalse(Kegiatan::whereDate('tanggal', '2026-08-04')->exists());
    }

    public function test_kurikulum_wajib_target_tepat_satu_kelas(): void
    {
        [$pjk, , $kelasA] = $this->pjkDenganKelasKbm();
        $kelasB = Kelas::factory()->create(['kelompok_id' => $kelasA->kelompok_id]);

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Kegiatan Dua Kelas')
            ->set('jenisKegiatanId', JenisKegiatan::where('nama', 'KBM Reguler')->value('id'))
            ->set('frekuensiTipe', 'KURIKULUM')
            ->set('targetTipe', 'JENJANG_KELAS')
            ->set('kelasTerpilih', [$kelasA->id, $kelasB->id])
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-08-31')
            ->call('simpan')
            ->assertHasErrors('frekuensiTipe');

        $this->assertDatabaseCount('kegiatan_jadwal', 0);
    }
}
