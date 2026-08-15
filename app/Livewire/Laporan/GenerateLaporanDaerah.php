<?php

namespace App\Livewire\Laporan;

use App\Models\Daerah;
use App\Models\LaporanBulanan;
use App\Services\Laporan\AgregasiLaporanDaerah;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-LAP-01 (Daerah) — Daftar/Riwayat Laporan Bulanan Daerah + tombol Generate (agregasi).
 * SRS-Fase-2 §3.5, UCIC-Fase-2 UC-32 — permintaan fitur baru, dimajukan dari rencana awal
 * Fase 4. Tidak ada scope entitas (satu Daerah per instalasi, PRD §18.4) — beda dari
 * GenerateLaporanKelompok/Desa yang men-scope ke kelompok_id/desa_id user.
 */
#[Layout('layouts.app')]
class GenerateLaporanDaerah extends Component
{
    public string $periode = '';

    public function mount(): void
    {
        $this->authorize('laporan.manage');
        abort_unless(Auth::guard('web')->user()->hasRole('admin-daerah'), 403, 'Halaman ini khusus Admin Daerah');
        $this->periode = now()->format('Y-m');
    }

    public function getDaftarProperty()
    {
        return LaporanBulanan::where('tingkat', 'DAERAH')
            ->orderByDesc('periode')
            ->orderByDesc('versi')
            ->get()
            ->groupBy('periode');
    }

    /**
     * Agregasi otomatis dari laporan Desa FINAL/DISETUJUI (SRS §3.5). Regenerasi (dipanggil
     * lagi meski sudah ada baris untuk periode ini) SELALU membuat draft baru, tidak pernah
     * menimpa draft yang ada — pola sama GenerateLaporanDesa::generate() (SRS §3.5 Aturan
     * Bisnis, sama seperti §3.4).
     */
    public function generate(): mixed
    {
        $this->authorize('laporan.manage');

        $daerah = Daerah::first();
        $hasil = app(AgregasiLaporanDaerah::class)->agregasi($daerah, $this->periode);

        $versiTerakhir = LaporanBulanan::where('tingkat', 'DAERAH')
            ->where('periode', $this->periode)
            ->max('versi');

        $laporan = LaporanBulanan::create([
            'daerah_id' => $daerah->id,
            'tingkat' => 'DAERAH',
            'periode' => $this->periode,
            'versi' => $versiTerakhir ? $versiTerakhir + 1 : 1,
            'status' => 'DRAFT',
            'snapshot_data' => $hasil->snapshot,
            'dibuat_oleh' => Auth::id(),
        ]);

        activity('laporan-bulanan')->causedBy(Auth::user())->performedOn($laporan)->log('Laporan Bulanan Daerah dibuat (agregasi)');

        if ($hasil->adaYangBelumFinal()) {
            $jumlahBelumFinal = $hasil->jumlahDesaTotal - $hasil->jumlahDesaFinal;

            session()->flash('flash_toast', [
                'variant' => 'warning',
                'message' => "{$jumlahBelumFinal} dari {$hasil->jumlahDesaTotal} Desa belum finalisasi laporan periode ini: ".implode(', ', $hasil->desaBelumFinal),
            ]);
        }

        return $this->redirect(route('laporan.viewer', $laporan), navigate: false);
    }

    public function render()
    {
        return view('livewire.laporan.generate-laporan-daerah');
    }
}
