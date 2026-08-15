<?php

namespace App\Livewire\MasterData;

use App\Events\GenerusDisimpan;
use App\Models\Desa;
use App\Models\Generus;
use App\Models\GenerusKelasHistory;
use App\Models\GenerusStatusHistory;
use App\Models\JenisKelamin;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\StatusDomisili;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-05 — Kelola Data Generus. SRS §6.2, UCIC UC-05.
 */
#[Layout('layouts.app')]
class KelolaGenerus extends Component
{
    use WithPagination;

    #[Url]
    public string $filterNama = '';

    #[Url]
    public string $filterKelompok = '';

    #[Url]
    public string $filterStatusDomisili = '';

    #[Url]
    public string $filterKelas = '';

    #[Url]
    public string $filterNamaOrangTua = '';

    #[Url]
    public string $filterStatusAktif = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $tanggal_lahir = '';

    public string $jenis_kelamin = '';

    public ?int $desa_id = null;

    public ?int $kelompok_id = null;

    public ?int $jenjang_id = null;

    public string $nama_orang_tua = '';

    public string $nomor_hp_orang_tua = '';

    public string $status_domisili = 'SETEMPAT';

    public bool $status_aktif = true;

    public bool $showNaikKelasModal = false;

    public ?int $naikKelasGenerusId = null;

    public ?int $naikKelasJenjangId = null;

    public string $naikKelasSemester = '';

    public function mount(): void
    {
        $user = Auth::guard('web')->user();

        abort_unless($user->can('generus.manage') || $user->can('generus.view'), 403);

        if ($user->hasRole('pjp-desa') || $user->hasRole('sekretaris-desa')) {
            $this->desa_id = $user->desa_id;
        }
    }

    public function updating(string $name): void
    {
        if (str($name)->startsWith('filter')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterNama', 'filterKelompok', 'filterStatusDomisili', 'filterKelas', 'filterNamaOrangTua', 'filterStatusAktif']);
        $this->resetPage();
    }

    public function updatedDesaId(): void
    {
        $this->kelompok_id = null;
        $this->jenjang_id = null;
    }

    public function updatedKelompokId(): void
    {
        $this->jenjang_id = null;
    }

    public function getGenerusListProperty()
    {
        return Generus::query()
            ->with(['kelas.kelompok', 'jenjang'])
            ->when($this->filterNama, fn ($q) => $q->where('nama', 'like', '%'.$this->filterNama.'%'))
            ->when($this->filterKelompok, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('kelompok_id', $this->filterKelompok)))
            ->when($this->filterStatusDomisili, fn ($q) => $q->where('status_domisili', $this->filterStatusDomisili))
            ->when($this->filterKelas, fn ($q) => $q->where('kelas_id', $this->filterKelas))
            ->when($this->filterNamaOrangTua, fn ($q) => $q->where('nama_orang_tua', 'like', '%'.$this->filterNamaOrangTua.'%'))
            ->when($this->filterStatusAktif !== '', fn ($q) => $q->where('status_aktif', $this->filterStatusAktif))
            ->orderBy('nama')
            ->paginate(15);
    }

    public function openCreate(): void
    {
        $this->authorize('generus.manage');

        $this->reset([
            'editingId', 'nama', 'tanggal_lahir', 'jenis_kelamin', 'kelompok_id', 'jenjang_id',
            'nama_orang_tua', 'nomor_hp_orang_tua', 'status_aktif',
        ]);
        $this->status_domisili = 'SETEMPAT';

        $user = Auth::guard('web')->user();

        if ($user->hasRole('pjp-desa')) {
            $this->desa_id = $user->desa_id;
        } elseif ($user->hasRole('admin-daerah')) {
            $this->desa_id = null;
        } elseif ($user->kelompok_id) {
            $this->jenjang_id = Kelas::where('kelompok_id', $user->kelompok_id)->where('status_aktif', true)->urutJenjang()->value('jenjang_id');
        }

        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('generus.manage');

        $generus = Generus::with('kelas.kelompok')->findOrFail($id);
        $this->editingId = $generus->id;
        $this->nama = $generus->nama;
        $this->tanggal_lahir = $generus->tanggal_lahir->format('Y-m-d');
        $this->jenis_kelamin = $generus->jenis_kelamin;
        $this->desa_id = $generus->kelas->kelompok->desa_id;
        $this->kelompok_id = $generus->kelas->kelompok_id;
        $this->jenjang_id = $generus->jenjang_id;
        $this->nama_orang_tua = $generus->nama_orang_tua;
        $this->nomor_hp_orang_tua = $generus->nomor_hp_orang_tua;
        $this->status_domisili = $generus->status_domisili;
        $this->status_aktif = $generus->status_aktif;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('generus.manage');

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', Rule::in(JenisKelamin::pluck('kode'))],
            'jenjang_id' => ['required', 'exists:jenjang,id'],
            'nama_orang_tua' => ['required', 'string', 'max:255'],
            'nomor_hp_orang_tua' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
            'status_domisili' => ['required', Rule::in(StatusDomisili::pluck('kode'))],
            'status_aktif' => ['boolean'],
        ], [
            'nomor_hp_orang_tua.regex' => 'Format nomor HP tidak valid.',
        ]);

        $user = Auth::guard('web')->user();
        $bisaPilihKelompok = $user->hasRole('admin-daerah') || $user->hasRole('pjp-desa');
        $kelompokId = $bisaPilihKelompok ? $this->kelompok_id : $user->kelompok_id;

        // jenjang_id saja tidak cukup menentukan baris `kelas` yang tepat (satu Jenjang
        // bisa aktif di banyak Kelompok) — kelompok_id wajib dipin dulu sebelum derive kelas_id.
        $kelasValid = Kelas::where('kelompok_id', $kelompokId)->where('jenjang_id', $this->jenjang_id);

        // Jenjang nonaktif di Kelompok itu hanya diblok untuk penempatan BARU/PINDAH — edit
        // tanpa mengubah jenjang (mis. Generus lama yang Jenjang-nya sudah dinonaktifkan) tetap boleh disimpan.
        $generusSemula = $this->editingId ? Generus::find($this->editingId) : null;

        if (! $generusSemula || $generusSemula->jenjang_id !== $this->jenjang_id) {
            $kelasValid->where('status_aktif', true);
        }

        if ($user->hasRole('pjp-desa')) {
            $kelasValid->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id));
        } elseif (! $user->hasRole('admin-daerah')) {
            $kelasValid->where('kelompok_id', $user->kelompok_id);
        }

        $kelas = $kelasValid->first();

        if (! $kelas) {
            $this->addError('jenjang_id', 'Jenjang tidak valid untuk Anda.');

            return;
        }

        $data = [
            'nama' => $this->nama,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'kelas_id' => $kelas->id,
            'jenjang_id' => $this->jenjang_id,
            'nama_orang_tua' => $this->nama_orang_tua,
            'nomor_hp_orang_tua' => $this->nomor_hp_orang_tua,
            'status_domisili' => $this->status_domisili,
            'status_aktif' => $this->status_aktif,
        ];

        if ($this->editingId) {
            $generus = Generus::findOrFail($this->editingId);
            $statusBerubah = $generus->status_domisili !== $this->status_domisili;
            $nomorHpBerubah = $generus->nomor_hp_orang_tua !== $this->nomor_hp_orang_tua;

            $generus->update($data);

            if ($statusBerubah) {
                GenerusStatusHistory::create([
                    'generus_id' => $generus->id,
                    'status_domisili' => $this->status_domisili,
                    'berlaku_sejak' => now()->toDateString(),
                    'dicatat_oleh' => Auth::guard('web')->id(),
                ]);
            }

            activity('generus')->causedBy(Auth::guard('web')->user())->performedOn($generus)->log('Data Generus diperbarui');

            // Nomor HP orang tua berubah (mis. wali baru/nomor lama tidak aktif) — pemicu
            // UC-16 yang sama seperti create, listener sudah idempotent (find-or-create by
            // hash + attach-if-not-exists), jadi aman dipanggil ulang di jalur update.
            if ($nomorHpBerubah) {
                GenerusDisimpan::dispatch($generus);
            }

            $this->dispatch('toast', variant: 'success', message: 'Perubahan data Generus berhasil disimpan.');
        } else {
            $generus = Generus::create($data);

            activity('generus')->causedBy(Auth::guard('web')->user())->performedOn($generus)->log('Generus baru ditambahkan');

            GenerusDisimpan::dispatch($generus);

            $this->dispatch('toast', variant: 'success', message: 'Generus berhasil disimpan.');
        }

        $this->showFormModal = false;
    }

    public function openNaikKelas(int $generusId): void
    {
        $this->authorize('generus.manage');

        $this->naikKelasGenerusId = $generusId;
        $this->naikKelasJenjangId = null;
        $this->naikKelasSemester = '';
        $this->showNaikKelasModal = true;
    }

    public function naikkanKelas(): void
    {
        $this->authorize('generus.manage');

        $this->validate([
            'naikKelasJenjangId' => ['required', 'exists:jenjang,id'],
            'naikKelasSemester' => ['required', 'string', 'max:20'],
        ]);

        $generus = Generus::findOrFail($this->naikKelasGenerusId);

        // Naik kelas tetap dalam Kelompok yang sama, cuma pindah Jenjang — pastikan Jenjang
        // tujuan aktif & tetap dalam cakupan (sama seperti validasi jenjang_id di simpan()).
        $user = Auth::guard('web')->user();
        $kelasValid = Kelas::where('kelompok_id', $generus->kelas->kelompok_id)
            ->where('jenjang_id', $this->naikKelasJenjangId)
            ->where('status_aktif', true);

        if ($user->hasRole('pjp-desa')) {
            $kelasValid->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id));
        } elseif (! $user->hasRole('admin-daerah')) {
            $kelasValid->where('kelompok_id', $user->kelompok_id);
        }

        $kelasBaru = $kelasValid->first();

        if (! $kelasBaru) {
            $this->addError('naikKelasJenjangId', 'Jenjang tidak valid untuk Anda.');

            return;
        }

        GenerusKelasHistory::create([
            'generus_id' => $generus->id,
            'kelas_id' => $kelasBaru->id,
            'semester' => $this->naikKelasSemester,
            'dicatat_oleh' => Auth::guard('web')->id(),
        ]);

        $generus->update(['kelas_id' => $kelasBaru->id, 'jenjang_id' => $this->naikKelasJenjangId]);

        activity('generus')->causedBy(Auth::guard('web')->user())->performedOn($generus)->log('Kenaikan kelas dicatat');

        $this->dispatch('toast', variant: 'success', message: 'Kenaikan kelas berhasil dicatat.');
        $this->showNaikKelasModal = false;
    }

    public function render()
    {
        $user = Auth::guard('web')->user();
        $bisaPilihKelompok = $user->hasRole('admin-daerah') || $user->hasRole('pjp-desa');

        $desaOptions = $user->hasRole('admin-daerah') ? Desa::orderBy('nama')->pluck('nama', 'id') : collect();

        $kelompokOptions = match (true) {
            $user->hasRole('admin-daerah') => $this->desa_id
                ? Kelompok::where('desa_id', $this->desa_id)->orderBy('nama')->pluck('nama', 'id')
                : collect(),
            $user->hasRole('pjp-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->pluck('nama', 'id'),
            default => collect(),
        };

        // Jenjang nonaktif di Kelompok itu ("tidak diselenggarakan tahun ini") disembunyikan
        // dari opsi penempatan baru — Generus yang sudah ada di Jenjang nonaktif tidak terpengaruh.
        $formJenjangQuery = Jenjang::whereHas('kelas', function ($q) use ($bisaPilihKelompok, $user) {
            $q->where('status_aktif', true);

            if ($bisaPilihKelompok) {
                $q->where('kelompok_id', $this->kelompok_id ?? 0);
            } elseif ($user->kelompok_id) {
                $q->where('kelompok_id', $user->kelompok_id);
            }
        })->orderBy('urutan');

        // Opsi filter tabel (bukan form) — scope penuh sesuai peran, lepas dari state cascading form di atas.
        // Sengaja tidak difilter status_aktif — admin perlu bisa melihat/filter Generus
        // yang masih tercatat di Kelas yang sudah dinonaktifkan.
        // `pakar-pendidik` (Daerah-wide, generus.view) diperlakukan sama dengan admin-daerah di sini;
        // `sekretaris-desa` (Desa-wide, generus.view) diperlakukan sama dengan pjp-desa.
        $filterKelasQuery = Kelas::urutJenjang();

        if ($user->hasRole('pjp-desa') || $user->hasRole('sekretaris-desa')) {
            $filterKelasQuery->whereHas('kelompok', fn ($q) => $q->where('desa_id', $user->desa_id));
        } elseif (! $user->hasRole('admin-daerah') && ! $user->hasRole('pakar-pendidik') && $user->kelompok_id) {
            $filterKelasQuery->where('kelompok_id', $user->kelompok_id);
        }

        $filterKelasOptions = $filterKelasQuery->get();

        // "Naik Kelas" tetap dalam Kelompok Generus yang sama, cuma pindah Jenjang — opsi
        // dibatasi ke Jenjang yang aktif di Kelompok itu (sama seperti formJenjangQuery).
        $naikKelasKelompokId = $this->naikKelasGenerusId
            ? Generus::find($this->naikKelasGenerusId)?->kelas?->kelompok_id
            : null;

        $naikKelasOptions = $naikKelasKelompokId
            ? Jenjang::whereHas('kelas', fn ($q) => $q->where('kelompok_id', $naikKelasKelompokId)->where('status_aktif', true))->orderBy('urutan')->get()
            : collect();

        $filterKelompokOptions = match (true) {
            $user->hasRole('admin-daerah'), $user->hasRole('pakar-pendidik') => Kelompok::orderBy('nama')->pluck('nama', 'id'),
            $user->hasRole('pjp-desa'), $user->hasRole('sekretaris-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->pluck('nama', 'id'),
            $user->kelompok_id => Kelompok::whereKey($user->kelompok_id)->pluck('nama', 'id'),
            default => collect(),
        };

        return view('livewire.master-data.kelola-generus', [
            'jenisKelaminOptions' => JenisKelamin::options(),
            'statusDomisiliOptions' => StatusDomisili::options(),
            'bisaKelola' => $user->can('generus.manage'),
            'bisaPilihKelompok' => $bisaPilihKelompok,
            'bisaPilihDesa' => $user->hasRole('admin-daerah'),
            'desaOptions' => $desaOptions,
            'kelompokOptions' => $kelompokOptions,
            'jenjangOptions' => $formJenjangQuery->get(),
            'filterKelasOptions' => $filterKelasOptions,
            'filterKelompokOptions' => $filterKelompokOptions,
            // Modal "Naik Kelas" independen dari state cascading Desa/Kelompok form
            // Tambah/Edit di atas — harus tetap terisi meski kelompok_id form kosong.
            'naikKelasOptions' => $naikKelasOptions,
        ]);
    }
}
