<?php

namespace App\Livewire\Laporan;

use App\Models\LaporanBulanan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * P-LAP-03 — Antrian Approval Laporan. SRS-Fase-2 §3, UCIC-Fase-2 UC-32.
 * PJP Desa mereview laporan Kelompok di desanya; Admin Daerah mereview laporan Desa.
 */
#[Layout('layouts.app')]
class AntrianApprovalLaporan extends Component
{
    public function mount(): void
    {
        $this->authorize('laporan.review');
    }

    public function getAntrianProperty()
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            return LaporanBulanan::where('tingkat', 'DESA')
                ->where('status', 'FINAL')
                ->with('desa')
                ->orderBy('difinalisasi_pada')
                ->get();
        }

        return LaporanBulanan::where('tingkat', 'KELOMPOK')
            ->where('status', 'FINAL')
            ->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id))
            ->with('kelompok')
            ->orderBy('difinalisasi_pada')
            ->get();
    }

    public function render()
    {
        return view('livewire.laporan.antrian-approval-laporan');
    }
}
