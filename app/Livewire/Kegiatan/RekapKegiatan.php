<?php

namespace App\Livewire\Kegiatan;

use App\Models\Kegiatan;
use App\Models\ProgramMonitoring;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-25 — Rekap Kegiatan & Program Monitoring (Cetak). SRS §18.1, UCIC UC-25.
 * Dihitung on-the-fly, tidak perlu tabel agregat terpisah di Fase 1.
 */
#[Layout('layouts.app')]
class RekapKegiatan extends Component
{
    public Kegiatan $kegiatan;

    public function mount(Kegiatan $kegiatan): void
    {
        $this->authorize('kegiatan.view');

        $user = Auth::guard('web')->user();

        // Kegiatan bisa diselenggarakan kelompok/desa lain — route-model binding tidak
        // otomatis mengecek kepemilikan, jadi verifikasi manual di sini (bukan cuma
        // permission generik `kegiatan.view`).
        if (! $user->hasRole('admin-daerah') && $kegiatan->tingkat !== 'DAERAH') {
            $desaId = $user->desa_id ?? $user->kelompok?->desa_id;

            $bolehLihat = match ($kegiatan->tingkat) {
                'KELOMPOK' => $user->kelompok_id && (int) $kegiatan->penyelenggara_id === (int) $user->kelompok_id,
                'DESA' => $desaId && (int) $kegiatan->penyelenggara_id === (int) $desaId,
                default => false,
            };

            abort_unless($bolehLihat, 403);
        }

        $this->kegiatan = $kegiatan;
    }

    public function render()
    {
        $peserta = $this->kegiatan->peserta()->with(['generus', 'kelompok', 'kelas'])->get();

        $rekapPerKelompok = $peserta->groupBy('kelompok_id')->map(function ($rows) {
            $total = $rows->count();
            $hadir = $rows->where('status_presensi', 'HADIR')->count();

            // Breakdown per-kelas (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5) —
            // tersedia begitu kegiatan_peserta.kelas_id terisi, baik untuk Kegiatan KBM
            // (satu kelas) maupun Kegiatan Tambahan yang pesertanya lintas kelas.
            $perKelas = $rows->whereNotNull('kelas_id')->groupBy('kelas_id')->map(function ($kelasRows) {
                $totalKelas = $kelasRows->count();
                $hadirKelas = $kelasRows->where('status_presensi', 'HADIR')->count();

                return [
                    'kelas' => $kelasRows->first()->kelas,
                    'hadir' => $hadirKelas,
                    'izin' => $kelasRows->where('status_presensi', 'IZIN')->count(),
                    'sakit' => $kelasRows->where('status_presensi', 'SAKIT')->count(),
                    'alpha' => $kelasRows->where('status_presensi', 'ALPHA')->count(),
                    'persentase' => $totalKelas > 0 ? round(($hadirKelas / $totalKelas) * 100) : 0,
                ];
            })->values();

            return [
                'kelompok' => $rows->first()->kelompok,
                'hadir' => $hadir,
                'izin' => $rows->where('status_presensi', 'IZIN')->count(),
                'sakit' => $rows->where('status_presensi', 'SAKIT')->count(),
                'alpha' => $rows->where('status_presensi', 'ALPHA')->count(),
                'persentase' => $total > 0 ? round(($hadir / $total) * 100) : 0,
                'per_kelas' => $perKelas,
            ];
        });

        // UCIC UC-25 — rekap ini juga menampilkan status Program Monitoring kelompoknya,
        // bukan cuma kehadiran Kegiatan. ProgramMonitoring di-scope kelompok_id saja
        // (UC-24, tidak tertaut ke kegiatan_id tertentu), jadi diambil terpisah per
        // Kelompok peserta yang muncul di rekap ini.
        $programMonitoringPerKelompok = ProgramMonitoring::withoutGlobalScopes()
            ->with('items')
            ->whereIn('kelompok_id', $peserta->pluck('kelompok_id')->unique())
            ->get()
            ->groupBy('kelompok_id');

        return view('livewire.kegiatan.rekap-kegiatan', [
            'rekapPerKelompok' => $rekapPerKelompok,
            'pesertaPerKelompok' => $peserta->groupBy(fn ($p) => $p->kelompok->nama),
            'programMonitoringPerKelompok' => $programMonitoringPerKelompok,
        ]);
    }
}
