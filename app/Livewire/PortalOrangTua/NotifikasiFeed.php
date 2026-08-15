<?php

namespace App\Livewire\PortalOrangTua;

use App\Models\NotifikasiOrangTua;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-18 — Notifikasi Alpha (tampilan Portal Orang Tua). SRS §12.3, UCIC UC-18.
 */
#[Layout('layouts.portal')]
class NotifikasiFeed extends Component
{
    public function tandaiDibaca(int $notifikasiId): void
    {
        NotifikasiOrangTua::where('akun_orang_tua_id', Auth::guard('orangtua')->id())
            ->where('id', $notifikasiId)
            ->update(['dibaca_pada' => now()]);
    }

    public function render()
    {
        // Feed selalu gabungan semua anak — TIDAK ikut ter-filter pemilih anak (SRS §12.3, UIUX §3.3.1).
        $notifikasi = NotifikasiOrangTua::with('generus')
            ->where('akun_orang_tua_id', Auth::guard('orangtua')->id())
            ->orderByDesc('created_at')
            ->get();

        $tertautLebihSatu = Auth::guard('orangtua')->user()->generus()->count() > 1;

        return view('livewire.portal-orang-tua.notifikasi-feed', [
            'notifikasi' => $notifikasi,
            'tertautLebihSatu' => $tertautLebihSatu,
        ]);
    }
}
