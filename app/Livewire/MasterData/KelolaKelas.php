<?php

namespace App\Livewire\MasterData;

use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelompok;
use App\Models\RombonganBelajar;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-04 — Kelola Struktur Wilayah (tab Kelas). SRS §6.1, UCIC UC-04.
 *
 * Kelas di sini adalah RombonganBelajar — kelas sungguhan di lapangan, BUKAN tabel
 * `kelas` (junction Kelompok×Jenjang yang dipakai pipeline KBM/presensi, tidak
 * disentuh di sini). Satu RombonganBelajar bisa menggabungkan >1 Jenjang sekaligus
 * (mis. "Kelas ACR A" = Dasar 1 & Dasar 2) karena keterbatasan Guru/MT/MS di
 * lapangan — dipakai untuk pelaporan, bukan target generate Kegiatan.
 */
class KelolaKelas extends Component
{
    use WithPagination;

    #[Url]
    public string $filterNama = '';

    #[Url]
    public string $filterKelompok = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $kelompok_id = null;

    public string $nama = '';

    /** @var array<int> */
    public array $jenjangIds = [];

    public function mount(): void
    {
        $this->authorize('kelas.view');

        // filterKelompok adalah properti #[Url] — nilainya datang mentah dari query
        // string dan bisa diubah manual di browser, lepas dari pilihan yang tersedia di
        // dropdown. Buang kalau di luar scope, supaya tidak menghasilkan kombinasi filter
        // yang kontradiktif dengan terapkanScope() (selalu kosong) dan menyesatkan.
        if ($this->filterKelompok && ! $this->kelompokDalamScope((int) $this->filterKelompok)) {
            $this->filterKelompok = '';
        }
    }

    private function kelompokDalamScope(int $kelompokId): bool
    {
        $query = Kelompok::whereKey($kelompokId);
        $user = Auth::guard('web')->user();

        if (! $user->hasRole('admin-daerah')) {
            if ($user->kelompok_id) {
                $query->where('id', $user->kelompok_id);
            } elseif ($user->desa_id) {
                $query->where('desa_id', $user->desa_id);
            }
        }

        return $query->exists();
    }

    public function updating(string $name): void
    {
        if (str($name)->startsWith('filter')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterNama', 'filterKelompok']);
        $this->resetPage();
    }

    private function terapkanScope($query): void
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            return;
        }

        if ($user->kelompok_id) {
            $query->where('kelompok_id', $user->kelompok_id);
        } elseif ($user->desa_id) {
            $query->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id));
        }
    }

    public function getRombonganBelajarListProperty()
    {
        $query = RombonganBelajar::with(['kelompok', 'jenjangs'])
            ->when($this->filterNama, fn ($q) => $q->where('nama', 'like', '%'.$this->filterNama.'%'))
            ->when($this->filterKelompok, fn ($q) => $q->where('kelompok_id', $this->filterKelompok))
            ->orderBy(Kelompok::select('nama')->whereColumn('kelompok.id', 'rombongan_belajar.kelompok_id'))
            ->orderBy('nama');

        $this->terapkanScope($query);

        return $query->paginate(15);
    }

    public function openCreate(): void
    {
        $this->authorize('kelas.manage');
        $this->reset(['editingId', 'kelompok_id', 'nama', 'jenjangIds']);
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('kelas.manage');

        $query = RombonganBelajar::with('jenjangs')->whereKey($id);
        $this->terapkanScope($query);
        $rombel = $query->firstOrFail();

        $this->editingId = $rombel->id;
        $this->kelompok_id = $rombel->kelompok_id;
        $this->nama = $rombel->nama;
        $this->jenjangIds = $rombel->jenjangs->pluck('id')->all();
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('kelas.manage');

        // kelompok_id adalah properti public wire:model — jangan percaya nilai dari client
        // begitu saja untuk role selain admin-daerah, supaya PJP Kelompok tidak bisa
        // memindahkan/membuat Kelas di kelompok lain lewat request yang dimanipulasi.
        $user = Auth::guard('web')->user();
        if (! $user->hasRole('admin-daerah') && $user->kelompok_id) {
            $this->kelompok_id = $user->kelompok_id;
        }

        $this->validate([
            'kelompok_id' => ['required', 'exists:kelompok,id'],
            'nama' => ['required', 'string', 'max:100'],
            'jenjangIds' => ['required', 'array', 'min:1'],
            'jenjangIds.*' => ['integer', 'exists:jenjang,id'],
        ]);

        $rombel = RombonganBelajar::updateOrCreate(
            ['id' => $this->editingId],
            ['kelompok_id' => $this->kelompok_id, 'nama' => $this->nama]
        );

        $rombel->jenjangs()->sync($this->jenjangIds);

        // Satu Jenjang cuma boleh diwakili 1 Kelas per Kelompok — kalau salah satu Jenjang
        // yang baru dipilih sebelumnya menempel di Kelas lain (mis. sebelum digabung ke sini),
        // lepas dari sana. Tanpa ini, Kelas lama itu tetap "berisi" Jenjang yang sama dan guard
        // Generus di hapus() (dicek per-jenjang, bukan per-Kelas) akan selalu memblokir
        // penghapusannya walau Jenjang itu sudah diwakili Kelas gabungan yang baru.
        if ($this->jenjangIds !== []) {
            RombonganBelajar::where('kelompok_id', $rombel->kelompok_id)
                ->whereKeyNot($rombel->id)
                ->get()
                ->each(fn (RombonganBelajar $lain) => $lain->jenjangs()->detach($this->jenjangIds));
        }

        $this->dispatch('toast', variant: 'success', message: 'Kelas berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function toggleAktif(int $id): void
    {
        $this->authorize('kelas.manage');

        $query = RombonganBelajar::whereKey($id);
        $this->terapkanScope($query);

        $rombel = $query->firstOrFail();
        $rombel->update(['status_aktif' => ! $rombel->status_aktif]);
    }

    public function hapus(int $id): void
    {
        $this->authorize('kelas.manage');

        $query = RombonganBelajar::with('jenjangs')->whereKey($id);
        $this->terapkanScope($query);
        $rombel = $query->firstOrFail();

        $jenjangIds = $rombel->jenjangs->pluck('id');
        $adaGenerus = Generus::whereHas('kelas', fn ($q) => $q->where('kelompok_id', $rombel->kelompok_id))
            ->whereIn('jenjang_id', $jenjangIds)
            ->exists();

        if ($adaGenerus) {
            $this->dispatch('toast', variant: 'danger', message: 'Tidak dapat menghapus — masih ada Generus di Kelas ini.');

            return;
        }

        $rombel->delete();
        $this->dispatch('toast', variant: 'success', message: 'Kelas berhasil dihapus.');
    }

    public function render()
    {
        $user = Auth::guard('web')->user();
        $kelompokQuery = Kelompok::orderBy('nama');

        if (! $user->hasRole('admin-daerah')) {
            if ($user->kelompok_id) {
                $kelompokQuery->where('id', $user->kelompok_id);
            } elseif ($user->desa_id) {
                $kelompokQuery->where('desa_id', $user->desa_id);
            }
        }

        return view('livewire.master-data.kelola-kelas', [
            'kelompokOptions' => $kelompokQuery->pluck('nama', 'id'),
            'jenjangOptions' => Jenjang::orderBy('urutan')->get(['id', 'label']),
        ]);
    }
}
