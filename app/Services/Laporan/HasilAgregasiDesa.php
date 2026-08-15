<?php

namespace App\Services\Laporan;

/** Ringkasan hasil agregasi Laporan Desa dari laporan Kelompok yang sudah FINAL/DISETUJUI (SRS-Fase-2 §3.4). */
final class HasilAgregasiDesa
{
    /** @param  list<string>  $kelompokBelumFinal */
    public function __construct(
        public readonly array $snapshot,
        public readonly int $jumlahKelompokFinal,
        public readonly int $jumlahKelompokTotal,
        public readonly array $kelompokBelumFinal,
    ) {}

    public function adaYangBelumFinal(): bool
    {
        return $this->jumlahKelompokFinal < $this->jumlahKelompokTotal;
    }
}
