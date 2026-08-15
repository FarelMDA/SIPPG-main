<div>
    @if($mode === 'list')
        <x-page-header title="Program Monitoring">
            <x-slot:actions>
                <x-button variant="primary" icon="plus" wire:click="mulaiBaru">Program Baru</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="card overflow-x-auto p-0">
            <table class="data-table">
                <thead><tr><th>Nama Program</th><th>Tenggat</th><th>Jumlah Item</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse($this->daftar as $program)
                        <tr wire:key="pm-{{ $program->id }}">
                            <td class="font-medium text-ink-primary">{{ $program->nama_program }}</td>
                            <td>{{ $program->tenggat?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $program->items_count }}</td>
                            <td class="text-right">
                                <button type="button" wire:click="edit({{ $program->id }})" class="text-brand-primary hover:underline">Kelola</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state title="Belum ada Program Monitoring" icon="list-checks" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <x-page-header :title="$editingId ? 'Kelola Program Monitoring' : 'Program Monitoring Baru'">
            <x-slot:actions>
                <x-button variant="secondary" wire:click="kembali">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form wire:submit="simpan" class="space-y-6">
            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input label="Nama Program" name="nama_program" wire:model="nama_program" required :error="$errors->first('nama_program')" description="Bebas teks, mis. Turba GPN, GOMA, GMKM" />
                    <x-input label="Tenggat" name="tenggat" type="date" wire:model="tenggat" :error="$errors->first('tenggat')" />
                </div>
                <div class="mt-4">
                    <label class="form-label">Target Peserta</label>
                    <textarea wire:model="target_peserta" class="form-control" rows="2"></textarea>
                </div>
            </x-card>

            <x-card>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-ink-primary">Item Monitoring</h2>
                    <x-button type="button" variant="secondary" size="sm" icon="plus" wire:click="tambahItem">Tambah Item</x-button>
                </div>

                <div class="space-y-4">
                    @foreach($items as $index => $item)
                        <div class="rounded-lg border border-border p-4" wire:key="pmitem-{{ $index }}">
                            <div class="mb-2 flex justify-end">
                                <button type="button" wire:click="hapusItem({{ $index }})" class="text-xs text-danger-text hover:underline">Hapus</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-select label="Generus (opsional)" name="items.{{ $index }}.generus_id" wire:model="items.{{ $index }}.generus_id" :options="$generusOptions" placeholder="— Umum, tidak spesifik —" />
                                <x-select label="Status" name="items.{{ $index }}.status_item" wire:model="items.{{ $index }}.status_item" :options="['BELUM' => 'Belum', 'PROSES' => 'Proses', 'SELESAI' => 'Selesai']" />
                                <div class="sm:col-span-2">
                                    <label class="form-label">Temuan</label>
                                    <textarea wire:model="items.{{ $index }}.temuan" class="form-control" rows="2"></textarea>
                                </div>
                                <x-input label="PIC" name="items.{{ $index }}.pic" wire:model="items.{{ $index }}.pic" />
                                <x-input label="Tenggat Item" name="items.{{ $index }}.tenggat_item" type="date" wire:model="items.{{ $index }}.tenggat_item" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-info-banner>Item bulan lalu tidak otomatis muncul di sini — pantau manual dari status tiap item; fitur carry-over otomatis baru tersedia Fase 2.</x-info-banner>

            <x-button type="submit" variant="primary" size="lg">Simpan Program Monitoring</x-button>
        </form>
    @endif
</div>
