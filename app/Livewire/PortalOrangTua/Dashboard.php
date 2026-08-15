<?php

namespace App\Livewire\PortalOrangTua;

use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-17 — Lihat Data Anak (Portal Orang Tua), halaman Dashboard/Pemilih Anak.
 * SRS §12, UCIC UC-17.
 */
#[Layout('layouts.portal')]
class Dashboard extends Component
{
    public ?int $generus_terpilih_id = null;

    public function mount(): void
    {
        $akun = Auth::guard('orangtua')->user();
        $daftarAnak = $akun->generus;

        $this->generus_terpilih_id = session('portal_generus_id') ?? $daftarAnak->first()?->id;

        if ($this->generus_terpilih_id && ! $daftarAnak->pluck('id')->contains($this->generus_terpilih_id)) {
            $this->generus_terpilih_id = $daftarAnak->first()?->id;
        }
    }

    public function pilihAnak(int $generusId): void
    {
        $this->generus_terpilih_id = $generusId;
        session(['portal_generus_id' => $generusId]);
    }

    public function render()
    {
        $akun = Auth::guard('orangtua')->user();
        $daftarAnak = $akun->generus()->with('kelas.kelompok')->get();
        $anak = $daftarAnak->firstWhere('id', $this->generus_terpilih_id);

        $ringkasanKehadiran = null;
        $materiTerakhir = null;

        if ($anak) {
            $awal = now()->startOfMonth();

            // KBM Reguler (Kegiatan ber-kurikulum_kalender_id) — menggantikan Presensi/
            // Jurnal Harian lama, docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
            $counts = KegiatanPeserta::where('generus_id', $anak->id)
                ->whereHas('kegiatan', fn ($q) => $q->whereNotNull('kurikulum_kalender_id')
                    ->whereDate('tanggal', '>=', $awal->toDateString())
                    ->whereDate('tanggal', '<=', now()->toDateString()))
                ->selectRaw('status_presensi as status, count(*) as jumlah')
                ->groupBy('status_presensi')
                ->pluck('jumlah', 'status');
            $total = $counts->sum();

            $ringkasanKehadiran = $total > 0 ? round((($counts['HADIR'] ?? 0) / $total) * 100) : null;

            $materiTerakhir = Kegiatan::whereNotNull('kurikulum_kalender_id')
                ->whereHas('targetKelas', fn ($q) => $q->where('kelas.id', $anak->kelas_id))
                ->orderByDesc('tanggal')
                ->first();
        }

        return view('livewire.portal-orang-tua.dashboard', [
            'daftarAnak' => $daftarAnak,
            'anak' => $anak,
            'ringkasanKehadiran' => $ringkasanKehadiran,
            'materiTerakhir' => $materiTerakhir,
        ]);
    }
}
