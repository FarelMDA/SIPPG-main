<?php

namespace App\Livewire\Kegiatan;

use App\Models\Daerah;
use App\Models\Generus;
use App\Models\HariLibur;
use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use App\Models\Kelas;
use App\Models\Kelompok;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * UC-21 — Tambah/Ubah Kegiatan, halaman biasa (bukan modal). SRS §18.1, UCIC UC-21.
 * Diperluas Fase 2 (SRS-Fase-2 §2.4/§2.5/§2.8): deskripsi, penargetan peserta,
 * pengelompokan Program, peringatan non-blocking Hari Libur.
 */
#[Layout('layouts.app')]
class FormKegiatan extends Component
{
    public ?Kegiatan $kegiatan = null;

    public string $nama = '';

    public string $deskripsi = '';

    public ?int $jenisKegiatanId = null;

    public string $tanggal = '';

    public string $tempat = '';

    public string $targetTipe = 'SEMUA';

    /** @var array<int, int> */
    public array $kelasTerpilih = [];

    /** @var array<int, int> */
    public array $individuTerpilih = [];

    public ?int $kegiatanProgramId = null;

    public ?string $peringatanHariLibur = null;

    public function mount(?Kegiatan $kegiatan = null): void
    {
        $this->authorize('kegiatan.manage');

        if ($kegiatan && $kegiatan->exists) {
            $this->authorizeKepemilikan($kegiatan);

            $this->kegiatan = $kegiatan;
            $this->nama = $kegiatan->nama;
            $this->deskripsi = (string) $kegiatan->deskripsi;
            $this->jenisKegiatanId = $kegiatan->jenis_kegiatan_id;
            $this->tanggal = $kegiatan->tanggal->toDateString();
            $this->tempat = (string) $kegiatan->tempat;
            $this->targetTipe = $kegiatan->target_tipe ?? 'SEMUA';
            $this->kelasTerpilih = $kegiatan->targetKelas()->pluck('kelas.id')->all();
            $this->individuTerpilih = $kegiatan->targetIndividu()->pluck('generus.id')->all();
            $this->kegiatanProgramId = $kegiatan->kegiatan_program_id;
            $this->cekPeringatanHariLibur();
        } else {
            $this->tanggal = now()->toDateString();
        }
    }

    /** Non admin-daerah hanya boleh mengubah Kegiatan yang diselenggarakan di lokasinya sendiri (pola sama RekapKegiatan::mount()). */
    private function authorizeKepemilikan(Kegiatan $kegiatan): void
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            return;
        }

        $bolehKelola = match ($kegiatan->tingkat) {
            'KELOMPOK' => $user->kelompok_id && (int) $kegiatan->penyelenggara_id === (int) $user->kelompok_id,
            'DESA' => $user->desa_id && (int) $kegiatan->penyelenggara_id === (int) $user->desa_id,
            default => false,
        };

        abort_unless($bolehKelola, 403);
    }

    #[On('target-updated')]
    public function menerimaTargetUpdate(string $targetTipe, array $kelasIds, array $individuIds): void
    {
        $this->targetTipe = $targetTipe;
        $this->kelasTerpilih = $kelasIds;
        $this->individuTerpilih = $individuIds;
    }

    #[On('program-dipilih')]
    public function menerimaProgramDipilih(?int $id): void
    {
        $this->kegiatanProgramId = $id;
    }

    public function updatedTanggal(): void
    {
        $this->cekPeringatanHariLibur();
    }

    private function cekPeringatanHariLibur(): void
    {
        if (! $this->tanggal) {
            $this->peringatanHariLibur = null;

            return;
        }

        $libur = HariLibur::where('tanggal_mulai', '<=', $this->tanggal)->where('tanggal_selesai', '>=', $this->tanggal)->first();

        $this->peringatanHariLibur = $libur
            ? "Tanggal ini termasuk Hari Libur: {$libur->nama}. Anda tetap bisa melanjutkan bila kegiatan ini memang sengaja diadakan saat libur."
            : null;
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

    /**
     * Tingkat & penyelenggara EFEKTIF untuk form ini — saat mengubah Kegiatan existing,
     * pakai tingkat/penyelenggara milik Kegiatan itu sendiri (bukan tingkatSaya() milik
     * acting user), supaya admin-daerah yang mengubah Kegiatan tingkat Kelompok tetap
     * melihat cakupan Penargetan Peserta yang benar (Kelompok tsb, bukan seluruh Daerah).
     */
    private function tingkatEfektif(): ?string
    {
        return $this->kegiatan?->tingkat ?? $this->tingkatSaya();
    }

    private function penyelenggaraEfektif(): array
    {
        if ($this->kegiatan) {
            return [$this->kegiatan->penyelenggara_type, $this->kegiatan->penyelenggara_id];
        }

        $user = Auth::guard('web')->user();

        return match ($this->tingkatSaya()) {
            'KELOMPOK' => ['kelompok', $user->kelompok_id],
            'DESA' => ['desa', $user->desa_id],
            'DAERAH' => ['daerah', Daerah::value('id')],
            default => [null, null],
        };
    }

    private function targetValid(?int $penyelenggaraId, string $tingkat): array
    {
        $kelompokIds = match ($tingkat) {
            'KELOMPOK' => collect([$penyelenggaraId]),
            'DESA' => Kelompok::where('desa_id', $penyelenggaraId)->pluck('id'),
            'DAERAH' => Kelompok::pluck('id'),
            default => collect(),
        };

        return [
            'kelas' => Kelas::whereIn('id', $this->kelasTerpilih)->whereIn('kelompok_id', $kelompokIds)->pluck('id'),
            'individu' => Generus::withoutGlobalScopes()->whereIn('id', $this->individuTerpilih)
                ->whereHas('kelas', fn ($q) => $q->whereIn('kelompok_id', $kelompokIds))
                ->pluck('id'),
        ];
    }

    public function simpan(): void
    {
        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenisKegiatanId' => ['required', 'exists:jenis_kegiatan,id'],
            'tanggal' => ['required', 'date'],
            'tempat' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::guard('web')->user();
        $tingkat = $this->tingkatEfektif();
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggaraEfektif();

        if ($this->kegiatan) {
            $this->authorizeKepemilikan($this->kegiatan);

            $this->kegiatan->update([
                'nama' => $this->nama,
                'deskripsi' => $this->deskripsi ?: null,
                'jenis_kegiatan_id' => $this->jenisKegiatanId,
                'tanggal' => $this->tanggal,
                'tempat' => $this->tempat ?: null,
                'target_tipe' => $this->targetTipe,
                'kegiatan_program_id' => $this->kegiatanProgramId,
            ]);

            $kegiatan = $this->kegiatan;
            activity('kegiatan')->causedBy($user)->performedOn($kegiatan)->log('Kegiatan diperbarui');
            $pesan = 'Kegiatan berhasil diperbarui.';
        } else {
            $kegiatan = Kegiatan::create([
                'nama' => $this->nama,
                'deskripsi' => $this->deskripsi ?: null,
                'tingkat' => $tingkat,
                'penyelenggara_type' => $penyelenggaraType,
                'penyelenggara_id' => $penyelenggaraId,
                'jenis_kegiatan_id' => $this->jenisKegiatanId,
                'tanggal' => $this->tanggal,
                'tempat' => $this->tempat ?: null,
                'target_tipe' => $this->targetTipe,
                'kegiatan_program_id' => $this->kegiatanProgramId,
                'status' => 'TERJADWAL',
                'dibuat_oleh' => $user->id,
            ]);

            activity('kegiatan')->causedBy($user)->log('Kegiatan berhasil disimpan');
            $pesan = 'Kegiatan berhasil disimpan.';
        }

        $target = $this->targetValid($penyelenggaraId, $tingkat);
        $kegiatan->targetKelas()->sync($this->targetTipe === 'JENJANG_KELAS' ? $target['kelas'] : []);
        $kegiatan->targetIndividu()->sync($this->targetTipe === 'INDIVIDU' ? $target['individu'] : []);

        session()->flash('flash_toast', ['variant' => 'success', 'message' => $pesan]);
        $this->redirect(route('kegiatan.index'), navigate: false);
    }

    public function render()
    {
        $tingkat = $this->tingkatEfektif();
        [, $penyelenggaraId] = $this->penyelenggaraEfektif();

        return view('livewire.kegiatan.form-kegiatan', [
            'jenisOptions' => JenisKegiatan::options(),
            'tingkatSaya' => $tingkat,
            'penyelenggaraIdSaya' => $penyelenggaraId,
        ]);
    }
}
