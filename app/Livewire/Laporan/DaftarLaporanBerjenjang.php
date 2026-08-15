<?php

namespace App\Livewire\Laporan;

use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-LAP-04 — Telusur Laporan Individual Berjenjang (baru, SRS-Fase-2 §3.6, UCIC-Fase-2
 * UC-32). Read-only, scope mengikuti aktor: PJP Desa menelusuri tiap Kelompok di desanya;
 * Admin Daerah menelusuri tiap Desa MAUPUN tiap Kelompok di daerahnya (scope Daerah penuh,
 * satu Daerah per instalasi — PRD §18.4). Berbeda dari AntrianApprovalLaporan (P-LAP-03)
 * yang hanya menampilkan laporan FINAL menunggu review, halaman ini menampilkan seluruh
 * laporan individual apapun statusnya.
 */
#[Layout('layouts.app')]
class DaftarLaporanBerjenjang extends Component
{
    public string $tingkat = 'KELOMPOK';

    public ?int $entitasId = null;

    public function mount(): void
    {
        $this->authorize('laporan.review');

        if ($this->bolehAdminDaerah()) {
            $this->tingkat = 'DESA';
        }
    }

    private function bolehAdminDaerah(): bool
    {
        return Auth::guard('web')->user()->hasRole('admin-daerah');
    }

    /** tingkat &amp; entitasId adalah properti publik Livewire (bisa dimanipulasi lewat request) — validasi ulang di server. */
    public function updatedTingkat(): void
    {
        if (! $this->bolehAdminDaerah() || ! in_array($this->tingkat, ['KELOMPOK', 'DESA'], true)) {
            $this->tingkat = 'KELOMPOK';
        }

        $this->entitasId = null;
    }

    public function updatedEntitasId(): void
    {
        if ($this->entitasId !== null && ! $this->bolehLihatEntitas($this->entitasId)) {
            $this->entitasId = null;
        }
    }

    private function bolehLihatEntitas(int $entitasId): bool
    {
        if ($this->bolehAdminDaerah()) {
            return true;
        }

        if ($this->tingkat !== 'KELOMPOK') {
            return false;
        }

        $desaId = Auth::guard('web')->user()->desa_id;

        return Kelompok::where('id', $entitasId)->where('desa_id', $desaId)->exists();
    }

    /** @return \Illuminate\Support\Collection<int, Kelompok|Desa> */
    public function getEntitasListProperty()
    {
        if ($this->tingkat === 'DESA') {
            return Desa::orderBy('nama')->get();
        }

        $user = Auth::guard('web')->user();

        return Kelompok::when(! $this->bolehAdminDaerah(), fn ($q) => $q->where('desa_id', $user->desa_id))
            ->orderBy('nama')
            ->get();
    }

    public function getDaftarProperty()
    {
        if (! $this->entitasId) {
            return collect();
        }

        $kolom = $this->tingkat === 'DESA' ? 'desa_id' : 'kelompok_id';

        return LaporanBulanan::where('tingkat', $this->tingkat)
            ->where($kolom, $this->entitasId)
            ->orderByDesc('periode')
            ->orderByDesc('versi')
            ->get()
            ->groupBy('periode');
    }

    public function render()
    {
        return view('livewire.laporan.daftar-laporan-berjenjang');
    }
}
