<?php

namespace App\Livewire\Laporan;

use App\Models\LaporanBulanan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Ditanam di dalam Laporan\ViewerLaporanSlide saat laporan FINAL & user berwenang
 * mereview (bukan halaman/route tersendiri — pola sama Kegiatan\KelolaPenargetanPeserta
 * yang ditanam di KelolaJadwalKegiatan). SRS-Fase-2 §3.1, UCIC-Fase-2 UC-32.
 */
class ApprovalLaporan extends Component
{
    #[Locked]
    public int $laporanId;

    public string $catatan = '';

    public bool $showTolakModal = false;

    public function mount(int $laporanId): void
    {
        $this->laporanId = $laporanId;
    }

    private function laporan(): LaporanBulanan
    {
        return LaporanBulanan::with('kelompok')->findOrFail($this->laporanId);
    }

    private function bolehReview(LaporanBulanan $laporan): bool
    {
        $user = Auth::guard('web')->user();

        if ($laporan->tingkat === 'KELOMPOK') {
            return $user->hasRole('pjp-desa') && (int) $laporan->kelompok?->desa_id === (int) $user->desa_id;
        }

        if ($laporan->tingkat === 'DAERAH') {
            return false;
        }

        return $user->hasRole('admin-daerah');
    }

    /**
     * Laporan tingkat DAERAH tidak pernah melalui approval — Daerah adalah jenjang teratas
     * struktur organisasi (PRD §18.4), tidak ada approver di atasnya (SRS-Fase-2 §3.1/§3.5).
     */
    private function pesanTidakBisaDireview(LaporanBulanan $laporan): string
    {
        return $laporan->tingkat === 'DAERAH'
            ? 'Laporan tingkat Daerah tidak melalui approval — sudah final begitu difinalisasi pembuatnya'
            : 'Anda tidak berwenang mereview laporan ini';
    }

    public function setujui(): void
    {
        $this->authorize('laporan.review');
        $laporan = $this->laporan();
        abort_unless($this->bolehReview($laporan), 403, $this->pesanTidakBisaDireview($laporan));
        abort_unless($laporan->status === 'FINAL', 403, 'Laporan ini belum difinalisasi oleh pembuatnya');

        $laporan->update(['status' => 'DISETUJUI', 'disetujui_oleh' => Auth::id(), 'disetujui_pada' => now()]);

        activity('laporan-bulanan')->causedBy(Auth::user())->performedOn($laporan)->log('Laporan Bulanan disetujui');
        session()->flash('flash_toast', ['variant' => 'success', 'message' => 'Laporan disetujui.']);
        $this->redirect(route('laporan.antrian-approval'), navigate: false);
    }

    public function tolak(): void
    {
        $this->authorize('laporan.review');
        $this->validate(['catatan' => 'required|string'], ['catatan.required' => 'Catatan revisi wajib diisi']);

        $laporan = $this->laporan();
        abort_unless($this->bolehReview($laporan), 403, $this->pesanTidakBisaDireview($laporan));
        abort_unless($laporan->status === 'FINAL', 403, 'Laporan ini belum difinalisasi oleh pembuatnya');

        $laporan->update(['status' => 'REVISI_DIMINTA', 'catatan_revisi' => $this->catatan]);

        activity('laporan-bulanan')->causedBy(Auth::user())->performedOn($laporan)->log('Laporan Bulanan ditolak — revisi diminta');
        session()->flash('flash_toast', ['variant' => 'success', 'message' => 'Laporan dikembalikan untuk revisi.']);
        $this->redirect(route('laporan.antrian-approval'), navigate: false);
    }

    public function render()
    {
        return view('livewire.laporan.approval-laporan');
    }
}
