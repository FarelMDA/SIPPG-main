<?php

namespace App\Livewire\Kegiatan;

use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-21 — Daftar Kegiatan. SRS §18.1, UCIC UC-21. Tambah/Ubah ada di halaman
 * tersendiri (bukan modal) — lihat Kegiatan\FormKegiatan.
 */
#[Layout('layouts.app')]
class KelolaKegiatan extends Component
{
    public string $filterTingkat = '';

    public string $filterStatus = '';

    public function mount(): void
    {
        $this->authorize('kegiatan.view');

        $user = Auth::guard('web')->user();
        $this->filterTingkat = match (true) {
            $user->hasRole('pjp-kelompok') => 'KELOMPOK',
            $user->hasRole('pjp-desa') => 'DESA',
            default => '',
        };
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

    public function getDaftarProperty()
    {
        $user = Auth::guard('web')->user();

        return Kegiatan::query()
            ->with('jenisKegiatan')
            ->when($this->filterTingkat, fn ($q) => $q->where('tingkat', $this->filterTingkat))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            // Non-admin-daerah hanya boleh melihat kegiatan yang diselenggarakan di lokasinya
            // sendiri (Kelompok/Desa-nya) — kegiatan tingkat Daerah tetap terlihat semua.
            ->when(! $user->hasRole('admin-daerah'), function ($q) use ($user) {
                $desaId = $user->desa_id ?? $user->kelompok?->desa_id;

                $q->where(function ($q2) use ($user, $desaId) {
                    $q2->where('tingkat', 'DAERAH')
                        ->when($user->kelompok_id, fn ($q3) => $q3->orWhere(
                            fn ($q4) => $q4->where('tingkat', 'KELOMPOK')->where('penyelenggara_id', $user->kelompok_id)
                        ))
                        ->when($desaId, fn ($q3) => $q3->orWhere(
                            fn ($q4) => $q4->where('tingkat', 'DESA')->where('penyelenggara_id', $desaId)
                        ));
                });
            })
            ->orderByDesc('tanggal')
            ->get();
    }

    public function tandaiStatus(int $id, string $status): void
    {
        $this->authorize('kegiatan.manage');

        if (! in_array($status, ['TERJADWAL', 'TERLAKSANA', 'TIDAK_TERLAKSANA'], true)) {
            return;
        }

        $user = Auth::guard('web')->user();
        $kegiatanQuery = Kegiatan::query();

        // id adalah argumen method Livewire — pastikan kegiatan yang diubah statusnya memang
        // diselenggarakan oleh level/entitas acting user, bukan disisipkan lewat request lain.
        if (! $user->hasRole('admin-daerah')) {
            $tingkat = $this->tingkatSaya();

            [$penyelenggaraType, $penyelenggaraId] = match ($tingkat) {
                'KELOMPOK' => ['kelompok', $user->kelompok_id],
                'DESA' => ['desa', $user->desa_id],
                default => [null, null],
            };

            $kegiatanQuery->where('tingkat', $tingkat)
                ->where('penyelenggara_type', $penyelenggaraType)
                ->where('penyelenggara_id', $penyelenggaraId);
        }

        $kegiatan = $kegiatanQuery->findOrFail($id);
        $kegiatan->update(['status' => $status]);

        activity('kegiatan')->causedBy(Auth::guard('web')->user())->performedOn($kegiatan)->log('Status Kegiatan diperbarui');
    }

    public function render()
    {
        return view('livewire.kegiatan.kelola-kegiatan', [
            'jenisOptions' => JenisKegiatan::options(),
        ]);
    }
}
