<?php

namespace App\Livewire\Konseling;

use App\Models\Generus;
use App\Models\KonselingCatatan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-27 — Kelola Catatan Konseling (Rekam Kasus). SRS-Fase-1 §5.1, UCIC-Fase-1 UC-27.
 * Fitur sederhana (teks bebas, tanpa struktur kategori kasus), akses dibatasi PRD §11.1:
 * `bk-kbm` mencatat (tulis), `pjp-kelompok`/`admin-daerah` hanya melihat.
 */
#[Layout('layouts.app')]
class KelolaKonseling extends Component
{
    public const STATUS_OPTIONS = [
        'BERLANGSUNG' => 'Berlangsung',
        'SELESAI' => 'Selesai',
    ];

    public bool $showFormModal = false;

    public ?int $generus_id = null;

    public string $tanggal = '';

    public string $catatan = '';

    public string $status = 'BERLANGSUNG';

    public function mount(): void
    {
        $user = Auth::guard('web')->user();

        abort_unless($user->can('konseling.manage') || $user->can('konseling.view'), 403);
    }

    public function getDaftarProperty()
    {
        return KonselingCatatan::with(['generus', 'dicatatOleh'])
            ->orderByDesc('tanggal')
            ->get();
    }

    public function getGenerusOptionsProperty()
    {
        return Generus::where('status_aktif', true)->orderBy('nama')->pluck('nama', 'id');
    }

    public function openCreate(): void
    {
        $this->authorize('konseling.manage');

        $this->reset(['generus_id', 'catatan']);
        $this->tanggal = now()->toDateString();
        $this->status = 'BERLANGSUNG';
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->authorize('konseling.manage');

        $this->validate([
            'generus_id' => ['required', 'exists:generus,id'],
            'tanggal' => ['required', 'date'],
            'catatan' => ['required', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(self::STATUS_OPTIONS))],
        ]);

        $user = Auth::guard('web')->user();

        // generus_id adalah properti publik Livewire — tetap divalidasi berada dalam
        // kelompok milik user yang login, bukan cuma dari dropdown yang sudah difilter.
        if (! Generus::whereKey($this->generus_id)->exists()) {
            $this->addError('generus_id', 'Generus tidak ditemukan di kelompok Anda.');

            return;
        }

        $catatan = KonselingCatatan::create([
            'kelompok_id' => $user->kelompok_id,
            'generus_id' => $this->generus_id,
            'tanggal' => $this->tanggal,
            'catatan' => $this->catatan,
            'status' => $this->status,
            'dicatat_oleh' => $user->id,
        ]);

        activity('konseling')->causedBy($user)->performedOn($catatan)->log('Catatan konseling disimpan');

        $this->dispatch('toast', variant: 'success', message: 'Catatan konseling berhasil disimpan.');
        $this->showFormModal = false;
    }

    public function render()
    {
        return view('livewire.konseling.kelola-konseling', [
            'statusOptions' => self::STATUS_OPTIONS,
            'bisaTulis' => Auth::guard('web')->user()->can('konseling.manage'),
        ]);
    }
}
