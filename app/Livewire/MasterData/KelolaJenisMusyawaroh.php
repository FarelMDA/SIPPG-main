<?php

namespace App\Livewire\MasterData;

use App\Models\JenisMusyawaroh;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Kelola Jenis Musyawaroh — master data pengganti ENUM `musyawaroh.jenis`.
 * Hanya Admin Daerah/sysadmin (jenis-musyawaroh.manage) — role di bawahnya hanya
 * memilih dari daftar ini lewat KelolaMusyawaroh, pola sama KelolaJenisKegiatan.
 */
#[Layout('layouts.app')]
class KelolaJenisMusyawaroh extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $tingkat = 'KELOMPOK';

    public ?int $urutan = null;

    public bool $perlu_jumlah_hadir = false;

    public function mount(): void
    {
        $this->authorize('jenis-musyawaroh.manage');
    }

    public function getDaftarProperty()
    {
        return JenisMusyawaroh::orderBy('tingkat')->orderBy('urutan')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nama', 'tingkat', 'urutan', 'perlu_jumlah_hadir']);
        $this->urutan = (int) JenisMusyawaroh::where('tingkat', $this->tingkat)->max('urutan') + 1;
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $jenisMusyawaroh = JenisMusyawaroh::findOrFail($id);

        $this->editingId = $jenisMusyawaroh->id;
        $this->nama = $jenisMusyawaroh->nama;
        $this->tingkat = $jenisMusyawaroh->tingkat;
        $this->urutan = $jenisMusyawaroh->urutan;
        $this->perlu_jumlah_hadir = $jenisMusyawaroh->perlu_jumlah_hadir;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('jenis-musyawaroh.manage');

        $this->validate([
            'nama' => [
                'required', 'string', 'max:255',
                'unique:jenis_musyawaroh,nama,'.$this->editingId.',id,tingkat,'.$this->tingkat,
            ],
            'tingkat' => ['required', 'in:KELOMPOK,DESA,DAERAH'],
            'urutan' => ['required', 'integer', 'min:1'],
            'perlu_jumlah_hadir' => ['boolean'],
        ]);

        $jenisMusyawaroh = JenisMusyawaroh::updateOrCreate(
            ['id' => $this->editingId],
            [
                'nama' => $this->nama,
                'tingkat' => $this->tingkat,
                'urutan' => $this->urutan,
                'perlu_jumlah_hadir' => $this->perlu_jumlah_hadir,
            ]
        );

        activity('jenis-musyawaroh')->causedBy(Auth::guard('web')->user())->performedOn($jenisMusyawaroh)->log('Jenis Musyawaroh disimpan');

        $this->dispatch('toast', variant: 'success', message: 'Jenis Musyawaroh berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function hapus(int $id): void
    {
        $this->authorize('jenis-musyawaroh.manage');

        $jenisMusyawaroh = JenisMusyawaroh::withCount('musyawaroh')->findOrFail($id);

        if ($jenisMusyawaroh->musyawaroh_count > 0) {
            $this->dispatch('toast', variant: 'danger', message: 'Tidak dapat menghapus — masih dipakai oleh Musyawaroh.');

            return;
        }

        $jenisMusyawaroh->delete();

        activity('jenis-musyawaroh')->causedBy(Auth::guard('web')->user())->log('Jenis Musyawaroh dihapus');
        $this->dispatch('toast', variant: 'success', message: 'Jenis Musyawaroh berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.master-data.kelola-jenis-musyawaroh');
    }
}
