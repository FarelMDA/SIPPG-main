<?php

namespace App\Livewire\MasterData;

use App\Models\Desa;
use App\Models\JenisPendidik;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\Pendidik;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-06 — Kelola Data Pendidik. SRS §6.3, UCIC UC-06.
 *
 * Admin Daerah: pilih Desa lalu Kelompok bebas (lintas-Desa).
 * PJP Desa: Desa tetap (miliknya), pilih Kelompok bebas dalam Desa tsb (§4.2).
 * PJP Kelompok: kelompok_id otomatis dari akunnya, field Desa/Kelompok disembunyikan.
 */
#[Layout('layouts.app')]
class KelolaPendidik extends Component
{
    use WithPagination;

    #[Url]
    public string $filterNama = '';

    #[Url]
    public string $filterJenis = '';

    #[Url]
    public string $filterKelompok = '';

    #[Url]
    public string $filterDesa = '';

    #[Url]
    public string $filterKelas = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $jenis = '';

    public ?int $desa_id = null;

    public ?int $kelompok_id = null;

    public array $kelas_ids = [];

    /**
     * Admin Daerah & Bidang Tendik punya scope identik untuk modul ini — Daerah-wide,
     * bebas pilih Kelompok mana pun lewat cascading Desa→Kelompok (SRS §6.3 UC-06 Aturan
     * Bisnis: "Bidang Tendik memiliki scope identik Admin Daerah untuk modul ini").
     */
    private function bisaPilihKelompokUntuk($user): bool
    {
        return $user->hasRole('admin-daerah') || $user->hasRole('bidang-tendik') || $user->hasRole('pjp-desa');
    }

    private function bisaPilihDesaUntuk($user): bool
    {
        return $user->hasRole('admin-daerah') || $user->hasRole('bidang-tendik');
    }

    public function mount(): void
    {
        $this->authorize('pendidik.manage');

        $user = Auth::guard('web')->user();

        if ($user->hasRole('pjp-desa')) {
            $this->desa_id = $user->desa_id;
        }
    }

    public function updatedDesaId(): void
    {
        $this->kelompok_id = null;
        $this->kelas_ids = [];
    }

    public function updatedKelompokId(): void
    {
        $this->kelas_ids = [];
    }

    public function updating(string $name): void
    {
        if (str($name)->startsWith('filter')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterNama', 'filterJenis', 'filterKelompok', 'filterDesa', 'filterKelas']);
        $this->resetPage();
    }

    public function getPendidikListProperty()
    {
        return Pendidik::with(['kelas', 'kelompok.desa'])
            ->when($this->filterNama, fn ($q) => $q->where('nama', 'like', '%'.$this->filterNama.'%'))
            ->when($this->filterJenis, fn ($q) => $q->where('jenis', $this->filterJenis))
            ->when($this->filterKelompok, fn ($q) => $q->where('kelompok_id', $this->filterKelompok))
            ->when($this->filterDesa, fn ($q) => $q->whereHas('kelompok', fn ($k) => $k->where('desa_id', $this->filterDesa)))
            ->when($this->filterKelas, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('kelas.id', $this->filterKelas)))
            ->orderBy('nama')
            ->paginate(15);
    }

    public function openCreate(): void
    {
        $user = Auth::guard('web')->user();

        $this->reset(['editingId', 'nama', 'kelompok_id', 'kelas_ids']);
        $this->jenis = JenisPendidik::options()->keys()->first() ?? '';
        $this->desa_id = $user->hasRole('pjp-desa') ? $user->desa_id : null;
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $pendidik = Pendidik::with('kelas')->findOrFail($id);
        $this->editingId = $pendidik->id;
        $this->nama = $pendidik->nama;
        $this->jenis = $pendidik->jenis;
        $this->desa_id = $pendidik->kelompok?->desa_id;
        $this->kelompok_id = $pendidik->kelompok_id;
        $this->kelas_ids = $pendidik->kelas->pluck('id')->toArray();
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(JenisPendidik::pluck('kode'))],
            'kelas_ids' => ['array'],
        ]);

        $user = Auth::guard('web')->user();

        if ($this->bisaPilihKelompokUntuk($user)) {
            $this->validate(['kelompok_id' => ['required', 'exists:kelompok,id']]);

            if ($user->hasRole('pjp-desa') && Kelompok::whereKey($this->kelompok_id)->where('desa_id', $user->desa_id)->doesntExist()) {
                $this->addError('kelompok_id', 'Kelompok tidak berada di Desa Anda.');

                return;
            }

            $kelompokId = $this->kelompok_id;
        } else {
            $kelompokId = $user->kelompok_id;
        }

        $pendidik = Pendidik::updateOrCreate(
            ['id' => $this->editingId],
            ['nama' => $this->nama, 'jenis' => $this->jenis, 'kelompok_id' => $kelompokId]
        );

        // kelas_ids adalah properti publik Livewire — pastikan tiap kelas benar-benar milik
        // kelompok pendidik ini, jangan sampai mengaitkan guru ke kelas kelompok lain.
        $kelasValid = Kelas::where('kelompok_id', $kelompokId)->whereIn('id', $this->kelas_ids)->pluck('id');

        $pendidik->kelas()->sync($kelasValid);

        activity('pendidik')->causedBy($user)->performedOn($pendidik)->log('Data Pendidik disimpan');
        $this->dispatch('toast', variant: 'success', message: 'Data Pendidik berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function render()
    {
        $user = Auth::guard('web')->user();
        $bisaPilihKelompok = $this->bisaPilihKelompokUntuk($user);
        $bisaPilihDesa = $this->bisaPilihDesaUntuk($user);
        $daerahWide = $user->hasRole('admin-daerah') || $user->hasRole('bidang-tendik');

        $desaOptions = $bisaPilihDesa ? Desa::orderBy('nama')->pluck('nama', 'id') : collect();

        $kelompokOptions = match (true) {
            $daerahWide => $this->desa_id
                ? Kelompok::where('desa_id', $this->desa_id)->orderBy('nama')->pluck('nama', 'id')
                : collect(),
            $user->hasRole('pjp-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->pluck('nama', 'id'),
            default => collect(),
        };

        $kelasQuery = Kelas::urutJenjang();

        if ($bisaPilihKelompok) {
            $kelasQuery->where('kelompok_id', $this->kelompok_id ?? 0);
        } elseif ($user->kelompok_id) {
            $kelasQuery->where('kelompok_id', $user->kelompok_id);
        }

        // Opsi filter tabel (bukan form) — scope penuh sesuai peran, lepas dari state cascading form di atas.
        $filterKelompokOptions = match (true) {
            $daerahWide => Kelompok::orderBy('nama')->pluck('nama', 'id'),
            $user->hasRole('pjp-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->pluck('nama', 'id'),
            default => collect(),
        };

        $filterDesaOptions = match (true) {
            $daerahWide => Desa::orderBy('nama')->pluck('nama', 'id'),
            $user->hasRole('pjp-desa') => Desa::whereKey($user->desa_id)->pluck('nama', 'id'),
            default => collect(),
        };

        $filterKelasQuery = Kelas::urutJenjang();

        if ($user->hasRole('pjp-desa')) {
            $filterKelasQuery->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id));
        } elseif (! $daerahWide && $user->kelompok_id) {
            $filterKelasQuery->where('kelompok_id', $user->kelompok_id);
        }

        return view('livewire.master-data.kelola-pendidik', [
            'jenisPendidikOptions' => JenisPendidik::options(),
            'bisaPilihKelompok' => $bisaPilihKelompok,
            'bisaPilihDesa' => $bisaPilihDesa,
            'desaOptions' => $desaOptions,
            'kelompokOptions' => $kelompokOptions,
            'kelasOptions' => $kelasQuery->get(),
            'filterKelompokOptions' => $filterKelompokOptions,
            'filterDesaOptions' => $filterDesaOptions,
            'filterKelasOptions' => $filterKelasQuery->get(),
        ]);
    }
}
