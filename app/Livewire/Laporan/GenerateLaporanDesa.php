<?php

namespace App\Livewire\Laporan;

use App\Models\LaporanBulanan;
use App\Services\Laporan\AgregasiLaporanDesa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-LAP-01 (Desa) — Daftar/Riwayat Laporan Bulanan Desa + tombol Generate (agregasi).
 * SRS-Fase-2 §3.4, UCIC-Fase-2 UC-32.
 */
#[Layout('layouts.app')]
class GenerateLaporanDesa extends Component
{
    public string $periode = '';

    public function mount(): void
    {
        $this->authorize('laporan.manage');
        // admin-daerah punya permission laporan.manage secara blanket (RolePermissionSeeder)
        // tapi tidak punya desa_id — halaman ini murni untuk PJP Desa, bukan mereka.
        abort_unless(Auth::guard('web')->user()->desa_id, 403, 'Halaman ini khusus PJP Desa');
        $this->periode = now()->format('Y-m');
    }

    private function desaId(): int
    {
        return Auth::guard('web')->user()->desa_id;
    }

    public function getDaftarProperty()
    {
        return LaporanBulanan::where('desa_id', $this->desaId())
            ->where('tingkat', 'DESA')
            ->orderByDesc('periode')
            ->orderByDesc('versi')
            ->get()
            ->groupBy('periode');
    }

    /**
     * Agregasi otomatis dari laporan Kelompok FINAL/DISETUJUI di Desa ini (SRS §3.4).
     * Regenerasi (dipanggil lagi meski sudah ada baris untuk periode ini) SELALU membuat
     * draft baru, tidak pernah menimpa draft yang ada — PJP Desa yang menentukan kapan
     * generate ulang (SRS §3.4 Aturan Bisnis).
     */
    public function generate(): mixed
    {
        $this->authorize('laporan.manage');

        $desa = Auth::guard('web')->user()->desa;
        $hasil = app(AgregasiLaporanDesa::class)->agregasi($desa, $this->periode);

        $versiTerakhir = LaporanBulanan::where('desa_id', $this->desaId())
            ->where('tingkat', 'DESA')
            ->where('periode', $this->periode)
            ->max('versi');

        $laporan = LaporanBulanan::create([
            'desa_id' => $this->desaId(),
            'tingkat' => 'DESA',
            'periode' => $this->periode,
            'versi' => $versiTerakhir ? $versiTerakhir + 1 : 1,
            'status' => 'DRAFT',
            'snapshot_data' => $hasil->snapshot,
            'dibuat_oleh' => Auth::id(),
        ]);

        activity('laporan-bulanan')->causedBy(Auth::user())->performedOn($laporan)->log('Laporan Bulanan Desa dibuat (agregasi)');

        if ($hasil->adaYangBelumFinal()) {
            $jumlahBelumFinal = $hasil->jumlahKelompokTotal - $hasil->jumlahKelompokFinal;

            session()->flash('flash_toast', [
                'variant' => 'warning',
                'message' => "{$jumlahBelumFinal} dari {$hasil->jumlahKelompokTotal} Kelompok belum finalisasi laporan periode ini: ".implode(', ', $hasil->kelompokBelumFinal),
            ]);
        }

        return $this->redirect(route('laporan.viewer', $laporan), navigate: false);
    }

    public function render()
    {
        return view('livewire.laporan.generate-laporan-desa');
    }
}
