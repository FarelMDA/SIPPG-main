<div>
    @if($mode === 'list')
        <x-page-header title="Musyawaroh & Notulen">
            <x-slot:actions>
                <x-button variant="primary" icon="plus" wire:click="mulaiBaru">Catat Musyawaroh Baru</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="no-print mb-4 flex flex-wrap gap-1">
            <button type="button" wire:click="$set('filterJenis', '')" class="badge {{ $filterJenis === '' ? 'badge-info' : 'badge-neutral' }}">Semua</button>
            @foreach($jenisOptions as $value => $label)
                <button type="button" wire:click="$set('filterJenis', '{{ $value }}')" class="badge {{ $filterJenis === (string) $value ? 'badge-info' : 'badge-neutral' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="card overflow-x-auto p-0">
            <table class="data-table">
                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Jumlah Item</th><th>Jumlah Hadir</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse($this->daftar as $m)
                        <tr wire:key="musy-{{ $m->id }}">
                            <td>{{ $m->tanggal->format('d/m/Y') }}</td>
                            <td><x-badge variant="info">{{ $m->jenisMusyawaroh->nama }}</x-badge></td>
                            <td>{{ $m->items_count }}</td>
                            <td>{{ $m->jumlah_hadir ?? '—' }}</td>
                            <td class="text-right">
                                <button type="button" wire:click="lihatCetak({{ $m->id }})" class="text-brand-primary hover:underline">Lihat / Cetak</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="Belum ada notulen musyawaroh" icon="users" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif($mode === 'form')
        <x-page-header title="Catat Musyawaroh Baru">
            <x-slot:actions>
                <x-button variant="secondary" wire:click="kembali">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form wire:submit="simpan" class="space-y-6">
            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-select label="Jenis Musyawaroh" name="jenis" wire:model.live="jenis" :options="$jenisOptions" required :error="$errors->first('jenis')" />
                    <x-input label="Tanggal" name="tanggal" type="date" wire:model="tanggal" required :error="$errors->first('tanggal')" />
                </div>

                @if($this->jenisPerluJumlahHadir)
                    <div class="mt-4 max-w-xs">
                        <x-input label="Jumlah Hadir (Absensi Mustin)" name="jumlah_hadir" type="number" wire:model="jumlah_hadir" required :error="$errors->first('jumlah_hadir')" />
                    </div>
                @endif
            </x-card>

            <x-card>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-ink-primary">Item Notulen</h2>
                    <x-button type="button" variant="secondary" size="sm" icon="plus" wire:click="tambahItem">Tambah Baris</x-button>
                </div>

                <div class="space-y-4">
                    @foreach($items as $index => $item)
                        <div class="rounded-lg border border-border p-4" wire:key="item-{{ $index }}">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-ink-secondary">Item #{{ $index + 1 }}</span>
                                @if(count($items) > 1)
                                    <button type="button" wire:click="hapusItem({{ $index }})" class="text-danger-text hover:underline text-xs">Hapus</button>
                                @endif
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="form-label">Pokok Masalah / Pembahasan <span class="text-danger-solid">*</span></label>
                                    <textarea wire:model="items.{{ $index }}.pokok_masalah" class="form-control" rows="2"></textarea>
                                    @error("items.$index.pokok_masalah") <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Keputusan / Rencana Mengatasi</label>
                                    <textarea wire:model="items.{{ $index }}.keputusan" class="form-control" rows="2"></textarea>
                                </div>
                                <div>
                                    <label class="form-label">PIC</label>
                                    <input type="text" wire:model="items.{{ $index }}.pic" class="form-control" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="form-label">Keterangan (Status)</label>
                                    <input type="text" wire:model="items.{{ $index }}.keterangan" class="form-control" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-info-banner>Item bulan lalu tidak otomatis muncul di sini — salin manual bila perlu; fitur carry-over otomatis baru tersedia Fase 2.</x-info-banner>

            <x-button type="submit" variant="primary" size="lg">Simpan Notulen</x-button>
        </form>
    @elseif($mode === 'cetak' && $viewing)
        <x-page-header title="Notulen Musyawaroh">
            <x-slot:actions>
                <x-button variant="secondary" wire:click="kembali">Kembali</x-button>
                @if($bisaSahkan && $viewing->tingkat === 'DAERAH' && ! $viewing->disahkan_pada)
                    <x-button variant="primary" icon="check" wire:click="sahkan({{ $viewing->id }})" wire:confirm="Sahkan notulen ini? Notulen yang sudah disahkan tidak bisa diubah lagi.">Sahkan</x-button>
                @endif
                <x-button variant="primary" icon="printer" onclick="window.print()">Cetak / Simpan sebagai PDF</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card>
            <div class="mb-4 grid gap-2 text-sm sm:grid-cols-3">
                <div><span class="text-ink-muted">{{ ucfirst(strtolower($viewing->tingkat)) }}:</span> <strong>{{ $viewing->penyelenggara_nama ?? '—' }}</strong></div>
                <div><span class="text-ink-muted">Jenis:</span> <strong>{{ $viewing->jenisMusyawaroh->nama }}</strong></div>
                <div><span class="text-ink-muted">Tanggal:</span> <strong>{{ $viewing->tanggal->format('d/m/Y') }}</strong></div>
                @if($viewing->jumlah_hadir !== null)
                    <div><span class="text-ink-muted">Jumlah Hadir:</span> <strong>{{ $viewing->jumlah_hadir }}</strong></div>
                @endif
                @if($viewing->tingkat === 'DAERAH')
                    <div>
                        <span class="text-ink-muted">Status Pengesahan:</span>
                        @if($viewing->disahkan_pada)
                            <x-badge variant="success">Disahkan oleh {{ $viewing->disahkanOleh?->nama }} — {{ $viewing->disahkan_pada->format('d/m/Y') }}</x-badge>
                        @else
                            <x-badge variant="neutral">Belum disahkan</x-badge>
                        @endif
                    </div>
                @endif
            </div>

            <table class="data-table">
                <thead><tr><th>Pokok Masalah</th><th>Keputusan</th><th>PIC</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @foreach($viewing->items as $item)
                        <tr>
                            <td>{{ $item->pokok_masalah }}</td>
                            <td>{{ $item->keputusan ?? '—' }}</td>
                            <td>{{ $item->pic ?? '—' }}</td>
                            <td>{{ $item->keterangan ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @endif
</div>
