<?php

namespace App\Services\Kegiatan;

use RuntimeException;

/**
 * SRS-Fase-2 §2.3 — satu Jadwal maksimal menghasilkan 370 kejadian per generate/regenerasi.
 */
class BatasEkspansiTerlampauiException extends RuntimeException
{
    public function __construct(public readonly int $jumlahDihasilkan)
    {
        parent::__construct("Rentang tanggal terlalu panjang — pola ini menghasilkan {$jumlahDihasilkan} kejadian, melebihi batas 370.");
    }
}
