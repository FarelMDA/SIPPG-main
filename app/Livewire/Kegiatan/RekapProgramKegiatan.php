<?php

namespace App\Livewire\Kegiatan;

use App\Models\Kegiatan;
use App\Models\KegiatanProgram;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * UC-40 — Rekap Program Kegiatan (Gabungan Lintas-Tingkat). SRS-Fase-2 §2.8.
 * Murni agregasi baca lintas Kegiatan (Jadwal maupun Insidental) yang berbagi
 * kegiatan_program_id yang sama — Program tidak punya batasan kepemilikan (§2.8).
 */
#[Layout('layouts.app')]
class RekapProgramKegiatan extends Component
{
    #[Url]
    public ?int $kegiatanProgramId = null;

    #[Url]
    public string $periode = '';

    public function mount(): void
    {
        $this->authorize('kegiatan.view');
        $this->periode = $this->periode ?: now()->format('Y-m');
    }

    public function render()
    {
        // Filter periode di PHP (bukan fungsi tanggal SQL spesifik-dialek) — portabel
        // lintas MySQL/MariaDB/SQLite, konsisten prinsip SRS-Fase-1 §18.1.
        $kegiatan = $this->kegiatanProgramId
            ? Kegiatan::with('peserta')
                ->where('kegiatan_program_id', $this->kegiatanProgramId)
                ->get()
                ->filter(fn (Kegiatan $k) => $k->tanggal->format('Y-m') === $this->periode)
            : collect();

        $rekapPerPenyelenggara = $kegiatan->groupBy(fn (Kegiatan $k) => $k->tingkat.'-'.$k->penyelenggara_id)
            ->map(function ($rows) {
                $peserta = $rows->flatMap->peserta;
                $total = $peserta->count();
                $hadir = $peserta->where('status_presensi', 'HADIR')->count();

                return [
                    'tingkat' => $rows->first()->tingkat,
                    'penyelenggara' => $rows->first()->penyelenggara_nama,
                    'kegiatan' => $rows,
                    'hadir' => $hadir,
                    'izin' => $peserta->where('status_presensi', 'IZIN')->count(),
                    'sakit' => $peserta->where('status_presensi', 'SAKIT')->count(),
                    'alpha' => $peserta->where('status_presensi', 'ALPHA')->count(),
                    'persentase' => $total > 0 ? round(($hadir / $total) * 100) : 0,
                ];
            });

        return view('livewire.kegiatan.rekap-program-kegiatan', [
            'programOptions' => KegiatanProgram::orderBy('nama')->pluck('nama', 'id'),
            'rekapPerPenyelenggara' => $rekapPerPenyelenggara,
            'totalHadir' => $kegiatan->flatMap->peserta->where('status_presensi', 'HADIR')->count(),
            'totalPeserta' => $kegiatan->flatMap->peserta->count(),
        ]);
    }
}
