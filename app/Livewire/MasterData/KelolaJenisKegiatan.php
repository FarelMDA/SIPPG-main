<?php

namespace App\Livewire\MasterData;

use App\Models\JenisKegiatan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Kelola Jenis Kegiatan — master data pengganti ENUM `kegiatan.jenis`/`kegiatan_jadwal.jenis`.
 * Hanya Admin Daerah/sysadmin (jenis-kegiatan.manage) — role di bawahnya hanya memilih
 * dari daftar ini lewat FormKegiatan/FormJadwalKegiatan, pola sama KelolaHariLibur.
 */
#[Layout('layouts.app')]
class KelolaJenisKegiatan extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public function mount(): void
    {
        $this->authorize('jenis-kegiatan.manage');
    }

    public function getDaftarProperty()
    {
        return JenisKegiatan::orderBy('nama')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nama']);
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $jenisKegiatan = JenisKegiatan::findOrFail($id);

        $this->editingId = $jenisKegiatan->id;
        $this->nama = $jenisKegiatan->nama;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('jenis-kegiatan.manage');

        $this->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:jenis_kegiatan,nama,'.$this->editingId],
        ]);

        $jenisKegiatan = JenisKegiatan::updateOrCreate(
            ['id' => $this->editingId],
            ['nama' => $this->nama]
        );

        activity('jenis-kegiatan')->causedBy(Auth::guard('web')->user())->performedOn($jenisKegiatan)->log('Jenis Kegiatan disimpan');

        $this->dispatch('toast', variant: 'success', message: 'Jenis Kegiatan berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function hapus(int $id): void
    {
        $this->authorize('jenis-kegiatan.manage');

        $jenisKegiatan = JenisKegiatan::withCount(['kegiatan', 'kegiatanJadwal'])->findOrFail($id);

        if ($jenisKegiatan->kegiatan_count > 0 || $jenisKegiatan->kegiatan_jadwal_count > 0) {
            $this->dispatch('toast', variant: 'danger', message: 'Tidak dapat menghapus — masih dipakai oleh Kegiatan/Jadwal Kegiatan.');

            return;
        }

        $jenisKegiatan->delete();

        activity('jenis-kegiatan')->causedBy(Auth::guard('web')->user())->log('Jenis Kegiatan dihapus');
        $this->dispatch('toast', variant: 'success', message: 'Jenis Kegiatan berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.master-data.kelola-jenis-kegiatan');
    }
}
