<?php

namespace Tests\Feature\Laporan;

use App\Models\Generus;
use App\Models\JenisKegiatan;
use App\Models\JenisMusyawaroh;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\Musyawaroh;
use App\Models\MusyawarohItem;
use App\Models\ProgramMonitoring;
use App\Models\ProgramMonitoringItem;
use App\Models\KurikulumKalender;
use App\Models\SensusSnapshot;
use App\Models\User;
use App\Services\Laporan\SusunSnapshotLaporan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SusunSnapshotLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function jenisKegiatanTambahan(): int
    {
        return JenisKegiatan::where('nama', 'Tambahan')->value('id');
    }

    private function buatKegiatan(Kelompok $kelompok, string $tanggal, ?int $kurikulumKalenderId = null, string $nama = 'Kegiatan'): Kegiatan
    {
        return Kegiatan::create([
            'nama' => $nama,
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => $this->jenisKegiatanTambahan(),
            'tanggal' => $tanggal,
            'status' => 'TERLAKSANA',
            'kurikulum_kalender_id' => $kurikulumKalenderId,
            'dibuat_oleh' => User::factory()->create()->id,
        ]);
    }

    public function test_susun_kelompok_mengembalikan_struktur_sesuai_srs_3_3(): void
    {
        $kelompok = Kelompok::factory()->create();
        $periode = '2026-08';

        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id, 'periode' => $periode, 'jenjang' => 'ACR',
            'status_domisili' => 'SETEMPAT', 'jenis_kelamin' => 'LAKI', 'jumlah' => 5,
        ]);
        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id, 'periode' => $periode, 'jenjang' => 'ACR',
            'status_domisili' => 'PENDATANG', 'jenis_kelamin' => 'PEREMPUAN', 'jumlah' => 2,
        ]);

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, $periode);

        $this->assertSame(['kelompok' => $kelompok->nama, 'periode' => $periode], $snapshot['cover']);
        $this->assertSame(['tersedia' => false, 'pesan' => 'Modul Monitoring 29 Karakter menyusul di Fase 3.'], $snapshot['karakter_29']);
        $this->assertFalse($snapshot['sarpras']['tersedia']);
        $this->assertFalse($snapshot['shodaqoh']['tersedia']);
        $this->assertFalse($snapshot['foto']['tersedia']);

        $baris = collect($snapshot['sensus']['per_kategori'])->firstWhere('jenjang', 'ACR');
        $this->assertSame(5, $baris['laki']);
        $this->assertSame(2, $baris['perempuan']);
        $this->assertSame(5, $baris['setempat']);
        $this->assertSame(2, $baris['pendatang']);
    }

    public function test_pengurus_diambil_dari_users_aktif_kelompok_dengan_label_jabatan(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id, 'nama' => 'Ahmad']);
        $pjk->assignRole('pjp-kelompok');
        $nonAktif = User::factory()->create(['kelompok_id' => $kelompok->id, 'is_active' => false]);
        $nonAktif->assignRole('sekretaris-kbm');

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $this->assertCount(1, $snapshot['pengurus']);
        $this->assertSame('Ahmad', $snapshot['pengurus'][0]['nama']);
        $this->assertSame('PJP Kelompok (Koordinator)', $snapshot['pengurus'][0]['jabatan']);
    }

    public function test_kegiatan_tambahan_mengecualikan_kegiatan_kbm(): void
    {
        $kelompok = Kelompok::factory()->create();

        $kurikulum = KurikulumKalender::create([
            'jenjang' => 'ACR', 'tanggal_mulai' => '2026-08-16', 'tanggal_selesai' => '2026-08-16',
            'dibuat_oleh' => User::factory()->create()->id,
        ]);

        $this->buatKegiatan($kelompok, '2026-08-15', null, 'Pengajian Sabtu');
        $this->buatKegiatan($kelompok, '2026-08-16', $kurikulum->id, 'KBM Reguler');

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $this->assertCount(1, $snapshot['kegiatan']);
        $this->assertSame('Pengajian Sabtu', $snapshot['kegiatan'][0]['nama']);
    }

    public function test_kehadiran_dihitung_dari_kegiatan_peserta(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kegiatan = $this->buatKegiatan($kelompok, '2026-08-10');
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);

        foreach (range(1, 3) as $i) {
            KegiatanPeserta::create([
                'kegiatan_id' => $kegiatan->id,
                'generus_id' => Generus::factory()->create(['kelas_id' => $kelas->id])->id,
                'kelompok_id' => $kelompok->id,
                'status_presensi' => 'HADIR',
            ]);
        }
        KegiatanPeserta::create([
            'kegiatan_id' => $kegiatan->id,
            'generus_id' => Generus::factory()->create(['kelas_id' => $kelas->id])->id,
            'kelompok_id' => $kelompok->id,
            'status_presensi' => 'ALPHA',
        ]);

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $this->assertSame(75, $snapshot['kehadiran']['persentase_bulan_ini']);
        $this->assertCount(6, $snapshot['kehadiran']['tren_6_bulan']);
        $this->assertSame('2026-08', collect($snapshot['kehadiran']['tren_6_bulan'])->last()['periode']);
    }

    public function test_program_monitoring_meringkas_status_item(): void
    {
        $kelompok = Kelompok::factory()->create();
        $program = ProgramMonitoring::create([
            'kelompok_id' => $kelompok->id, 'nama_program' => 'Turba GPN', 'dibuat_oleh' => User::factory()->create()->id,
        ]);
        ProgramMonitoringItem::create(['program_monitoring_id' => $program->id, 'status_item' => 'SELESAI']);
        ProgramMonitoringItem::create(['program_monitoring_id' => $program->id, 'status_item' => 'BELUM']);

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $this->assertSame('Turba GPN', $snapshot['program_monitoring'][0]['nama_program']);
        $this->assertSame(1, $snapshot['program_monitoring'][0]['ringkasan_status']['selesai']);
        $this->assertSame(1, $snapshot['program_monitoring'][0]['ringkasan_status']['belum']);
    }

    public function test_absensi_mustin_diambil_dari_jenis_musyawaroh_perlu_jumlah_hadir(): void
    {
        $kelompok = Kelompok::factory()->create();
        $mustin = JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('perlu_jumlah_hadir', true)->firstOrFail();

        Musyawaroh::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id, 'jenis_musyawaroh_id' => $mustin->id,
            'tanggal' => '2026-08-05', 'jumlah_hadir' => 12,
        ]);

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $this->assertSame(12, $snapshot['musyawaroh']['absensi_mustin']['hadir']);
        $this->assertNull($snapshot['musyawaroh']['absensi_mustin']['total_kk']);
    }

    public function test_evaluasi_dan_resume_musyawaroh_dipisah_per_bulan(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pengurusKbm = JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('perlu_jumlah_hadir', false)->firstOrFail();

        $bulanLalu = Musyawaroh::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id, 'jenis_musyawaroh_id' => $pengurusKbm->id, 'tanggal' => '2026-07-05',
        ]);
        MusyawarohItem::create(['musyawaroh_id' => $bulanLalu->id, 'pokok_masalah' => 'Kehadiran menurun']);

        $bulanIni = Musyawaroh::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id, 'jenis_musyawaroh_id' => $pengurusKbm->id, 'tanggal' => '2026-08-05',
        ]);
        MusyawarohItem::create(['musyawaroh_id' => $bulanIni->id, 'pokok_masalah' => 'Rencana Mustin', 'pic' => 'Sekretaris']);

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $this->assertSame(['Kehadiran menurun'], $snapshot['musyawaroh']['evaluasi_bulan_lalu']);
        $this->assertSame(['Rencana Mustin (PIC: Sekretaris)'], $snapshot['musyawaroh']['resume_bulan_ini']);
    }

    public function test_nama_per_jenjang_dikelompokkan_dari_generus_aktif(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        Generus::factory()->create(['kelas_id' => $kelas->id, 'jenjang_id' => $kelas->jenjang_id, 'nama' => 'Fatimah', 'status_aktif' => true]);
        Generus::factory()->create(['kelas_id' => $kelas->id, 'jenjang_id' => $kelas->jenjang_id, 'nama' => 'Nonaktif', 'status_aktif' => false]);

        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, '2026-08');

        $kodeJenjang = $kelas->jenjang->kode;
        $this->assertCount(1, $snapshot['sensus']['nama_per_jenjang'][$kodeJenjang]);
        $this->assertSame('Fatimah', $snapshot['sensus']['nama_per_jenjang'][$kodeJenjang][0]['nama']);
    }
}
