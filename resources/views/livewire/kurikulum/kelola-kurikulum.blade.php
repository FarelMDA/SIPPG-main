<div>
    <x-page-header title="Kelola Kurikulum" />

    @can('kurikulum.manage')
        <div x-data="{ open: false }" class="mb-4">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center gap-2 text-sm font-medium text-brand-primary hover:underline"
            >
                <x-icon name="upload" size="16" />
                Impor Massal per Jenjang
            </button>

            <div x-show="open" x-cloak class="card mt-2 p-4">
                <p class="mb-3 text-sm font-medium text-ink-primary">Impor Massal per Jenjang</p>
                <form wire:submit="unggah" class="flex flex-wrap items-end gap-3">
                    <x-select label="Jenjang" name="jenjangImpor" wire:model="jenjang" :options="$jenjangOptions" placeholder="Pilih Jenjang" />
                    <div>
                        <label class="form-label">File Excel (.xlsx)</label>
                        <input type="file" wire:model="file" class="form-control">
                    </div>
                    <x-button type="submit" variant="secondary">Unggah</x-button>
                </form>
                <p class="mt-2 text-xs text-ink-muted">Kolom: Tanggal Mulai | Tanggal Selesai | Jenis (MATERI/MUNAQOSAH) | Item Materi (pisah dengan ";") | Keterangan. Mengganti seluruh baris jenjang yang dipilih.</p>
            </div>
        </div>
    @endcan

    <div class="mb-4 max-w-xs">
        <x-select label="Jenjang" name="filterJenjang" wire:model.live="filterJenjang" :options="$jenjangOptions" placeholder="Pilih Jenjang" />
    </div>

    @if(!$filterJenjang)
        <x-empty-state title="Pilih Jenjang untuk melihat kalender kurikulum" icon="calendar" />
    @else
        <div class="card overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <button type="button" wire:click="bulanSebelumnya" class="btn btn-sm btn-secondary" aria-label="Bulan sebelumnya">&larr;</button>
                <div class="text-center">
                    <p class="font-semibold text-ink-primary">{{ $labelBulan }}</p>
                    <button type="button" wire:click="bulanIni" class="text-xs text-brand-primary hover:underline">Hari ini</button>
                </div>
                <button type="button" wire:click="bulanBerikutnya" class="btn btn-sm btn-secondary" aria-label="Bulan berikutnya">&rarr;</button>
            </div>

            <div class="grid grid-cols-7 border-b border-border text-center text-xs font-semibold uppercase tracking-wide text-ink-muted">
                @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $hari)
                    <div class="py-2">{{ $hari }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach($this->mingguan as $pekan)
                    @foreach($pekan as $hari)
                        <button
                            type="button"
                            wire:key="hari-{{ $hari['tanggal'] }}"
                            wire:click="pilihTanggal('{{ $hari['tanggal'] }}')"
                            @class([
                                'flex min-h-40 flex-col items-start gap-1 border-b border-r border-border p-2 text-left align-top transition hover:bg-surface-subtle',
                                'bg-surface-subtle/40 text-ink-muted' => !$hari['dalamBulan'],
                                'ring-2 ring-inset ring-brand-primary' => $selectedDate === $hari['tanggal'],
                            ])
                        >
                            <span @class([
                                'text-sm',
                                'font-bold text-brand-primary' => $hari['hariIni'],
                                'font-medium text-ink-primary' => !$hari['hariIni'] && $hari['dalamBulan'],
                            ])>{{ $hari['hari'] }}</span>

                            @if($hari['entry'])
                                <div class="max-h-28 w-full space-y-0.5 overflow-y-auto rounded px-1.5 py-1 text-xs leading-snug {{ $hari['entry']->jenis === 'MUNAQOSAH' ? 'bg-warning-subtle text-warning-text' : 'bg-brand-subtle text-brand-primary' }}">
                                    @if($hari['entry']->jenis === 'MUNAQOSAH')
                                        <div>Munaqosah</div>
                                    @else
                                        @foreach($hari['entry']->item_materi ?? [] as $item)
                                            <div class="truncate" title="{{ $item }}">{{ $item }}</div>
                                        @endforeach
                                    @endif
                                </div>
                            @endif
                        </button>
                    @endforeach
                @endforeach
            </div>
        </div>

        @if($selectedDate)
            <x-card class="mt-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="font-semibold text-ink-primary">{{ \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</p>
                    <button type="button" wire:click="tutupPanel" class="text-sm text-ink-muted hover:underline">Tutup</button>
                </div>

                @can('kurikulum.manage')
                    <form wire:submit="simpan" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-input label="Tanggal Mulai" name="tanggalMulai" type="date" wire:model="tanggalMulai" required :error="$errors->first('tanggalMulai')" />
                            <x-input label="Tanggal Selesai" name="tanggalSelesai" type="date" wire:model="tanggalSelesai" required :error="$errors->first('tanggalSelesai')" />
                        </div>
                        <div>
                            <span class="form-label">Jenis</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="jenis" value="MATERI"> Materi</label>
                                <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="jenis" value="MUNAQOSAH"> Munaqosah</label>
                            </div>
                        </div>
                        @if($jenis === 'MATERI')
                            <div>
                                <label class="form-label">Item Materi (satu per baris)</label>
                                <textarea wire:model="itemMateriText" rows="4" class="form-control" placeholder="Tilawati 1 halaman 1&#10;Hafalan An-Nas"></textarea>
                                @error('itemMateriText') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <x-input label="Keterangan" name="keterangan" wire:model="keterangan" :error="$errors->first('keterangan')" />

                        <div class="flex items-center justify-between pt-2">
                            @if($editingId)
                                <button type="button" wire:click="hapus({{ $editingId }})" wire:confirm="Hapus baris kurikulum ini?" class="text-sm text-danger-text hover:underline">Hapus</button>
                            @else
                                <span></span>
                            @endif
                            <x-button type="submit" variant="primary">Simpan</x-button>
                        </div>
                    </form>
                @else
                    @if($editingId)
                        <p class="text-sm font-medium text-ink-primary">{{ $jenis === 'MUNAQOSAH' ? 'Munaqosah' : 'Materi' }}</p>
                        @if(!empty($itemMateriText))
                            <ul class="mt-1 list-inside list-disc text-sm text-ink-secondary">
                                @foreach(explode("\n", $itemMateriText) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($keterangan)
                            <p class="mt-2 text-sm text-ink-muted">{{ $keterangan }}</p>
                        @endif
                    @else
                        <p class="text-sm text-ink-muted">Belum ada materi terjadwal tanggal ini.</p>
                    @endif
                @endcan
            </x-card>
        @endif
    @endif
</div>
