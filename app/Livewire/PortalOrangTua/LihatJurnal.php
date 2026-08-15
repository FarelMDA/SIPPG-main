<?php

namespace App\Livewire\PortalOrangTua;

use App\Models\Kegiatan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-17 — Lihat Data Anak (Portal Orang Tua): Jurnal. SRS §12, UCIC UC-17.
 */
#[Layout('layouts.portal')]
class LihatJurnal extends Component
{
    public ?int $generus_terpilih_id = null;

    public string $bulan = '';

    public function mount(): void
    {
        $daftarAnak = Auth::guard('orangtua')->user()->generus;
        $this->generus_terpilih_id = session('portal_generus_id') ?? $daftarAnak->first()?->id;
        $this->bulan = now()->format('Y-m');
    }

    public function pilihAnak(int $generusId): void
    {
        $this->generus_terpilih_id = $generusId;
        session(['portal_generus_id' => $generusId]);
    }

    public function render()
    {
        $daftarAnak = Auth::guard('orangtua')->user()->generus()->with('kelas')->get();
        $anak = $daftarAnak->firstWhere('id', $this->generus_terpilih_id);

        $awal = \Illuminate\Support\Carbon::parse($this->bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        // KBM Reguler (Kegiatan ber-kurikulum_kalender_id) — menggantikan Jurnal Harian
        // lama, docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
        $jurnal = $anak
            ? Kegiatan::whereNotNull('kurikulum_kalender_id')
                ->whereHas('targetKelas', fn ($q) => $q->where('kelas.id', $anak->kelas_id))
                ->whereDate('tanggal', '>=', $awal->toDateString())->whereDate('tanggal', '<=', $akhir->toDateString())
                ->orderByDesc('tanggal')->get()
            : collect();

        return view('livewire.portal-orang-tua.lihat-jurnal', [
            'daftarAnak' => $daftarAnak,
            'anak' => $anak,
            'jurnal' => $jurnal,
        ]);
    }
}
