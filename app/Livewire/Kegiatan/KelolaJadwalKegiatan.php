<?php

namespace App\Livewire\Kegiatan;

use App\Models\Daerah;
use App\Models\KegiatanJadwal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-28 — Daftar Jadwal Kegiatan Berulang. SRS-Fase-2 §2.2, UCIC-Fase-2 UC-28.
 * Tambah/Ubah ada di halaman tersendiri (bukan modal) — lihat Kegiatan\FormJadwalKegiatan.
 */
#[Layout('layouts.app')]
class KelolaJadwalKegiatan extends Component
{
    public string $filterStatus = '';

    public function mount(): void
    {
        $this->authorize('kegiatan-jadwal.manage');
    }

    private function tingkatSaya(): ?string
    {
        $user = Auth::guard('web')->user();

        return match (true) {
            $user->hasRole('admin-daerah') => 'DAERAH',
            $user->hasRole('pjp-desa') => 'DESA',
            $user->hasRole('pjp-kelompok') => 'KELOMPOK',
            default => null,
        };
    }

    private function penyelenggaraSaya(): array
    {
        $user = Auth::guard('web')->user();

        return match ($this->tingkatSaya()) {
            'KELOMPOK' => ['kelompok', $user->kelompok_id],
            'DESA' => ['desa', $user->desa_id],
            'DAERAH' => ['daerah', Daerah::value('id')],
            default => [null, null],
        };
    }

    public function getDaftarProperty()
    {
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggaraSaya();
        $user = Auth::guard('web')->user();

        return KegiatanJadwal::query()
            ->when(! $user->hasRole('admin-daerah'), fn ($q) => $q->where('penyelenggara_type', $penyelenggaraType)->where('penyelenggara_id', $penyelenggaraId))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->withCount('kegiatan')
            ->orderByDesc('created_at')
            ->get();
    }

    private function jadwalDalamCakupan()
    {
        $user = Auth::guard('web')->user();
        $query = KegiatanJadwal::query();

        if (! $user->hasRole('admin-daerah')) {
            [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggaraSaya();
            $query->where('penyelenggara_type', $penyelenggaraType)->where('penyelenggara_id', $penyelenggaraId);
        }

        return $query;
    }

    public function nonaktifkan(int $id): void
    {
        $this->authorize('kegiatan-jadwal.manage');

        $jadwal = $this->jadwalDalamCakupan()->findOrFail($id);
        $jadwal->update(['status' => 'NONAKTIF']);

        activity('kegiatan-jadwal')->causedBy(Auth::guard('web')->user())->performedOn($jadwal)->log('Jadwal Kegiatan Berulang dinonaktifkan');
        $this->dispatch('toast', variant: 'success', message: 'Jadwal Kegiatan Berulang dinonaktifkan.');
    }

    public function render()
    {
        return view('livewire.kegiatan.kelola-jadwal-kegiatan');
    }
}
