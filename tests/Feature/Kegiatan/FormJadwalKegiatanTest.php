<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\FormJadwalKegiatan;
use App\Models\Generus;
use App\Models\HariLibur;
use App\Models\Kegiatan;
use App\Models\KegiatanJadwal;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use App\Services\Kegiatan\GeneratorKegiatanDariJadwal;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-28/UC-30 — Tambah/Ubah Jadwal Kegiatan Berulang (halaman biasa, bukan modal).
 * SRS-Fase-2 §2.2–§2.3.
 */
class FormJadwalKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /** assertDatabaseHas dengan match string biasa pada kolom `tanggal` tidak aman — kolom
     *  ber-cast 'date' tapi tersimpan sebagai "Y-m-d H:i:s" penuh (pola sama UpsertsByTanggal). */
    private function assertAdaKegiatanTanggal(int $kegiatanJadwalId, string $tanggal): void
    {
        $this->assertTrue(
            Kegiatan::where('kegiatan_jadwal_id', $kegiatanJadwalId)->whereDate('tanggal', $tanggal)->exists(),
            "Tidak ditemukan kegiatan_jadwal_id={$kegiatanJadwalId} pada tanggal {$tanggal}"
        );
    }

    private function assertTidakAdaKegiatanTanggal(int $kegiatanJadwalId, string $tanggal): void
    {
        $this->assertFalse(
            Kegiatan::where('kegiatan_jadwal_id', $kegiatanJadwalId)->whereDate('tanggal', $tanggal)->exists(),
            "Tidak seharusnya ada kegiatan_jadwal_id={$kegiatanJadwalId} pada tanggal {$tanggal}"
        );
    }

    private function pjkDenganKelompok(): array
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        return [$pjk, $kelompok];
    }

    public function test_pjp_kelompok_membuat_jadwal_harian_generate_kejadian(): void
    {
        [$pjk, $kelompok] = $this->pjkDenganKelompok();

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Pengajian Rutin')
            ->set('jenisKegiatanId', \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'))
            ->set('frekuensiTipe', 'HARIAN')
            ->set('hariDalamMinggu', ['SABTU'])
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-08-31')
            ->call('hitungPratinjau')
            ->call('simpan')
            ->assertRedirect(route('kegiatan.jadwal.index'));

        $this->assertDatabaseHas('kegiatan_jadwal', [
            'nama' => 'Pengajian Rutin',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
        ]);

        $jadwal = KegiatanJadwal::first();
        // Sabtu Agustus 2026: 1, 8, 15, 22, 29 -> 5 kejadian.
        $this->assertSame(5, Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->count());
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-01');
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-29');
    }

    public function test_pjp_kelompok_tidak_bisa_buat_jadwal_tingkat_lain(): void
    {
        [$pjk, $kelompok] = $this->pjkDenganKelompok();

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Jadwal X')
            ->set('jenisKegiatanId', \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'))
            ->set('hariDalamMinggu', ['SABTU'])
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-08-31')
            ->call('simpan');

        $this->assertDatabaseHas('kegiatan_jadwal', ['nama' => 'Jadwal X', 'tingkat' => 'KELOMPOK']);
        $this->assertDatabaseMissing('kegiatan_jadwal', ['nama' => 'Jadwal X', 'tingkat' => 'DAERAH']);
    }

    public function test_bulanan_wajib_pilih_minggu_ke(): void
    {
        [$pjk] = $this->pjkDenganKelompok();

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Jadwal Bulanan')
            ->set('frekuensiTipe', 'BULANAN')
            ->set('hariDalamMinggu', ['SABTU'])
            ->set('mingguKeDalamBulan', [])
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-12-31')
            ->call('hitungPratinjau')
            ->assertHasErrors('mingguKeDalamBulan');
    }

    public function test_interval_mingguan_wajib_isi_interval(): void
    {
        [$pjk] = $this->pjkDenganKelompok();

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Jadwal Interval')
            ->set('frekuensiTipe', 'MINGGUAN_INTERVAL')
            ->set('hariDalamMinggu', ['SENIN'])
            ->set('intervalMinggu', null)
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-12-31')
            ->call('hitungPratinjau')
            ->assertHasErrors('intervalMinggu');
    }

    public function test_penolakan_lebih_dari_370_kejadian_tidak_menyimpan_apa_pun(): void
    {
        [$pjk] = $this->pjkDenganKelompok();

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Jadwal Terlalu Panjang')
            ->set('frekuensiTipe', 'HARIAN')
            ->set('hariDalamMinggu', ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'MINGGU'])
            ->set('tanggalMulai', '2025-01-01')
            ->set('tanggalSelesai', '2027-06-30')
            ->call('simpan');

        $this->assertDatabaseCount('kegiatan_jadwal', 0);
        $this->assertDatabaseCount('kegiatan', 0);
    }

    public function test_perbarui_jadwal_tidak_menyentuh_event_lampau_atau_sudah_ada_presensi(): void
    {
        $this->travelTo(Carbon::parse('2026-08-10'));

        [$pjk, $kelompok] = $this->pjkDenganKelompok();

        $jadwal = KegiatanJadwal::factory()->create([
            'penyelenggara_id' => $kelompok->id,
            'dibuat_oleh' => $pjk->id,
            'hari_dalam_minggu' => ['SABTU'],
            'frekuensi_tipe' => 'HARIAN',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);

        app(GeneratorKegiatanDariJadwal::class)->generate($jadwal);

        $eventLampau = Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-08-01')->firstOrFail();
        $eventDenganPresensi = Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-08-15')->firstOrFail();

        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
        KegiatanPeserta::create([
            'kegiatan_id' => $eventDenganPresensi->id,
            'generus_id' => $generus->id,
            'kelompok_id' => $kelompok->id,
            'status_presensi' => 'HADIR',
        ]);

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class, ['kegiatanJadwal' => $jadwal])
            ->set('hariDalamMinggu', ['MINGGU'])
            ->call('simpan')
            ->assertRedirect(route('kegiatan.jadwal.index'));

        // Lampau & sudah-ada-presensi tidak pernah tersentuh.
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-01');
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-15');
        $this->assertSame($eventLampau->id, Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-08-01')->value('id'));
        $this->assertSame($eventDenganPresensi->id, Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-08-15')->value('id'));

        // Mendatang & belum ada presensi (pola lama, 22 & 29 Agustus) sudah diganti.
        $this->assertTidakAdaKegiatanTanggal($jadwal->id, '2026-08-22');
        $this->assertTidakAdaKegiatanTanggal($jadwal->id, '2026-08-29');

        // Pola baru (Minggu) sejak hari ini (10 Agustus): 16, 23, 30 Agustus 2026.
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-16');
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-23');
        $this->assertAdaKegiatanTanggal($jadwal->id, '2026-08-30');
    }

    public function test_pjp_kelompok_tidak_bisa_ubah_jadwal_kelompok_lain(): void
    {
        $kelompokLain = Kelompok::factory()->create();
        $pembuat = User::factory()->create();

        $jadwal = KegiatanJadwal::factory()->create([
            'penyelenggara_id' => $kelompokLain->id,
            'dibuat_oleh' => $pembuat->id,
        ]);

        [$pjk] = $this->pjkDenganKelompok();

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class, ['kegiatanJadwal' => $jadwal])
            ->assertForbidden();
    }

    public function test_rotasi_tempat_terindeks_benar_termasuk_saat_ada_hari_libur(): void
    {
        [$pjk] = $this->pjkDenganKelompok();

        HariLibur::factory()->create([
            'tanggal_mulai' => '2026-08-08',
            'tanggal_selesai' => '2026-08-08',
        ]);

        Livewire::actingAs($pjk, 'web')
            ->test(FormJadwalKegiatan::class)
            ->set('nama', 'Rotasi Test')
            ->set('jenisKegiatanId', \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'))
            ->set('frekuensiTipe', 'HARIAN')
            ->set('hariDalamMinggu', ['SABTU'])
            ->set('tanggalMulai', '2026-08-01')
            ->set('tanggalSelesai', '2026-08-31')
            ->set('tipeTempat', 'ROTASI')
            ->set('rotasiTempat', ['Tempat A', 'Tempat B'])
            ->call('simpan')
            ->assertRedirect(route('kegiatan.jadwal.index'));

        $jadwal = KegiatanJadwal::firstOrFail();
        $events = Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->orderBy('tanggal')->get();

        // Sabtu Agustus 2026: 1, 8, 15, 22, 29 — tapi 8 Agustus Hari Libur, jadi 4 kejadian: 1, 15, 22, 29.
        $this->assertSame(['2026-08-01', '2026-08-15', '2026-08-22', '2026-08-29'], $events->map(fn ($k) => $k->tanggal->toDateString())->all());
        $this->assertSame(['Tempat A', 'Tempat B', 'Tempat A', 'Tempat B'], $events->pluck('tempat')->all());
    }
}
