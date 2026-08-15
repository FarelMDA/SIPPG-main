<?php

namespace App\Services\Kegiatan;

/** Ringkasan hasil generate/regenerasi kejadian Kegiatan dari Jadwal (UC-30). */
final class HasilGenerate
{
    public function __construct(
        public readonly int $jumlahDibuat,
        public readonly int $jumlahDilewatiLibur,
    ) {}
}
