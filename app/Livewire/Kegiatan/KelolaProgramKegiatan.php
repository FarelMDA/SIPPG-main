<?php

namespace App\Livewire\Kegiatan;

use App\Models\KegiatanProgram;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * UC-40 — Kelola Program Kegiatan (bagian pemilih/pembuat). SRS-Fase-2 §2.8.
 * Embedded di KelolaKegiatan (UC-21) dan KelolaJadwalKegiatan (UC-28) — Program murni
 * label bersama, tidak ada pembatasan kepemilikan (SRS §2.8 Aturan Bisnis).
 */
class KelolaProgramKegiatan extends Component
{
    public ?int $kegiatanProgramId = null;

    public bool $tampilkanFormBaru = false;

    public string $namaBaru = '';

    public string $tingkatTertinggiBaru = 'KELOMPOK';

    public function mount(?int $kegiatanProgramId = null): void
    {
        $this->kegiatanProgramId = $kegiatanProgramId;
    }

    public function updatedKegiatanProgramId(): void
    {
        $this->dispatch('program-dipilih', id: $this->kegiatanProgramId);
    }

    public function bukaFormBaru(): void
    {
        $this->tampilkanFormBaru = true;
        $this->reset(['namaBaru', 'tingkatTertinggiBaru']);
        $this->tingkatTertinggiBaru = 'KELOMPOK';
    }

    public function buatProgram(): void
    {
        $this->validate([
            'namaBaru' => ['required', 'string', 'max:255'],
            'tingkatTertinggiBaru' => ['required', 'in:KELOMPOK,DESA,DAERAH'],
        ]);

        $program = KegiatanProgram::create([
            'nama' => $this->namaBaru,
            'tingkat_tertinggi' => $this->tingkatTertinggiBaru,
            'dibuat_oleh' => Auth::guard('web')->id(),
        ]);

        activity('kegiatan-program')->causedBy(Auth::guard('web')->user())->performedOn($program)->log('Program Kegiatan dibuat');

        $this->kegiatanProgramId = $program->id;
        $this->tampilkanFormBaru = false;
        $this->dispatch('program-dipilih', id: $program->id);
    }

    public function render()
    {
        return view('livewire.kegiatan.kelola-program-kegiatan', [
            'programOptions' => KegiatanProgram::orderBy('nama')->pluck('nama', 'id'),
        ]);
    }
}
