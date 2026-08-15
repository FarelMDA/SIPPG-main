<?php

namespace App\Livewire\Kegiatan;

use App\Models\Generus;
use App\Models\ProgramMonitoring;
use App\Models\ProgramMonitoringItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-24 — Kelola Program Monitoring. SRS §18.3, UCIC UC-24. Generik (Turba,
 * GOMA, GMKM, dsb.) — nama_program bebas teks, TANPA carry-over otomatis.
 */
#[Layout('layouts.app')]
class KelolaProgramMonitoring extends Component
{
    public string $mode = 'list';

    public ?int $editingId = null;

    public string $nama_program = '';

    public string $target_peserta = '';

    public ?string $tenggat = null;

    /** @var array<int, array{generus_id: ?int, temuan: string, pic: string, status_item: string, tenggat_item: ?string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->authorize('program-monitoring.manage');
    }

    public function getDaftarProperty()
    {
        return ProgramMonitoring::withCount('items')->orderByDesc('id')->get();
    }

    public function mulaiBaru(): void
    {
        $this->reset(['editingId', 'nama_program', 'target_peserta', 'tenggat']);
        $this->items = [['generus_id' => null, 'temuan' => '', 'pic' => '', 'status_item' => 'BELUM', 'tenggat_item' => null]];
        $this->mode = 'form';
    }

    public function edit(int $id): void
    {
        $program = ProgramMonitoring::with('items')->findOrFail($id);
        $this->editingId = $program->id;
        $this->nama_program = $program->nama_program;
        $this->target_peserta = (string) $program->target_peserta;
        $this->tenggat = $program->tenggat?->toDateString();
        $this->items = $program->items->map(fn ($item) => [
            'generus_id' => $item->generus_id,
            'temuan' => (string) $item->temuan,
            'pic' => (string) $item->pic,
            'status_item' => $item->status_item,
            'tenggat_item' => $item->tenggat_item?->toDateString(),
        ])->toArray();
        $this->mode = 'form';
    }

    public function kembali(): void
    {
        $this->mode = 'list';
    }

    public function tambahItem(): void
    {
        $this->items[] = ['generus_id' => null, 'temuan' => '', 'pic' => '', 'status_item' => 'BELUM', 'tenggat_item' => null];
    }

    public function hapusItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateStatusItem(int $itemId, string $status): void
    {
        // itemId adalah argumen method Livewire — ProgramMonitoringItem sendiri tidak punya
        // global scope kelompok (beda dari parent ProgramMonitoring-nya), jadi cakupannya
        // harus dicek lewat relasi programMonitoring() di sini.
        $item = ProgramMonitoringItem::whereKey($itemId)->whereHas('programMonitoring')->first();

        if (! $item) {
            return;
        }

        $item->update(['status_item' => $status]);
    }

    public function simpan(): void
    {
        $this->validate([
            'nama_program' => ['required', 'string', 'max:255'],
            'target_peserta' => ['nullable', 'string'],
            'tenggat' => ['nullable', 'date'],
            'items' => ['array'],
        ]);

        $user = Auth::guard('web')->user();

        $program = ProgramMonitoring::updateOrCreate(
            ['id' => $this->editingId],
            [
                'kelompok_id' => $user->kelompok_id,
                'nama_program' => $this->nama_program,
                'target_peserta' => $this->target_peserta ?: null,
                'tenggat' => $this->tenggat ?: null,
                'dibuat_oleh' => $user->id,
            ]
        );

        // generus_id per item juga properti publik Livewire — pastikan hanya generus di
        // kelompok sendiri yang bisa dilampirkan (dropdown di render() sudah discope, ini jaga-jaga server-side).
        $generusValid = Generus::withoutGlobalScopes()
            ->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $user->kelompok_id))
            ->pluck('id')
            ->flip();

        $program->items()->delete();
        foreach ($this->items as $item) {
            if (! $item['temuan'] && ! $item['pic'] && ! $item['generus_id']) {
                continue;
            }

            ProgramMonitoringItem::create([
                'program_monitoring_id' => $program->id,
                'generus_id' => $item['generus_id'] && $generusValid->has($item['generus_id']) ? $item['generus_id'] : null,
                'temuan' => $item['temuan'] ?: null,
                'pic' => $item['pic'] ?: null,
                'status_item' => $item['status_item'] ?: 'BELUM',
                'tenggat_item' => $item['tenggat_item'] ?: null,
            ]);
        }

        activity('program-monitoring')->causedBy($user)->performedOn($program)->log('Program Monitoring disimpan');
        $this->dispatch('toast', variant: 'success', message: 'Program Monitoring berhasil disimpan.');
        $this->mode = 'list';
    }

    public function render()
    {
        $user = Auth::guard('web')->user();

        return view('livewire.kegiatan.kelola-program-monitoring', [
            'generusOptions' => Generus::withoutGlobalScopes()->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $user->kelompok_id))->pluck('nama', 'id'),
        ]);
    }
}
