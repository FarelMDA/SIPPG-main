<?php

namespace App\Events;

use App\Models\KegiatanPeserta;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dipicu UC-23 (Input Presensi Kegiatan, termasuk KBM Reguler ber-kurikulum_kalender_id)
 * / Sinkronisasi Offline, ditangkap UC-18 (Notifikasi Alpha) — Struktur-Proyek §3.3.
 * Sebelum konvergensi (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5) dipicu
 * dari Presensi Harian — kini dari KegiatanPeserta untuk semua jenis Kegiatan.
 */
class PresensiAlphaDicatat
{
    use Dispatchable;

    public function __construct(public KegiatanPeserta $pesertaKegiatan) {}
}
