<?php

namespace App\Livewire\Kegiatan;

use App\Events\HariLiburDisimpan;
use App\Models\HariLibur;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-38 — Kelola Kalender Hari Libur. SRS-Fase-2 §2.6/§2.6.1, UCIC-Fase-2 UC-38.
 * Admin Daerah saja — satu kalender berlaku seluruh Daerah (tidak ada multi-tenant).
 */
#[Layout('layouts.app')]
class KelolaHariLibur extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public string $sumberEditing = 'MANUAL';

    public function mount(): void
    {
        $this->authorize('hari-libur.manage');
    }

    public function getDaftarProperty()
    {
        return HariLibur::orderBy('tanggal_mulai')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nama', 'tanggalMulai', 'tanggalSelesai']);
        $this->sumberEditing = 'MANUAL';
        $this->tanggalMulai = now()->toDateString();
        $this->tanggalSelesai = now()->toDateString();
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $libur = HariLibur::findOrFail($id);

        $this->editingId = $libur->id;
        $this->nama = $libur->nama;
        $this->tanggalMulai = $libur->tanggal_mulai->toDateString();
        $this->tanggalSelesai = $libur->tanggal_selesai->toDateString();
        $this->sumberEditing = $libur->sumber;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('hari-libur.manage');

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date', 'after_or_equal:tanggalMulai'],
        ], [
            'tanggalSelesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $libur = $this->editingId ? HariLibur::findOrFail($this->editingId) : new HariLibur;

        $sumberOtomatisGoogle = $libur->exists && $libur->sumber === 'OTOMATIS_GOOGLE';

        $libur->fill([
            'nama' => $this->nama,
            'tanggal_mulai' => $this->tanggalMulai,
            'tanggal_selesai' => $this->tanggalSelesai,
        ]);

        if (! $libur->exists) {
            $libur->sumber = 'MANUAL';
            $libur->dibuat_oleh = Auth::guard('web')->id();
        } elseif ($sumberOtomatisGoogle) {
            // Baris hasil sinkronisasi Google yang diedit manual tidak boleh tertimpa
            // sinkronisasi bulan berikutnya (SRS §2.6.1).
            $libur->disunting_manual = true;
        }

        $libur->save();

        activity('hari-libur')->causedBy(Auth::guard('web')->user())->performedOn($libur)->log('Hari Libur disimpan');

        HariLiburDisimpan::dispatch($libur);

        $pesan = $sumberOtomatisGoogle
            ? 'Perubahan disimpan. Sinkronisasi berikutnya tidak akan menimpa baris ini.'
            : 'Hari Libur berhasil disimpan.';

        $this->dispatch('toast', variant: 'success', message: $pesan);
        $this->showFormModal = false;
    }

    public function hapus(int $id): void
    {
        $this->authorize('hari-libur.manage');

        $libur = HariLibur::findOrFail($id);

        if ($libur->sumber === 'MANUAL') {
            $libur->forceDelete();
        } else {
            $libur->delete();
        }

        activity('hari-libur')->causedBy(Auth::guard('web')->user())->log('Hari Libur dihapus');
        $this->dispatch('toast', variant: 'success', message: 'Hari Libur berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.kegiatan.kelola-hari-libur');
    }
}
