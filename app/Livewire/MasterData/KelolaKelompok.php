<?php

namespace App\Livewire\MasterData;

use App\Models\Desa;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelompok;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-04 — Kelola Struktur Wilayah (tab Kelompok). SRS §6.1, UCIC UC-04.
 *
 * PJP Desa hanya melihat (struktur-organisasi.view) daftar Kelompok di desanya sendiri —
 * fitur agregasi Desa penuh baru Fase 2 (SRS §1.1). Tombol tambah/edit/hapus
 * disembunyikan untuk role selain admin-daerah (permission struktur-organisasi.manage).
 */
class KelolaKelompok extends Component
{
    use WithPagination;

    #[Url]
    public string $filterNama = '';

    #[Url]
    public string $filterDesa = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $desa_id = null;

    public string $nama = '';

    public string $alamat = '';

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
        $this->reset(['filterNama', 'filterDesa']);
        $this->resetPage();
    }

    public function getKelompokListProperty()
    {
        $query = Kelompok::with('desa')
            ->when($this->filterNama, fn ($q) => $q->where('nama', 'like', '%'.$this->filterNama.'%'))
            ->when($this->filterDesa, fn ($q) => $q->where('desa_id', $this->filterDesa))
            ->orderBy('nama');

        $user = Auth::guard('web')->user();

        if ($user->hasRole('pjp-desa')) {
            $query->where('desa_id', $user->desa_id);
        }

        return $query->paginate(15);
    }

    public function openCreate(): void
    {
        $this->authorize('struktur-organisasi.manage');
        $this->reset(['editingId', 'desa_id', 'nama', 'alamat']);
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('struktur-organisasi.manage');
        $kelompok = Kelompok::findOrFail($id);
        $this->editingId = $kelompok->id;
        $this->desa_id = $kelompok->desa_id;
        $this->nama = $kelompok->nama;
        $this->alamat = (string) $kelompok->alamat;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('struktur-organisasi.manage');

        $this->validate([
            'desa_id' => ['required', 'exists:desa,id'],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
        ]);

        $kelompok = Kelompok::updateOrCreate(
            ['id' => $this->editingId],
            ['desa_id' => $this->desa_id, 'nama' => $this->nama, 'alamat' => $this->alamat]
        );

        if (! $this->editingId) {
            $jenjangs = Jenjang::orderBy('urutan')->get(['id', 'label']);

            // Kelas dibuat otomatis per Jenjang — admin tidak perlu mengetik ulang
            // Nama Kelas/Jenjang secara manual untuk tiap Kelompok baru.
            $kelompok->kelas()->createMany(
                $jenjangs->map(fn ($jenjang) => ['jenjang_id' => $jenjang->id, 'status_aktif' => true])->all()
            );

            // RombonganBelajar (Kelas sungguhan) juga dibuat otomatis per Jenjang sebagai
            // starting point — admin bisa gabungkan beberapa Jenjang jadi satu Rombel
            // (mis. Kelas ACR A = Dasar 1 & 2) belakangan lewat tab Kelas.
            foreach ($jenjangs as $jenjang) {
                $rombel = $kelompok->rombonganBelajar()->create(['nama' => $jenjang->label, 'status_aktif' => true]);
                $rombel->jenjangs()->attach($jenjang->id);
            }
        }

        $this->dispatch('toast', variant: 'success', message: 'Kelompok berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function hapus(int $id): void
    {
        $this->authorize('struktur-organisasi.manage');

        $kelompok = Kelompok::findOrFail($id);

        // Kelas SELALU ada (auto-provision per Jenjang — lihat simpan()), jadi bukan
        // indikator "kelompok ini masih dipakai" lagi. Cek dependent asli: Pendidik &
        // Generus (via Kelas). Kalau kosong, sekalian hapus Kelas-nya juga supaya tidak
        // ada baris "yatim" di bawah Kelompok yang sudah terhapus.
        $adaPendidik = $kelompok->pendidik()->exists();
        $adaGenerus = Generus::whereHas('kelas', fn ($q) => $q->where('kelompok_id', $kelompok->id))->exists();

        if ($adaPendidik || $adaGenerus) {
            $this->dispatch('toast', variant: 'danger', message: 'Tidak dapat menghapus — masih ada Pendidik/Generus di kelompok ini.');

            return;
        }

        $kelompok->kelas()->delete();
        $kelompok->rombonganBelajar()->delete();
        $kelompok->delete();
        $this->dispatch('toast', variant: 'success', message: 'Kelompok berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.master-data.kelola-kelompok', [
            'desaOptions' => Desa::orderBy('nama')->pluck('nama', 'id'),
        ]);
    }
}
