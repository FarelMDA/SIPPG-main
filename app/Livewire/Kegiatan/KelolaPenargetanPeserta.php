<?php

namespace App\Livewire\Kegiatan;

use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\JenisKelamin;
use App\Models\Kelas;
use App\Models\Kelompok;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * UC-29 — Kelola Penargetan Peserta Kegiatan. SRS-Fase-2 §2.4, UCIC-Fase-2 UC-29.
 * Embedded di KelolaKegiatan (UC-21, Fase 1) dan KelolaJadwalKegiatan (UC-28) — dipasang
 * dengan `wire:key` yang berubah tiap ganti mode create/edit supaya selalu mount ulang
 * dengan cakupan & pilihan awal yang benar (nested Livewire component tidak menerima
 * `wire:model` lintas-komponen, komunikasi balik ke induk lewat event `target-updated`).
 */
class KelolaPenargetanPeserta extends Component
{
    #[Locked]
    public string $tingkat;

    #[Locked]
    public ?int $penyelenggaraId;

    public string $targetTipe = 'SEMUA';

    /** @var array<int, int> */
    public array $kelasTerpilih = [];

    /** @var array<int, int> */
    public array $individuTerpilih = [];

    public string $filterNama = '';

    public string $filterJenisKelamin = '';

    /** @var array<int, array{id:int, nama:string}> */
    public array $hasilPencarian = [];

    /**
     * @param  array<int, int>  $kelasAwal
     * @param  array<int, int>  $individuAwal
     */
    public function mount(
        string $tingkat,
        ?int $penyelenggaraId,
        string $targetTipe = 'SEMUA',
        array $kelasAwal = [],
        array $individuAwal = [],
    ): void {
        $this->tingkat = $tingkat;
        $this->penyelenggaraId = $penyelenggaraId;
        $this->targetTipe = $targetTipe;
        $this->kelasTerpilih = $kelasAwal;
        $this->individuTerpilih = $individuAwal;
    }

    private function kelompokCakupanIds(): array
    {
        return match ($this->tingkat) {
            'KELOMPOK' => [$this->penyelenggaraId],
            'DESA' => Kelompok::where('desa_id', $this->penyelenggaraId)->pluck('id')->all(),
            'DAERAH' => Kelompok::pluck('id')->all(),
            default => [],
        };
    }

    public function updatedTargetTipe(): void
    {
        $this->kirimPerubahan();
    }

    /** Jalan pintas UI: centang massal seluruh Kelas berjenjang tsb dalam cakupan — bukan filter dinamis, hasilnya tetap daftar kelas_id konkret (SRS §2.4). */
    public function pilihJenjang(int $jenjangId): void
    {
        $kelasIds = Kelas::where('jenjang_id', $jenjangId)
            ->where('status_aktif', true)
            ->whereIn('kelompok_id', $this->kelompokCakupanIds())
            ->pluck('id')
            ->all();

        $this->kelasTerpilih = array_values(array_unique([...$this->kelasTerpilih, ...$kelasIds]));
        $this->kirimPerubahan();
    }

    public function toggleKelas(int $kelasId): void
    {
        if (Kelas::whereKey($kelasId)->whereIn('kelompok_id', $this->kelompokCakupanIds())->doesntExist()) {
            return;
        }

        if (in_array($kelasId, $this->kelasTerpilih, true)) {
            $this->kelasTerpilih = array_values(array_diff($this->kelasTerpilih, [$kelasId]));
        } else {
            $this->kelasTerpilih[] = $kelasId;
        }

        $this->kirimPerubahan();
    }

    /** Pencarian bersifat kumulatif — tidak mengosongkan individuTerpilih dari pencarian sebelumnya. */
    public function cariIndividu(): void
    {
        $this->hasilPencarian = Generus::withoutGlobalScopes()
            ->whereHas('kelas', fn ($q) => $q->whereIn('kelompok_id', $this->kelompokCakupanIds()))
            ->where('status_aktif', true)
            ->when($this->filterNama, fn ($q) => $q->where('nama', 'like', '%'.$this->filterNama.'%'))
            ->when($this->filterJenisKelamin, fn ($q) => $q->where('jenis_kelamin', $this->filterJenisKelamin))
            ->orderBy('nama')
            ->limit(50)
            ->get(['id', 'nama'])
            ->map(fn ($g) => ['id' => $g->id, 'nama' => $g->nama])
            ->all();
    }

    public function tambahIndividu(int $generusId): void
    {
        if (Generus::withoutGlobalScopes()->whereKey($generusId)->whereHas('kelas', fn ($q) => $q->whereIn('kelompok_id', $this->kelompokCakupanIds()))->doesntExist()) {
            return;
        }

        if (! in_array($generusId, $this->individuTerpilih, true)) {
            $this->individuTerpilih[] = $generusId;
        }

        $this->kirimPerubahan();
    }

    public function hapusIndividu(int $generusId): void
    {
        $this->individuTerpilih = array_values(array_diff($this->individuTerpilih, [$generusId]));
        $this->kirimPerubahan();
    }

    private function kirimPerubahan(): void
    {
        $this->dispatch('target-updated', targetTipe: $this->targetTipe, kelasIds: $this->kelasTerpilih, individuIds: $this->individuTerpilih);
    }

    public function getKelasTerpilihDetailProperty()
    {
        return Kelas::whereIn('id', $this->kelasTerpilih)->urutJenjang()->get();
    }

    public function getIndividuTerpilihDetailProperty()
    {
        return Generus::withoutGlobalScopes()->whereIn('id', $this->individuTerpilih)->orderBy('nama')->get();
    }

    public function render()
    {
        return view('livewire.kegiatan.kelola-penargetan-peserta', [
            'kelasOptions' => Kelas::whereIn('kelompok_id', $this->kelompokCakupanIds())->where('status_aktif', true)->urutJenjang()->get(),
            'jenjangOptions' => Jenjang::options(),
            'jenisKelaminOptions' => JenisKelamin::options(),
        ]);
    }
}
