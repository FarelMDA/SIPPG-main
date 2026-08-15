<div>
    <div class="mb-4 flex items-center justify-between">
        <button type="button" wire:click="resetFilters" class="no-print text-xs text-ink-muted hover:text-brand-primary">Reset Filter</button>
        @can('struktur-organisasi.manage')
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Kelompok</x-button>
        @endcan
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr><th scope="col">Nama Kelompok</th><th scope="col">Desa</th>@can('struktur-organisasi.manage')<th scope="col" class="text-right">Aksi</th>@endcan</tr>
                <tr class="no-print">
                    <th scope="col" class="py-2 font-normal normal-case"><input type="text" wire:model.live.debounce.400ms="filterNama" aria-label="Filter Nama Kelompok" placeholder="Cari nama..." class="form-control py-1 text-xs" /></th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterDesa" aria-label="Filter Desa" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($desaOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </th>
                    @can('struktur-organisasi.manage')<th scope="col" class="py-2 font-normal normal-case"></th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($this->kelompokList as $kelompok)
                    <tr wire:key="kelompok-{{ $kelompok->id }}">
                        <td class="font-medium text-ink-primary">{{ $kelompok->nama }}</td>
                        <td>{{ $kelompok->desa->nama }}</td>
                        @can('struktur-organisasi.manage')
                            <td class="text-right">
                                <button type="button" wire:click="openEdit({{ $kelompok->id }})" class="mr-3 text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                                <button type="button" wire:click="hapus({{ $kelompok->id }})" wire:confirm="Hapus kelompok {{ $kelompok->nama }}?" class="text-ink-muted hover:text-danger-solid"><x-icon name="trash" size="16" /></button>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="3"><x-empty-state title="Belum ada Kelompok" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->kelompokList->links() }}</div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit Kelompok' : 'Tambah Kelompok'">
        <form wire:submit="simpan" class="space-y-4">
            <x-select label="Desa" name="desa_id" wire:model="desa_id" :options="$desaOptions" placeholder="Pilih desa" required :error="$errors->first('desa_id')" />
            <x-input label="Nama Kelompok" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <div>
                <label for="alamat" class="form-label">Alamat</label>
                <textarea id="alamat" wire:model="alamat" class="form-control" rows="2"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
