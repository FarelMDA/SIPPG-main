<?php

namespace App\Livewire\PortalOrangTua;

use App\Models\KegiatanPeserta;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-17 — Lihat Data Anak (Portal Orang Tua): Presensi. SRS §12, UCIC UC-17.
 */
#[Layout('layouts.portal')]
class LihatPresensi extends Component
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

        // Data yang ditampilkan dibatasi hanya ke Generus yang tertaut ke akun
        // yang login (SRS §12, UC-17) — tidak pernah dari input ID bebas.
        $anak = $daftarAnak->firstWhere('id', $this->generus_terpilih_id);

        $awal = \Illuminate\Support\Carbon::parse($this->bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        // KBM Reguler (Kegiatan ber-kurikulum_kalender_id) — menggantikan Presensi lama,
        // docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
        $presensi = $anak
            ? KegiatanPeserta::where('generus_id', $anak->id)
                ->whereHas('kegiatan', fn ($q) => $q->whereNotNull('kurikulum_kalender_id')
                    ->whereDate('tanggal', '>=', $awal->toDateString())
                    ->whereDate('tanggal', '<=', $akhir->toDateString()))
                ->with('kegiatan')
                ->get()
                ->sortByDesc(fn ($p) => $p->kegiatan->tanggal)
                ->values()
            : collect();

        return view('livewire.portal-orang-tua.lihat-presensi', [
            'daftarAnak' => $daftarAnak,
            'anak' => $anak,
            'presensi' => $presensi,
        ]);
    }
}
