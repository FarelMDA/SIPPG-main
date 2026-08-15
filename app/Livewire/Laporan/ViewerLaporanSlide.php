<?php

namespace App\Livewire\Laporan;

use App\Models\LaporanBulanan;
use App\Services\Laporan\SusunSnapshotLaporan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-LAP-02 — Viewer Laporan Slide 16:9 Interaktif. SRS-Fase-2 §3, UCIC-Fase-2 UC-31/UC-32.
 *
 * Deck slide dirakit dinamis dari `$this->data` (live query bila DRAFT tingkat Kelompok,
 * snapshot_data untuk selainnya — lihat LaporanBulanan::isLive()) — BUKAN 46 slide tetap,
 * lihat docblock App\Services\Laporan\SusunSnapshotLaporan untuk alasannya.
 */
#[Layout('layouts.app')]
class ViewerLaporanSlide extends Component
{
    public LaporanBulanan $laporan;

    public function mount(LaporanBulanan $laporan): void
    {
        $this->authorize('laporan.manage');

        $user = Auth::guard('web')->user();
        abort_unless($this->bolehLihat($laporan, $user), 403, 'Anda tidak berwenang melihat laporan ini');

        $this->laporan = $laporan;
    }

    private function bolehLihat(LaporanBulanan $laporan, $user): bool
    {
        if ($user->hasRole('admin-daerah')) {
            return true;
        }

        // Tingkat DAERAH: hanya admin-daerah (di atas) — jenjang teratas, PRD §18.4.
        return match ($laporan->tingkat) {
            'KELOMPOK' => (int) $laporan->kelompok_id === (int) $user->kelompok_id
                || ($user->hasRole('pjp-desa') && (int) $laporan->kelompok?->desa_id === (int) $user->desa_id),
            'DESA' => $user->desa_id !== null && (int) $laporan->desa_id === (int) $user->desa_id,
            default => false,
        };
    }

    public function getDataProperty(): array
    {
        return $this->laporan->isLive()
            ? app(SusunSnapshotLaporan::class)->susunKelompok($this->laporan->kelompok, $this->laporan->periode)
            : $this->laporan->snapshot_data;
    }

    /** Rakit deck slide dari struktur data §3.3 — lihat docblock kelas. */
    public function getSlidesProperty(): array
    {
        $data = $this->data;
        $tingkat = $this->laporan->tingkat;

        $slides = [
            ['partial' => 'cover', 'judul' => 'Cover', 'data' => $data['cover'] ?? []],
            ['partial' => 'pengurus', 'judul' => 'Pengurus', 'data' => $data['pengurus'] ?? []],
            ['partial' => 'sensus', 'judul' => 'Sensus', 'data' => $data['sensus'] ?? []],
        ];

        foreach ($data['sensus']['nama_per_jenjang'] ?? [] as $jenjang => $daftar) {
            $slides[] = ['partial' => 'generus-per-jenjang', 'judul' => "Daftar Generus — {$jenjang}", 'data' => ['jenjang' => $jenjang, 'daftar' => $daftar]];
        }

        $slides[] = ['partial' => 'kehadiran', 'judul' => 'Kehadiran', 'data' => $data['kehadiran'] ?? []];
        $slides[] = ['partial' => 'belum-tersedia', 'judul' => '29 Karakter', 'data' => $data['karakter_29'] ?? ['tersedia' => false]];
        $slides[] = ['partial' => 'belum-tersedia', 'judul' => 'Sarana & Prasarana', 'data' => $data['sarpras'] ?? ['tersedia' => false]];
        $slides[] = ['partial' => 'kegiatan', 'judul' => 'Kegiatan Tambahan', 'data' => $data['kegiatan'] ?? [], 'tingkat' => $tingkat];
        $slides[] = ['partial' => 'program-monitoring', 'judul' => 'Program Monitoring', 'data' => $data['program_monitoring'] ?? [], 'tingkat' => $tingkat];
        $slides[] = ['partial' => 'belum-tersedia', 'judul' => 'Shodaqoh PPG', 'data' => $data['shodaqoh'] ?? ['tersedia' => false]];
        $slides[] = ['partial' => 'absensi-mustin', 'judul' => 'Absensi Mustin', 'data' => $data['musyawaroh']['absensi_mustin'] ?? []];
        $slides[] = ['partial' => 'evaluasi-resume-musyawaroh', 'judul' => 'Evaluasi & Resume Musyawaroh', 'data' => $data['musyawaroh'] ?? []];
        $slides[] = ['partial' => 'belum-tersedia', 'judul' => 'Foto Kegiatan', 'data' => $data['foto'] ?? ['tersedia' => false]];

        return $slides;
    }

    public function getBisaFinalisasiProperty(): bool
    {
        return $this->laporan->status === 'DRAFT' && (int) $this->laporan->dibuat_oleh === (int) Auth::id();
    }

    /** Tingkat DAERAH tidak pernah direview siapa pun — jenjang teratas (PRD §18.4, SRS §3.1/§3.5). */
    public function getBolehReviewProperty(): bool
    {
        if ($this->laporan->status !== 'FINAL' || $this->laporan->tingkat === 'DAERAH') {
            return false;
        }

        $user = Auth::guard('web')->user();

        if ($this->laporan->tingkat === 'KELOMPOK') {
            return $user->hasRole('pjp-desa') && (int) $this->laporan->kelompok?->desa_id === (int) $user->desa_id;
        }

        return $user->hasRole('admin-daerah');
    }

    /**
     * Bekukan snapshot (SRS §3.1). Untuk tingkat DESA, snapshot_data sudah beku sejak
     * generate() (agregasi hanya membaca snapshot Kelompok yang sudah final) — di sini
     * murni pindah status, tidak ada yang dihitung ulang (LaporanBulanan::isLive()).
     */
    public function finalisasi(): void
    {
        $this->authorize('laporan.manage');
        abort_unless($this->laporan->status === 'DRAFT', 403, 'Laporan ini sudah difinalisasi — buat revisi untuk mengubahnya');
        abort_unless((int) $this->laporan->dibuat_oleh === (int) Auth::id(), 403, 'Anda tidak berwenang memfinalisasi laporan ini');

        $snapshot = $this->laporan->isLive()
            ? app(SusunSnapshotLaporan::class)->susunKelompok($this->laporan->kelompok, $this->laporan->periode)
            : $this->laporan->snapshot_data;

        $this->laporan->update([
            'status' => 'FINAL',
            'snapshot_data' => $snapshot,
            'difinalisasi_oleh' => Auth::id(),
            'difinalisasi_pada' => now(),
        ]);

        activity('laporan-bulanan')->causedBy(Auth::user())->performedOn($this->laporan)->log('Laporan Bulanan difinalisasi');
        $this->dispatch('toast', variant: 'success', message: 'Laporan difinalisasi.');
    }

    public function render()
    {
        return view('livewire.laporan.viewer-laporan-slide');
    }
}
