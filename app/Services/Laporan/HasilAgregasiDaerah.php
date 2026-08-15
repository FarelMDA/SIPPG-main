<?php

namespace App\Services\Laporan;

/** Ringkasan hasil agregasi Laporan Daerah dari laporan Desa yang sudah FINAL/DISETUJUI (SRS-Fase-2 §3.5). */
final class HasilAgregasiDaerah
{
    /** @param  list<string>  $desaBelumFinal */
    public function __construct(
        public readonly array $snapshot,
        public readonly int $jumlahDesaFinal,
        public readonly int $jumlahDesaTotal,
        public readonly array $desaBelumFinal,
    ) {}

    public function adaYangBelumFinal(): bool
    {
        return $this->jumlahDesaFinal < $this->jumlahDesaTotal;
    }
}
