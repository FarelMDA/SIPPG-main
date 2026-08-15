<?php

namespace App\Livewire\MasterData;

use Livewire\Component;

/**
 * Komponen CRUD generik untuk tabel referensi kecil berbentuk kode+label+urutan
 * (Jenjang, Jenis Kelamin, Status Domisili, Jenis Pendidik, Status Presensi) —
 * satu komponen dipakai ulang lewat 5 tab di halaman Referensi (lihat
 * resources/views/master-data/referensi.blade.php), supaya tidak duplikasi
 * boilerplate CRUD 5×. Hanya Create/Edit — sengaja TIDAK ada Hapus, karena kolom
 * konsumen (mis. generus.jenis_kelamin) soft-reference by kode tanpa foreign key,
 * jadi hapus baris referensi bisa mengorbankan data yang sudah ada tanpa terdeteksi.
 */
class KelolaReferensi extends Component
{
    public string $model;

    public string $judul;

    public bool $withKategoriUsia = false;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $kode = '';

    public string $label = '';

    public ?int $urutan = null;

    public string $kategori_usia = '';

    public function mount(string $model, string $judul, bool $withKategoriUsia = false): void
    {
        $this->authorize('referensi.manage');
        $this->model = $model;
        $this->judul = $judul;
        $this->withKategoriUsia = $withKategoriUsia;
    }

    public function getDaftarProperty()
    {
        return ($this->model)::orderBy('urutan')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'kode', 'label', 'urutan', 'kategori_usia']);
        $this->urutan = (int) ($this->model)::max('urutan') + 1;
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $baris = ($this->model)::findOrFail($id);
        $this->editingId = $baris->id;
        $this->kode = $baris->kode;
        $this->label = $baris->label;
        $this->urutan = $baris->urutan;
        $this->kategori_usia = $this->withKategoriUsia ? $baris->kategori_usia : '';
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $table = ($this->model)::make()->getTable();

        $this->validate([
            'kode' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:'.$table.',kode,'.$this->editingId],
            'label' => ['required', 'string', 'max:50'],
            'urutan' => ['required', 'integer', 'min:1'],
            'kategori_usia' => [$this->withKategoriUsia ? 'required' : 'nullable', 'string', 'max:10'],
        ]);

        $data = ['kode' => $this->kode, 'label' => $this->label, 'urutan' => $this->urutan];

        if ($this->withKategoriUsia) {
            $data['kategori_usia'] = $this->kategori_usia;
        }

        ($this->model)::updateOrCreate(['id' => $this->editingId], $data);

        $this->dispatch('toast', variant: 'success', message: $this->judul.' berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function render()
    {
        return view('livewire.master-data.kelola-referensi');
    }
}
