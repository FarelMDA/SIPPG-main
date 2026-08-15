<?php

namespace App\Livewire\Laporan;

use App\Models\LaporanBulanan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-LAP-01 (Kelompok) — Daftar/Riwayat Laporan Bulanan + tombol Generate.
 * SRS-Fase-2 §3, UCIC-Fase-2 UC-31.
 */
#[Layout('layouts.app')]
class GenerateLaporanKelompok extends Component
{
    public string $periode = '';

    public function mount(): void
    {
        $this->authorize('laporan.manage');
        // admin-daerah punya permission laporan.manage secara blanket (RolePermissionSeeder)
        // tapi tidak punya kelompok_id — halaman ini murni untuk PJP Kelompok, bukan mereka.
        abort_unless(Auth::guard('web')->user()->kelompok_id, 403, 'Halaman ini khusus PJP Kelompok');
        $this->periode = now()->format('Y-m');
    }

    private function kelompokId(): int
    {
        return Auth::guard('web')->user()->kelompok_id;
    }

    public function getDaftarProperty()
    {
        return LaporanBulanan::where('kelompok_id', $this->kelompokId())
            ->where('tingkat', 'KELOMPOK')
            ->orderByDesc('periode')
            ->orderByDesc('versi')
            ->get()
            ->groupBy('periode');
    }

    /**
     * Buat/lanjutkan DRAFT untuk $this->periode lalu arahkan ke Viewer (UC-31 generate()).
     * Bila baris terakhir periode ini BUKAN DRAFT (FINAL/DISETUJUI/REVISI_DIMINTA), buat
     * versi baru — mencakup alur revisi pasca-tolak (UCIC UC-32) yang eksplisit meminta
     * "finalisasi ulang sebagai versi baru".
     */
    public function generate(): mixed
    {
        $this->authorize('laporan.manage');

        $terakhir = LaporanBulanan::where('kelompok_id', $this->kelompokId())
            ->where('tingkat', 'KELOMPOK')
            ->where('periode', $this->periode)
            ->orderByDesc('versi')
            ->first();

        if ($terakhir && $terakhir->status === 'DRAFT') {
            return $this->redirect(route('laporan.viewer', $terakhir), navigate: false);
        }

        $laporan = LaporanBulanan::create([
            'kelompok_id' => $this->kelompokId(),
            'tingkat' => 'KELOMPOK',
            'periode' => $this->periode,
            'versi' => $terakhir ? $terakhir->versi + 1 : 1,
            'status' => 'DRAFT',
            'dibuat_oleh' => Auth::id(),
        ]);

        activity('laporan-bulanan')->causedBy(Auth::user())->performedOn($laporan)->log('Laporan Bulanan Kelompok dibuat (draft)');

        return $this->redirect(route('laporan.viewer', $laporan), navigate: false);
    }

    public function render()
    {
        return view('livewire.laporan.generate-laporan-kelompok');
    }
}
