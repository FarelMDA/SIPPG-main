<?php

namespace App\Livewire\MasterData;

use App\Imports\GenerusImport;
use App\Imports\PendidikImport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

/**
 * UC-08 — Impor Massal Data Awal. SRS §6.4, UCIC UC-08.
 */
#[Layout('layouts.app')]
class ImporMassal extends Component
{
    use WithFileUploads;

    public string $tipe_impor = 'GENERUS';

    public $file;

    public ?int $ringkasanSukses = null;

    public array $ringkasanGagal = [];

    public function mount(): void
    {
        $this->authorize('import.run');
    }

    public function unggah(): void
    {
        $this->validate([
            'tipe_impor' => ['required', 'in:GENERUS,PENDIDIK'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.mimes' => 'Format file tidak sesuai template.',
            'file.max' => 'Format file tidak sesuai template.',
        ]);

        $import = $this->tipe_impor === 'GENERUS' ? new GenerusImport : new PendidikImport;

        Excel::import($import, $this->file->getRealPath());

        $this->ringkasanSukses = $import->sukses;
        $this->ringkasanGagal = $import->gagal;

        $this->dispatch('toast', variant: 'success', message: "Impor selesai: {$import->sukses} baris berhasil, ".count($import->gagal).' baris gagal.');

        $this->reset('file');
    }

    public function render()
    {
        return view('livewire.master-data.impor-massal');
    }
}
