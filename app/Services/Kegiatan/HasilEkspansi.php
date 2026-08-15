<?php

namespace App\Services\Kegiatan;

use Carbon\CarbonImmutable;

/**
 * Hasil ekspansi pola Jadwal Kegiatan Berulang (SRS-Fase-2 §2.3) — dipakai identik oleh
 * pratinjau (tanpa simpan, UC-28 `hitungPratinjau()`) maupun generate sungguhan (UC-30).
 */
final class HasilEkspansi
{
    /**
     * @param  list<CarbonImmutable>  $tanggalDibuat  tanggal yang lolos (sudah dikurangi hari libur), terurut
     * @param  int  $jumlahDilewatiLibur  jumlah tanggal yang cocok pola tapi dilewati karena Hari Libur
     * @param  list<string>  $contohTanggal  beberapa tanggal pertama (format d/m/Y) untuk pratinjau
     */
    public function __construct(
        public readonly array $tanggalDibuat,
        public readonly int $jumlahDilewatiLibur,
        public readonly array $contohTanggal,
    ) {}

    public function jumlah(): int
    {
        return count($this->tanggalDibuat);
    }
}
