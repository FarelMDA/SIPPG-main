<?php

namespace App\Livewire\MasterData;

use App\Models\Daerah;
use App\Models\Desa;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-04 — Kelola Struktur Wilayah (tab Desa). SRS §6.1, UCIC UC-04.
 */
class KelolaDesa extends Component
{
    use WithPagination;

    #[Url]
    public string $filterNama = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public function mount(): void
    {
        $this->authorize('struktur-organisasi.view');
    }

    public function updating(string $name): void
    {
        if (str($name)->startsWith('filter')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterNama']);
        $this->resetPage();
    }

    public function getDesaListProperty()
    {
        return Desa::when($this->filterNama, fn ($q) => $q->where('nama', 'like', '%'.$this->filterNama.'%'))
            ->orderBy('nama')
            ->paginate(15);
    }

    public function openCreate(): void
    {
        $this->authorize('struktur-organisasi.manage');
        $this->reset(['editingId', 'nama']);
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('struktur-organisasi.manage');
        $desa = Desa::findOrFail($id);
        $this->editingId = $desa->id;
        $this->nama = $desa->nama;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('struktur-organisasi.manage');
        $this->validate(['nama' => ['required', 'string', 'max:255']]);

        if ($this->editingId) {
            Desa::findOrFail($this->editingId)->update(['nama' => $this->nama]);
        } else {
            $daerah = Daerah::first();
            Desa::create(['daerah_id' => $daerah->id, 'nama' => $this->nama]);
        }

        $this->dispatch('toast', variant: 'success', message: 'Desa berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function hapus(int $id): void
    {
        $this->authorize('struktur-organisasi.manage');
        $desa = Desa::withCount('kelompok')->findOrFail($id);

        if ($desa->kelompok_count > 0) {
            $this->dispatch('toast', variant: 'danger', message: 'Tidak dapat menghapus — masih ada Kelompok di desa ini.');

            return;
        }

        $desa->delete();
        $this->dispatch('toast', variant: 'success', message: 'Desa berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.master-data.kelola-desa');
    }
}
