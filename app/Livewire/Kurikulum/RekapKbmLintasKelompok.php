<?php

namespace App\Livewire\Kurikulum;

use App\Models\Desa;
use App\Models\Jenjang;
use App\Models\Kegiatan;
use App\Models\Kelas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rekap KBM Reguler lintas Kelompok — rollup otomatis Kelompok→Desa/Daerah untuk
 * Kegiatan hasil-Kurikulum (sinyal keterkaitan jelas: jenjang + kurikulum_kalender_id
 * yang sama, lewat relasi struktural kelas.kelompok_id→kelompok.desa_id). Kegiatan
 * Tambahan independen lintas tingkat TETAP pakai kegiatan_program_id opt-in
 * (lihat RekapProgramKegiatan) — bukan mekanisme ini. docs/Visi-Konvergensi-
 * Kurikulum-Kegiatan-Presensi.md §5.
 */
#[Layout('layouts.app')]
class RekapKbmLintasKelompok extends Component
{
    #[Url]
    public string $filterJenjang = '';

    #[Url]
    public ?int $filterDesaId = null;

    #[Url]
    public string $bulan = '';

    public function mount(): void
    {
        $this->authorize('kegiatan.view');
        $this->bulan = now()->format('Y-m');
        $this->filterJenjang = Jenjang::orderBy('urutan')->value('kode') ?? '';
    }

    /**
     * Scope sesuai SRS-Fase-1 §4.2: hanya Admin Daerah (global) dan PJP Desa/role Desa
     * lain (se-Desa) yang punya visibilitas lintas-Kelompok — role Kelompok (PJP
     * Kelompok, Sekretaris KBM, Guru) dibatasi ke Kelompoknya sendiri, sama seperti
     * scope Generus/Pendidik/Musyawaroh. `$user->desa_id` (bukan
     * `$user->kelompok?->desa_id`) dipakai untuk mendeteksi akun tingkat Desa — kolom
     * ini hanya terisi untuk role Desa/Daerah, bukan role Kelompok.
     */
    private function kelasIdsSesuaiCakupan()
    {
        $user = Auth::guard('web')->user();

        $query = Kelas::query()->where('jenjang_id', Jenjang::where('kode', $this->filterJenjang)->value('id'));

        if ($user->hasRole('admin-daerah')) {
            if ($this->filterDesaId) {
                $query->whereHas('kelompok', fn ($q) => $q->where('desa_id', $this->filterDesaId));
            }
        } elseif ($user->desa_id) {
            $query->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id));
        } else {
            $query->where('kelompok_id', $user->kelompok_id);
        }

        return $query->pluck('id');
    }

    public function render()
    {
        $awal = Carbon::parse($this->bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $rekapPerKelompok = collect();

        if ($this->filterJenjang) {
            $kelasIds = $this->kelasIdsSesuaiCakupan();

            $kegiatan = Kegiatan::whereNotNull('kurikulum_kalender_id')
                ->whereHas('targetKelas', fn ($q) => $q->whereIn('kelas.id', $kelasIds))
                ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
                ->with(['targetKelas.kelompok', 'peserta'])
                ->get();

            $rekapPerKelompok = $kegiatan
                ->flatMap(fn (Kegiatan $k) => $k->targetKelas->map(fn (Kelas $kelas) => ['kegiatan' => $k, 'kelas' => $kelas]))
                ->groupBy(fn ($row) => $row['kelas']->id)
                ->map(function ($rows) {
                    $kelas = $rows->first()['kelas'];
                    $kegiatanKelas = $rows->pluck('kegiatan');
                    $totalPeserta = $kegiatanKelas->flatMap->peserta;
                    $hadir = $totalPeserta->where('status_presensi', 'HADIR')->count();

                    return [
                        'kelas' => $kelas,
                        'kelompok' => $kelas->kelompok,
                        'total_jadwal' => $kegiatanKelas->count(),
                        'terlaksana' => $kegiatanKelas->whereIn('realisasi_status', ['SESUAI_JADWAL', 'PENGGANTI'])->count(),
                        'persentase_kehadiran' => $totalPeserta->count() > 0 ? round(($hadir / $totalPeserta->count()) * 100) : 0,
                    ];
                })
                ->sortBy(fn ($row) => $row['kelompok']->nama.'-'.$row['kelas']->nama)
                ->groupBy(fn ($row) => $row['kelompok']->nama);
        }

        return view('livewire.kurikulum.rekap-kbm-lintas-kelompok', [
            'jenjangOptions' => Jenjang::kodeOptions(),
            'desaOptions' => Desa::orderBy('nama')->pluck('nama', 'id'),
            'rekapPerKelompok' => $rekapPerKelompok,
        ]);
    }
}
