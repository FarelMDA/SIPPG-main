<?php

namespace App\Listeners;

use App\Events\PresensiAlphaDicatat;
use App\Models\NotifikasiOrangTua;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * UC-18 — Notifikasi Alpha. PRD §12.3, UCIC UC-18.
 * Notifikasi in-app adalah jalur utama (gratis) — WhatsApp opsional Fase 2+.
 */
class KirimNotifikasiAlpha implements ShouldQueue
{
    public function handle(PresensiAlphaDicatat $event): void
    {
        $peserta = $event->pesertaKegiatan;
        $generus = $peserta->generus;
        $tanggal = $peserta->kegiatan->tanggal;

        foreach ($generus->akunOrangTua as $akun) {
            NotifikasiOrangTua::create([
                'akun_orang_tua_id' => $akun->id,
                'generus_id' => $generus->id,
                'tipe' => 'ALPHA',
                'pesan' => "{$generus->nama} tidak hadir pada {$tanggal->translatedFormat('d F Y')}",
            ]);
        }
    }
}
