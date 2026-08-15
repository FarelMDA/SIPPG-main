<?php

namespace Tests\Unit\Services\Kegiatan;

use App\Services\Kegiatan\EkspansiPolaJadwal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * UC-28/UC-30, SRS-Fase-2 §2.2–§2.3 — bagian paling berisiko dari modul Jadwal Kegiatan
 * Berulang (algoritma tanggal). Murni unit test tanpa DB — lihat App\Services\Kegiatan\EkspansiPolaJadwal.
 */
class EkspansiPolaJadwalTest extends TestCase
{
    private EkspansiPolaJadwal $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new EkspansiPolaJadwal;
    }

    private function tanggal(string $s): CarbonImmutable
    {
        return CarbonImmutable::parse($s);
    }

    /** @return Collection<int, array{mulai: CarbonImmutable, selesai: CarbonImmutable}> */
    private function tanpaLibur(): Collection
    {
        return collect();
    }

    private function assertTanggalSama(array $expected, array $actual): void
    {
        $this->assertSame(
            $expected,
            array_map(fn (CarbonImmutable $t) => $t->toDateString(), $actual)
        );
    }

    public function test_harian_dasar(): void
    {
        $pola = [
            'frekuensi_tipe' => 'HARIAN',
            'hari_dalam_minggu' => ['SENIN', 'RABU'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-08-03'), // Senin
            'tanggal_selesai' => $this->tanggal('2026-08-30'), // Minggu, 4 minggu penuh
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertTanggalSama([
            '2026-08-03', '2026-08-05', '2026-08-10', '2026-08-12',
            '2026-08-17', '2026-08-19', '2026-08-24', '2026-08-26',
        ], $hasil->tanggalDibuat);
        $this->assertSame(0, $hasil->jumlahDilewatiLibur);
    }

    public function test_bulanan_angka_minggu_ke_eksplisit(): void
    {
        // 1 Agustus 2026 = Sabtu (dikonfirmasi) -> minggu ke-1; minggu ke-2 = 8 Agu, ke-4 = 22 Agu.
        $pola = [
            'frekuensi_tipe' => 'BULANAN',
            'hari_dalam_minggu' => ['SABTU'],
            'minggu_ke_dalam_bulan' => [2, 4],
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-08-01'),
            'tanggal_selesai' => $this->tanggal('2026-08-31'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertTanggalSama(['2026-08-08', '2026-08-22'], $hasil->tanggalDibuat);
    }

    public function test_bulanan_terakhir_di_bulan_4_vs_5_kemunculan(): void
    {
        // Januari 2026 punya 5 Sabtu (terakhir 31 Jan), Februari 2026 cuma 4 (terakhir 28 Feb).
        $pola = [
            'frekuensi_tipe' => 'BULANAN',
            'hari_dalam_minggu' => ['SABTU'],
            'minggu_ke_dalam_bulan' => ['TERAKHIR'],
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-01-01'),
            'tanggal_selesai' => $this->tanggal('2026-02-28'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertTanggalSama(['2026-01-31', '2026-02-28'], $hasil->tanggalDibuat);
    }

    public function test_bulanan_angka_minggu_ke_yang_tidak_selalu_ada_dilewati_tanpa_error(): void
    {
        // minggu ke-5 Sabtu: ada di Januari 2026 (31 Jan), TIDAK ada di Februari/Maret 2026.
        $pola = [
            'frekuensi_tipe' => 'BULANAN',
            'hari_dalam_minggu' => ['SABTU'],
            'minggu_ke_dalam_bulan' => [5],
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-01-01'),
            'tanggal_selesai' => $this->tanggal('2026-03-31'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertTanggalSama(['2026-01-31'], $hasil->tanggalDibuat);
    }

    public function test_cartesian_product_hari_dan_minggu_ke(): void
    {
        $pola = [
            'frekuensi_tipe' => 'BULANAN',
            'hari_dalam_minggu' => ['SABTU', 'SENIN'],
            'minggu_ke_dalam_bulan' => [2, 4],
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-08-01'),
            'tanggal_selesai' => $this->tanggal('2026-08-31'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        // Sabtu minggu-2 & 4 (8, 22 Agu) DAN Senin minggu-2 & 4 (10, 24 Agu) — 4 tanggal berbeda.
        $this->assertTanggalSama(['2026-08-08', '2026-08-10', '2026-08-22', '2026-08-24'], $hasil->tanggalDibuat);
    }

    public function test_mingguan_interval_lintas_batas_bulan(): void
    {
        // tanggal_mulai = Senin 31 Agustus 2026, interval 2 minggu -> minggu aktif berikutnya
        // adalah minggu yang memuat 14 September, lalu 28 September — lepas dari batas bulan.
        $pola = [
            'frekuensi_tipe' => 'MINGGUAN_INTERVAL',
            'hari_dalam_minggu' => ['SENIN'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => 2,
            'tanggal_mulai' => $this->tanggal('2026-08-31'),
            'tanggal_selesai' => $this->tanggal('2026-09-30'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertTanggalSama(['2026-08-31', '2026-09-14', '2026-09-28'], $hasil->tanggalDibuat);
    }

    public function test_mingguan_interval_tanggal_mulai_bukan_hari_senin(): void
    {
        // tanggal_mulai = Rabu 5 Agustus 2026 (bukan awal minggu) -> minggu acuan tetap
        // minggu yang MEMUAT 5 Agustus (3-9 Agustus), bukan mulai dari hari itu sendiri.
        $pola = [
            'frekuensi_tipe' => 'MINGGUAN_INTERVAL',
            'hari_dalam_minggu' => ['RABU'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => 2,
            'tanggal_mulai' => $this->tanggal('2026-08-05'),
            'tanggal_selesai' => $this->tanggal('2026-09-10'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertTanggalSama(['2026-08-05', '2026-08-19', '2026-09-02'], $hasil->tanggalDibuat);
    }

    public function test_pengecualian_hari_libur_exact_date(): void
    {
        $pola = [
            'frekuensi_tipe' => 'HARIAN',
            'hari_dalam_minggu' => ['SENIN'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-08-03'),
            'tanggal_selesai' => $this->tanggal('2026-08-24'),
        ];

        $hariLibur = collect([
            ['mulai' => $this->tanggal('2026-08-10'), 'selesai' => $this->tanggal('2026-08-10')],
        ]);

        $hasil = $this->engine->ekspansi($pola, $hariLibur);

        $this->assertTanggalSama(['2026-08-03', '2026-08-17', '2026-08-24'], $hasil->tanggalDibuat);
        $this->assertSame(1, $hasil->jumlahDilewatiLibur);
    }

    public function test_pengecualian_hari_libur_rentang_mengenai_dua_tanggal_sekaligus(): void
    {
        $pola = [
            'frekuensi_tipe' => 'HARIAN',
            'hari_dalam_minggu' => ['SENIN'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-08-03'),
            'tanggal_selesai' => $this->tanggal('2026-09-14'),
        ];

        // Satu baris hari_libur berupa rentang, mengenai 2 tanggal kandidat sekaligus (31 Agu & 7 Sep).
        $hariLibur = collect([
            ['mulai' => $this->tanggal('2026-08-29'), 'selesai' => $this->tanggal('2026-09-08')],
        ]);

        $hasil = $this->engine->ekspansi($pola, $hariLibur);

        $this->assertTanggalSama(['2026-08-03', '2026-08-10', '2026-08-17', '2026-08-24', '2026-09-14'], $hasil->tanggalDibuat);
        $this->assertSame(2, $hasil->jumlahDilewatiLibur);
    }

    public function test_contoh_tanggal_dibatasi_lima_dan_terurut(): void
    {
        $pola = [
            'frekuensi_tipe' => 'HARIAN',
            'hari_dalam_minggu' => ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'MINGGU'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => null,
            'tanggal_mulai' => $this->tanggal('2026-08-01'),
            'tanggal_selesai' => $this->tanggal('2026-08-31'),
        ];

        $hasil = $this->engine->ekspansi($pola, $this->tanpaLibur());

        $this->assertSame(31, $hasil->jumlah());
        $this->assertCount(5, $hasil->contohTanggal);
        $this->assertSame(['01/08/2026', '02/08/2026', '03/08/2026', '04/08/2026', '05/08/2026'], $hasil->contohTanggal);
    }
}
