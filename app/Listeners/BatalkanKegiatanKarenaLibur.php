<?php

namespace App\Listeners;

use App\Events\HariLiburDisimpan;
use App\Models\Kegiatan;

/**
 * UC-39 — Sinkronisasi Otomatis Kegiatan vs Hari Libur. SRS-Fase-2 §2.6.
 *
 * Hanya kejadian hasil generate Jadwal Rutin (kegiatan_jadwal_id terisi) yang ikut
 * dibatalkan retroaktif — Kegiatan Insidental tidak disentuh (sudah diperingatkan
 * non-blocking satu-satu saat dibuat, UC-38). Kejadian lampau atau yang sudah ada
 * presensinya juga tidak pernah disentuh.
 */
class BatalkanKegiatanKarenaLibur
{
    public function handle(HariLiburDisimpan $event): void
    {
        $libur = $event->hariLibur;

        // whereDate() dipakai di kedua batas (bukan whereBetween/where string biasa) —
        // kolom `tanggal` ber-cast 'date' tapi Eloquent selalu menyimpannya sebagai
        // "Y-m-d H:i:s" penuh, jadi perbandingan string biasa terhadap batas atas persis
        // bisa salah di tanggal tepi (pola sama App\Models\Concerns\UpsertsByTanggal).
        $terdampak = Kegiatan::whereNotNull('kegiatan_jadwal_id')
            ->whereDate('tanggal', '>=', $libur->tanggal_mulai->toDateString())
            ->whereDate('tanggal', '<=', $libur->tanggal_selesai->toDateString())
            ->whereDate('tanggal', '>=', now()->toDateString())
            ->doesntHave('peserta')
            ->get();

        if ($terdampak->isEmpty()) {
            return;
        }

        Kegiatan::whereIn('id', $terdampak->pluck('id'))->update([
            'status' => 'TIDAK_TERLAKSANA',
            'catatan_status' => "Dibatalkan otomatis — Hari Libur: {$libur->nama}",
        ]);

        activity('hari-libur')
            ->performedOn($libur)
            ->withProperties(['jumlah_kegiatan_dibatalkan' => $terdampak->count()])
            ->log('Kegiatan mendatang otomatis dibatalkan karena Hari Libur');
    }
}
